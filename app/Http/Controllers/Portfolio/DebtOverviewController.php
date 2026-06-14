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
        $userId = $this->resolvePortfolioUserId();

        // Plan starts NEXT month. The current month is tracked in the budget
        // worksheet, so it is intentionally excluded from this forward plan.
        $startYM = Carbon::parse(date('Y-m') . '-01')->addMonth()->format('Y-m');

        // ── Installments: most-recent record per label ────────────────────
        // Using a single snapMonth misses installments added after the last
        // "new month" copy (e.g. SEasyCash has no 2026-06 row if it was added
        // mid-month). Taking the newest record per label is always correct.
        $installments = collect();
        if (Schema::hasTable('portfolio_installments')) {
            $installments = Installment::forUser($userId)
                ->where('total_months', '>', 0)
                ->orderBy('month', 'desc')
                ->get()
                ->unique('label')
                ->filter(fn ($i) => ((int) $i->total_months - (int) $i->paid_months) > 0)
                ->sortBy('label')
                ->values();
        }

        // ── Debts with future payment schedule only ───────────────────────
        $debts = collect();
        if (Schema::hasTable('portfolio_debts')) {
            $debts = Debt::forUser($userId)
                ->with(['payments' => fn ($q) => $q->where('month', '>=', $startYM)->orderBy('month')])
                ->get()
                ->filter(fn ($d) => $d->payments->isNotEmpty());
        }

        // ── Determine how far out to compute ──────────────────────────────
        // Horizon must track the latest payment DATE, not a count. Installments
        // run consecutively from the start month, but debt schedules (e.g. กยศ)
        // skip months — so 140 payments can span ~168 calendar months. Using a
        // count would truncate the tail of the schedule.
        $maxInstallmentHorizon = $installments->max(
            fn ($i) => (int) $i->total_months - (int) $i->paid_months
        ) ?? 0;

        $latestYM = $startYM;
        if ($maxInstallmentHorizon > 0) {
            $insLatest = Carbon::parse($startYM . '-01')
                ->addMonths($maxInstallmentHorizon - 1)->format('Y-m');
            if ($insLatest > $latestYM) $latestYM = $insLatest;
        }
        foreach ($debts as $debt) {
            $debtLatest = $debt->payments->max('month'); // YYYY-MM sorts lexicographically
            if ($debtLatest && $debtLatest > $latestYM) $latestYM = $debtLatest;
        }

        $start         = Carbon::parse($startYM . '-01');
        $latest        = Carbon::parse($latestYM . '-01');
        $horizonMonths = ($latest->year - $start->year) * 12
            + ($latest->month - $start->month) + 1;
        $horizonMonths = min(max($horizonMonths, 12), 240);

        // ── Build month-by-month schedule (from next month onward) ────────
        $schedule        = [];
        $maxMonthlyTotal = 1.0;

        for ($i = 0; $i < $horizonMonths; $i++) {
            $ym  = Carbon::parse($startYM . '-01')->addMonths($i)->format('Y-m');
            $row = [
                'month'         => $ym,
                'installments'  => [],
                'debt_payments' => [],
                'total'         => 0.0,
            ];

            // Installments: active for the next `remaining` months
            foreach ($installments as $ins) {
                $remaining = (int) $ins->total_months - (int) $ins->paid_months;
                if ($i < $remaining) {
                    $amt = (float) $ins->monthly_payment;
                    $row['installments'][] = [
                        'id'          => $ins->id,
                        'label'       => $ins->label,
                        'amount'      => $amt,
                        'months_left' => $remaining - $i,
                    ];
                    $row['total'] += $amt;
                }
            }

            // Debt payment schedule (pre-seeded rows). กยศ rows are yearly lumps
            // (due each July) paid flexibly, so plan the outstanding remainder.
            foreach ($debts as $debt) {
                $payment = $debt->payments->firstWhere('month', $ym);
                if ($payment) {
                    $amt = str_contains($debt->label, 'กยศ')
                        ? max(0.0, (float) $payment->amount - (float) $payment->paid_amount)
                        : (float) $payment->amount;
                    if ($amt <= 0) {
                        continue;
                    }
                    $row['debt_payments'][] = [
                        'id'         => $payment->id,
                        'debt_id'    => $debt->id,
                        'debt_label' => $debt->label,
                        'amount'     => $amt,
                    ];
                    $row['total'] += $amt;
                }
            }

            if ($row['total'] > 0) {
                if ($row['total'] > $maxMonthlyTotal) $maxMonthlyTotal = $row['total'];
                $schedule[$ym] = $row;
            }
        }

        // ── Summary numbers ───────────────────────────────────────────────
        $totalRemainingInstallments = $installments->sum(fn ($i) => $i->remainingBalance());
        $totalRemainingDebts = $debts->sum(function ($d) {
            if (str_contains($d->label, 'กยศ')) {
                return (float) $d->payments->sum(
                    fn ($p) => max(0.0, (float) $p->amount - (float) $p->paid_amount)
                );
            }
            return (float) $d->payments->where('is_paid', false)->sum('amount');
        });
        $totalRemaining   = $totalRemainingInstallments + $totalRemainingDebts;
        $firstMonthData   = $schedule[$startYM] ?? null;
        $firstMonthTotal  = $firstMonthData['total'] ?? 0.0;

        // Average monthly payment over the next 12 scheduled months
        $next12 = array_slice(array_values($schedule), 0, 12);
        $avgMonthly = count($next12) > 0
            ? collect($next12)->avg('total')
            : 0.0;

        return view('portfolio.debt-overview', compact(
            'installments', 'debts', 'schedule', 'startYM',
            'firstMonthData', 'firstMonthTotal', 'totalRemaining',
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
