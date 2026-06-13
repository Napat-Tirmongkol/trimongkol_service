<?php

namespace App\Http\Controllers\Portfolio;

use App\Http\Controllers\Controller;
use App\Models\Portfolio\Debt;
use App\Models\Portfolio\Installment;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schema;

class DebtOverviewController extends Controller
{
    public function index()
    {
        $userId    = $this->resolvePortfolioUserId();
        $currentYM = date('Y-m');

        // ── Installments: snapshot from current month, or most recent ─────
        $installments = collect();
        if (Schema::hasTable('portfolio_installments')) {
            $snapMonth = Installment::forUser($userId)->where('month', $currentYM)->exists()
                ? $currentYM
                : Installment::forUser($userId)->orderBy('month', 'desc')->value('month');

            if ($snapMonth) {
                $installments = Installment::forUser($userId)
                    ->where('month', $snapMonth)
                    ->where('total_months', '>', 0)
                    ->orderBy('label')
                    ->get()
                    ->filter(fn ($i) => ((int) $i->total_months - (int) $i->paid_months) > 0);
            }
        }

        // ── Debts with future payment schedule only ───────────────────────
        $debts = collect();
        if (Schema::hasTable('portfolio_debts')) {
            $debts = Debt::forUser($userId)
                ->with(['payments' => fn ($q) => $q->where('month', '>=', $currentYM)->orderBy('month')])
                ->get()
                ->filter(fn ($d) => $d->payments->isNotEmpty());
        }

        // ── Determine how many months to compute ─────────────────────────
        $maxInstallmentHorizon = $installments->max(
            fn ($i) => (int) $i->total_months - (int) $i->paid_months
        ) ?? 0;

        $maxDebtHorizon = $debts->max(fn ($d) => $d->payments->count()) ?? 0;

        $horizonMonths = min(max($maxInstallmentHorizon, $maxDebtHorizon, 12), 240);

        // ── Build month-by-month schedule ─────────────────────────────────
        $schedule        = [];
        $maxMonthlyTotal = 1.0;

        for ($i = 0; $i < $horizonMonths; $i++) {
            $ym  = Carbon::parse($currentYM . '-01')->addMonths($i)->format('Y-m');
            $row = [
                'month'         => $ym,
                'installments'  => [],
                'debt_payments' => [],
                'total'         => 0.0,
                'paid_total'    => 0.0,
            ];

            // Installments: active for the next `remaining` months
            foreach ($installments as $ins) {
                $remaining = (int) $ins->total_months - (int) $ins->paid_months;
                if ($i < $remaining) {
                    $amt = (float) $ins->monthly_payment;
                    // Current-month installment reflects is_checked state
                    $isPaid = ($i === 0) && (bool) $ins->is_checked;
                    $row['installments'][] = [
                        'id'      => $ins->id,
                        'label'   => $ins->label,
                        'amount'  => $amt,
                        'is_paid' => $isPaid,
                        'months_left' => $remaining - $i,
                    ];
                    $row['total'] += $amt;
                    if ($isPaid) $row['paid_total'] += $amt;
                }
            }

            // Debt payment schedule (pre-seeded rows)
            foreach ($debts as $debt) {
                $payment = $debt->payments->firstWhere('month', $ym);
                if ($payment) {
                    $amt = (float) $payment->amount;
                    $row['debt_payments'][] = [
                        'id'         => $payment->id,
                        'debt_id'    => $debt->id,
                        'debt_label' => $debt->label,
                        'amount'     => $amt,
                        'is_paid'    => (bool) $payment->is_paid,
                    ];
                    $row['total'] += $amt;
                    if ($payment->is_paid) $row['paid_total'] += $amt;
                }
            }

            if ($row['total'] > 0) {
                if ($row['total'] > $maxMonthlyTotal) $maxMonthlyTotal = $row['total'];
                $schedule[$ym] = $row;
            }
        }

        // ── Summary numbers ───────────────────────────────────────────────
        $totalRemainingInstallments = $installments->sum(fn ($i) => $i->remainingBalance());
        $totalRemainingDebts = $debts->sum(
            fn ($d) => (float) $d->payments->where('is_paid', false)->sum('amount')
        );
        $totalRemaining  = $totalRemainingInstallments + $totalRemainingDebts;
        $thisMonthData   = $schedule[$currentYM] ?? null;
        $thisMonthTotal  = $thisMonthData['total'] ?? 0.0;

        // Average monthly payment over next 12 months
        $next12 = array_slice(array_values($schedule), 0, 12);
        $avgMonthly = count($next12) > 0
            ? collect($next12)->avg('total')
            : 0.0;

        return view('portfolio.debt-overview', compact(
            'installments', 'debts', 'schedule', 'currentYM',
            'thisMonthData', 'thisMonthTotal', 'totalRemaining',
            'maxMonthlyTotal', 'avgMonthly'
        ));
    }

    private function resolvePortfolioUserId(): int
    {
        $allowed      = (array) config('portfolio.allowed_emails', []);
        $primaryEmail = !empty($allowed) ? strtolower($allowed[0]) : null;
        if ($primaryEmail) {
            $owner = \App\Models\User::where('email', $primaryEmail)->first();
            if ($owner) return $owner->id;
        }
        return (int) auth()->id();
    }
}
