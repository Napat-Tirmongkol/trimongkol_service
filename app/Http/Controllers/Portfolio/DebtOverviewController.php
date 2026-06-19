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

        // Current month — used as the $activeMonth context for management forms
        // (koyoso-debt-card partial needs it for redirect_month hidden inputs).
        $activeMonth = date('Y-m');

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

        // ── All debts with full payment history + logs (for management cards) ──
        $allDebts = collect();
        if (Schema::hasTable('portfolio_debts')) {
            $allDebts = Debt::forUser($userId)
                ->with([
                    'payments' => fn ($q) => $q->orderBy('month'),
                    'payments.logs' => fn ($q) => $q->orderBy('paid_on', 'desc'),
                ])
                ->orderBy('id')
                ->get();
        }

        // ── Debts with future payment schedule only (for the plan table) ──────
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

        // ── Spread กยศ yearly targets into monthly savings ────────────────
        // กยศ is stored as one target per year (due 5 July). The user wants to
        // SAVE for it monthly, so each year's outstanding remainder is spread
        // across its collection window — the 10 months (Sep–Jun) before the
        // July due date — instead of landing as one lump in July. paid_amount
        // (from the real payment logs) shrinks the remainder, so the suggested
        // monthly saving adapts to what has already been paid.
        $gysByMonth = []; // 'YYYY-MM' => ['debt_id', 'debt_label', 'amount', 'payment_id']
        foreach ($debts as $debt) {
            if (!str_contains($debt->label, 'กยศ')) {
                continue;
            }
            foreach ($debt->payments as $payment) {
                $remaining = max(0.0, (float) $payment->amount - (float) $payment->paid_amount);
                if ($remaining <= 0) {
                    continue; // งวดนี้จ่ายครบแล้ว
                }
                $targetYM = $payment->month; // YYYY-07 due month

                // Collection window: the 10 months immediately before the due
                // month (Sep of the previous year through Jun of the due year).
                $window = [];
                for ($k = 10; $k >= 1; $k--) {
                    $window[] = Carbon::parse($targetYM . '-01')->subMonths($k)->format('Y-m');
                }

                // Only plan months from next month onward.
                $eligible = array_values(array_filter($window, fn ($m) => $m >= $startYM));
                if (empty($eligible)) {
                    // Collection window already passed — the remainder is due on
                    // the July date itself, if that month is still in the plan.
                    if ($targetYM >= $startYM) {
                        $eligible = [$targetYM];
                    } else {
                        continue;
                    }
                }

                $per = $remaining / count($eligible);
                foreach ($eligible as $m) {
                    $gysByMonth[$m] = [
                        'debt_id'    => $debt->id,
                        'debt_label' => $debt->label,
                        'amount'     => $per,
                        'payment_id' => $payment->id,
                    ];
                }
            }
        }

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

            // Debt payment schedule (pre-seeded rows). กยศ is handled separately
            // below via $gysByMonth (yearly target spread across its savings window).
            foreach ($debts as $debt) {
                if (str_contains($debt->label, 'กยศ')) {
                    continue;
                }
                $payment = $debt->payments->firstWhere('month', $ym);
                if ($payment) {
                    $amt = (float) $payment->amount;
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

            // กยศ monthly saving for this month (spread from the yearly target).
            if (isset($gysByMonth[$ym])) {
                $g = $gysByMonth[$ym];
                $row['debt_payments'][] = [
                    'id'         => $g['payment_id'],
                    'debt_id'    => $g['debt_id'],
                    'debt_label' => $g['debt_label'],
                    'amount'     => $g['amount'],
                ];
                $row['total'] += $g['amount'];
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
            'installments', 'debts', 'allDebts', 'schedule', 'startYM',
            'activeMonth', 'firstMonthData', 'firstMonthTotal', 'totalRemaining',
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
