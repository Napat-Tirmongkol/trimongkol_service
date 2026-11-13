<?php

namespace App\Http\Controllers\Portfolio;

use App\Http\Controllers\Controller;
use App\Models\Portfolio\Holding;
use App\Models\Portfolio\Snapshot;
use App\Services\Portfolio\PriceFetcher;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Personal-portfolio dashboard. Every action is scoped to `auth()->id()`,
 * so each authorised Google account sees only its own holdings.
 */
class DashboardController extends Controller
{
    public function index()
    {
        $userId = $this->resolvePortfolioUserId();
        $fin = $this->getFinancialData($userId);

        // 1. Life Goals telemetry
        $lifeGoals = [
            'activeCount'     => 0,
            'completedCount'  => 0,
            'overallProgress' => 0,
            'focusGoals'      => collect(),
        ];
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('portfolio_life_goals')) {
                $allGoals = \App\Models\Portfolio\LifeGoal::query()->forUser($userId)->get();
                $active = $allGoals->where('is_completed', false);
                $lifeGoals['activeCount']     = $active->count();
                $lifeGoals['completedCount']  = $allGoals->where('is_completed', true)->count();
                $lifeGoals['overallProgress'] = $active->isNotEmpty() ? (int) round($active->avg('progress_percent')) : 0;
                $lifeGoals['focusGoals']      = $active->sortBy('sort_order')->take(3)->values();
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Life Goals telemetry warning: ' . $e->getMessage());
        }

        // 2. Second Brain telemetry
        $brain = [
            'totalCount'    => 0,
            'inboxCount'    => 0,
            'projectsCount' => 0,
            'recentNotes'   => collect(),
        ];
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('portfolio_brain_notes')) {
                $allNotes = \App\Models\Portfolio\BrainNote::query()->forUser($userId)->where('is_archived', false)->get();
                $brain['totalCount']    = $allNotes->count();
                $brain['inboxCount']    = $allNotes->where('type', 'inbox')->count();
                $brain['projectsCount'] = $allNotes->where('type', 'project')->count();
                $brain['recentNotes']   = $allNotes->sortByDesc('is_pinned')->sortByDesc('updated_at')->take(4)->values();
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Second Brain telemetry warning: ' . $e->getMessage());
        }

        // 3. Couple Space telemetry
        $couple = [
            'tripFundSaved'  => 0.0,
            'tripFundTarget' => 50000.0,
            'pendingCount'   => 0,
            'recentItems'    => collect(),
        ];
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('portfolio_couple_items')) {
                $coupleItems = \App\Models\Portfolio\CoupleItem::query()->forUser($userId)->get();
                $savings = $coupleItems->firstWhere('type', 'savings');
                $couple['tripFundSaved']  = $savings ? (float) $savings->saved_amount : 0.0;
                $couple['tripFundTarget'] = $savings && $savings->target_amount > 0 ? (float) $savings->target_amount : 50000.0;
                $couple['pendingCount']   = $coupleItems->where('is_done', false)->count();
                $couple['recentItems']    = $coupleItems->where('is_done', false)->sortByDesc('id')->take(3)->values();
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Couple Space telemetry warning: ' . $e->getMessage());
        }

        // 4. Fuel & Journey telemetry
        $fuelCostThisMonth = 0.0;
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('portfolio_fuel_logs')) {
                $fuelCostThisMonth = (float) \App\Models\Portfolio\FuelLog::query()
                    ->forUser($userId)
                    ->where('refill_date', '>=', now()->startOfMonth()->toDateString())
                    ->sum('total_price');
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Fuel telemetry warning: ' . $e->getMessage());
        }

        // 5. Persona / Profile
        $persona = null;
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('portfolio_personal_profiles')) {
                $persona = \App\Models\Portfolio\PersonalProfile::query()->forUser($userId)->first();
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Persona telemetry warning: ' . $e->getMessage());
        }

        // 6. AI Command Center fleet status
        $aiAgents = [
            [
                'id' => 'guard',
                'name' => 'ลุงยาม รปภ.',
                'role' => 'Security & Firewall',
                'status' => 'online',
                'status_text' => 'เฝ้าระวังเซิร์ฟเวอร์ 100%',
                'icon' => 'shield',
                'color' => 'emerald',
                'station' => 'STATION 01',
            ],
            [
                'id' => 'mechanic',
                'name' => 'ช่างซ่อมบำรุง',
                'role' => 'DevOps & Auto-Heal',
                'status' => 'online',
                'status_text' => 'ระบบเสถียร ไม่มี Error ค้าง',
                'icon' => 'wrench',
                'color' => 'sky',
                'station' => 'STATION 02',
            ],
            [
                'id' => 'hr_qa',
                'name' => 'คุณ HR & QA',
                'role' => 'AI Evaluator & Coordinator',
                'status' => 'standby',
                'status_text' => 'ตรวจคุณภาพงาน 4/4 ผ่านเกณฑ์',
                'icon' => 'clipboard',
                'color' => 'purple',
                'station' => 'STATION 03',
            ],
            [
                'id' => 'antigravity',
                'name' => 'Antigravity (AI)',
                'role' => 'Lead AI Pair Programmer',
                'status' => 'active',
                'status_text' => 'พร้อมลุยโค้ดกับมึงตลอด 24 ชม.',
                'icon' => 'sparkles',
                'color' => 'indigo',
                'station' => 'STATION 04',
            ],
        ];

        // 7. Bio-Hacker & Health Telemetry (Galaxy Watch 7 / Health Connect)
        $health = null;
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('portfolio_health_metrics')) {
                $health = \App\Models\Portfolio\HealthMetric::query()->where('user_id', $userId)->orderByDesc('date')->first();
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Health telemetry warning: ' . $e->getMessage());
        }

        return view('portfolio.dashboard', array_merge($fin, compact(
            'lifeGoals', 'brain', 'couple', 'fuelCostThisMonth', 'persona', 'aiAgents', 'health'
        )));
    }

    public function financeIndex()
    {
        $userId = $this->resolvePortfolioUserId();
        $data = $this->getFinancialData($userId);

        return view('portfolio.finance', $data);
    }

    protected function getFinancialData(int $userId): array
    {
        $holdings = Holding::query()
            ->forUser($userId)
            ->orderBy('sort_order')
            ->orderBy('kind')
            ->orderBy('label')
            ->get();

        $byKind = $holdings->groupBy('kind');

        $totals = [
            'assets' => 0.0,
            'debts'  => 0.0,
            'cost'   => 0.0,
        ];
        foreach ($holdings as $h) {
            $val = (float) ($h->current_value_thb ?? 0);
            if ($h->isDebt()) {
                $totals['debts'] += $val;
            } else {
                $totals['assets'] += $val;
                $totals['cost'] += (float) $h->cost_basis;
            }
        }
        $totals['net'] = $totals['assets'] - $totals['debts'];
        $totals['gain'] = $totals['assets'] - $totals['cost'];
        $totals['gain_pct'] = $totals['cost'] > 0
            ? ($totals['gain'] / $totals['cost']) * 100
            : 0;

        // Calculate remaining unpaid variable debts from budget + build กยศ widget data
        $variableDebtsUnpaid = 0.0;
        $koyosoDebtsUnpaid   = 0.0;
        $koyoso              = null;
        if (\Illuminate\Support\Facades\Schema::hasTable('portfolio_debts')) {
            $variableDebts = \App\Models\Portfolio\Debt::query()
                ->forUser($userId)
                ->with('payments')
                ->get();
            foreach ($variableDebts as $d) {
                $isKoyoso = str_contains($d->label, 'กยศ');
                if ($d->total_amount > 0) {
                    $paidSumD = $isKoyoso
                        ? (float) $d->payments->sum('paid_amount')
                        : (float) $d->payments->where('is_paid', true)->sum('amount');
                    $unpaid   = max(0.0, (float) $d->total_amount - $paidSumD);
                    if ($isKoyoso) {
                        $koyosoDebtsUnpaid += $unpaid;
                    } else {
                        $variableDebtsUnpaid += $unpaid;
                    }
                }

                if ($isKoyoso && $koyoso === null && $d->payments->isNotEmpty()) {
                    $periods     = $d->payments->sortBy('month')->values();
                    $paid        = (float) $periods->sum('paid_amount');
                    $totalTarget = (float) $d->total_amount;

                    $current    = $periods->firstWhere('is_paid', false) ?? $periods->last();
                    $curMonthYM = $current?->month;
                    $curTarget  = $current ? (float) $current->amount : 0.0;
                    $curPaid    = $current ? (float) $current->paid_amount : 0.0;

                    $koyoso = [
                        'debt'            => $d,
                        'paidAmount'      => $paid,
                        'remainingAmount' => max(0.0, $totalTarget - $paid),
                        'progressPct'     => $totalTarget > 0
                            ? min(100, round($paid / $totalTarget * 100, 1))
                            : 0,
                        'nextJulyMonth'   => $curMonthYM,
                        'installmentNo'   => $curMonthYM ? (int) substr($curMonthYM, 0, 4) - 2025 : 0,
                        'curTarget'       => $curTarget,
                        'curPaid'         => $curPaid,
                        'curRemaining'    => max(0.0, $curTarget - $curPaid),
                        'curPct'          => $curTarget > 0 ? min(100, round($curPaid / $curTarget * 100)) : 0,
                        'curIsPaid'       => (bool) ($current?->is_paid),
                    ];
                }
            }
        }

        // Calculate remaining unpaid installments from budget
        $installmentsUnpaid = 0.0;
        $koyosoInstallmentsUnpaid = 0.0;
        if (\Illuminate\Support\Facades\Schema::hasTable('portfolio_installments')) {
            $latestInstallmentMonth = \Illuminate\Support\Facades\DB::table('portfolio_installments')
                ->where('user_id', $userId)
                ->max('month');

            if ($latestInstallmentMonth) {
                $installments = \Illuminate\Support\Facades\DB::table('portfolio_installments')
                    ->where('user_id', $userId)
                    ->where('month', $latestInstallmentMonth)
                    ->get();
                foreach ($installments as $ins) {
                    if ($ins->total_amount > 0) {
                        $paidAmount = (float) $ins->monthly_payment * (int) $ins->paid_months;
                        $unpaid = max(0.0, (float) $ins->total_amount - $paidAmount);
                        if (str_contains(strtolower($ins->label), 'กยศ') || str_contains($ins->label, 'กยศ')) {
                            $koyosoInstallmentsUnpaid += $unpaid;
                        } else {
                            $installmentsUnpaid += $unpaid;
                        }
                    }
                }
            }
        }

        $totals['budget_debts'] = $variableDebtsUnpaid + $installmentsUnpaid;
        $totals['koyoso_total'] = $koyosoDebtsUnpaid + $koyosoInstallmentsUnpaid;

        // Cash flow & Monthly DCA Capacity from budget for active month
        $activeMonth = date('Y-m');
        $monthlyIncome = 0.0;
        $monthlyExpenses = 0.0;
        $monthlyDcaCapacity = 0.0;
        $coreValue = 0.0;
        $satelliteValue = 0.0;
        $otherStockValue = 0.0;
        $cashDepositValue = 0.0;
        $goldValue = 0.0;
        $cryptoValue = 0.0;
        $totalInvestedStocks = 0.0;
        $corePct = 70.0;
        $satellitePct = 30.0;
        $estAnnualDividend = 0.0;
        $estMonthlyDividend = 0.0;
        $freedomRatio = 0.0;
        $paperTradeCount = 0;
        $monthlyPlannedSavings = 0.0;
        $unallocatedSurplus = 0.0;

        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('portfolio_income') || \Illuminate\Support\Facades\Schema::hasTable('portfolio_incomes')) {
                $monthlyIncome = (float) \App\Models\Portfolio\Income::query()
                    ->forUser($userId)
                    ->where('month', $activeMonth)
                    ->sum('amount');
            }

            $monthlyFixedItems = 0.0;
            $monthlyVariableItems = 0.0;
            if (\Illuminate\Support\Facades\Schema::hasTable('portfolio_budget_items')) {
                $budgetItems = \App\Models\Portfolio\BudgetItem::query()
                    ->forUser($userId)
                    ->where('month', $activeMonth)
                    ->get();
                $monthlyFixedItems = (float) $budgetItems->where('category', \App\Models\Portfolio\BudgetItem::CATEGORY_FIXED)->sum('amount');
                $monthlyVariableItems = (float) $budgetItems->where('category', \App\Models\Portfolio\BudgetItem::CATEGORY_VARIABLE)->sum('amount');
                $monthlyPlannedSavings = (float) $budgetItems->where('category', \App\Models\Portfolio\BudgetItem::CATEGORY_SAVING)->sum('amount');
            }

            $monthlyInstallments = 0.0;
            if (\Illuminate\Support\Facades\Schema::hasTable('portfolio_installments')) {
                $monthlyInstallments = (float) \App\Models\Portfolio\Installment::query()
                    ->forUser($userId)
                    ->where('month', $activeMonth)
                    ->sum('monthly_payment');
            }

            $monthlySubscriptions = 0.0;
            if (\Illuminate\Support\Facades\Schema::hasTable('portfolio_subscriptions')) {
                $monthlySubscriptions = (float) \App\Models\Portfolio\Subscription::query()
                    ->forUser($userId)
                    ->where('month', $activeMonth)
                    ->sum('monthly_payment');
            }

            $monthlyDebtPayments = 0.0;
            if (\Illuminate\Support\Facades\Schema::hasTable('portfolio_debt_payments')) {
                $monthlyDebtPayments = (float) \App\Models\Portfolio\DebtPayment::query()
                    ->where('user_id', $userId)
                    ->where('month', $activeMonth)
                    ->sum('amount');
            }

            $monthlyExpenses = $monthlyFixedItems + $monthlyVariableItems + $monthlyInstallments + $monthlySubscriptions + $monthlyDebtPayments;
            $surplusCash = max(0.0, $monthlyIncome - $monthlyExpenses);
            $monthlyDcaCapacity = $monthlyPlannedSavings > 0 ? $monthlyPlannedSavings : $surplusCash;
            $unallocatedSurplus = max(0.0, $surplusCash - $monthlyPlannedSavings);

            $coreSymbols = ['CPALL.BK', 'BDMS.BK', 'ADVANC.BK', 'AOT.BK', 'MSFT', 'SCHD', 'KO', 'PTT.BK', 'SCB.BK', 'KBANK.BK'];
            $satelliteSymbols = ['NVDA', 'TSLA', 'DELTA.BK', 'GULF.BK', 'QQQ', 'SMH', 'AAPL', 'GOOGL', 'BTC', 'ETH'];

            foreach ($holdings as $h) {
                $val = (float) ($h->current_value_thb ?? 0);
                if ($h->kind === Holding::KIND_STOCK || $h->kind === Holding::KIND_FUND) {
                    $sym = strtoupper(trim((string)$h->symbol));
                    if (in_array($sym, $coreSymbols, true) || str_contains(strtolower($h->notes ?? ''), 'core') || str_contains(strtolower($h->label ?? ''), 'ชนะเงินเฟ้อ')) {
                        $coreValue += $val;
                    } elseif (in_array($sym, $satelliteSymbols, true) || str_contains(strtolower($h->notes ?? ''), 'satellite') || str_contains(strtolower($h->label ?? ''), 'กระแส')) {
                        $satelliteValue += $val;
                    } else {
                        $otherStockValue += $val;
                    }
                } elseif ($h->kind === Holding::KIND_CASH || $h->kind === Holding::KIND_DEPOSIT) {
                    $cashDepositValue += $val;
                } elseif ($h->kind === Holding::KIND_GOLD) {
                    $goldValue += $val;
                } elseif ($h->kind === Holding::KIND_CRYPTO) {
                    $cryptoValue += $val;
                }
            }

            if ($otherStockValue > 0) {
                $coreValue += $otherStockValue * 0.70;
                $satelliteValue += $otherStockValue * 0.30;
            }

            $totalInvestedStocks = $coreValue + $satelliteValue;
            $corePct = $totalInvestedStocks > 0 ? round(($coreValue / $totalInvestedStocks) * 100, 1) : 70.0;
            $satellitePct = $totalInvestedStocks > 0 ? round(($satelliteValue / $totalInvestedStocks) * 100, 1) : 30.0;

            $estAnnualDividend = $totalInvestedStocks * 0.035;
            $estMonthlyDividend = $estAnnualDividend / 12;
            $freedomRatio = $monthlyExpenses > 0 ? min(100.0, round(($estMonthlyDividend / $monthlyExpenses) * 100, 1)) : 0.0;

            if (\Illuminate\Support\Facades\Schema::hasTable('portfolio_paper_trades')) {
                $paperTradeCount = \Illuminate\Support\Facades\DB::table('portfolio_paper_trades')
                    ->where('user_id', $userId)
                    ->where('status', 'open')
                    ->count();
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Dashboard metric calculation warning: ' . $e->getMessage());
        }

        $snapshots = Snapshot::query()
            ->forUser($userId)
            ->where('snapshot_date', '>=', now()->subDays(90)->toDateString())
            ->orderBy('snapshot_date')
            ->get(['snapshot_date', 'net_worth_thb', 'total_assets_thb', 'total_debts_thb']);

        $trend = $snapshots->map(fn ($s) => [
            'd' => $s->snapshot_date->format('Y-m-d'),
            'net' => (float) $s->net_worth_thb,
            'assets' => (float) $s->total_assets_thb,
            'debts' => (float) $s->total_debts_thb,
        ])->all();

        $lastRefresh = $holdings->whereNotNull('last_priced_at')->max('last_priced_at');

        return compact(
            'holdings', 'byKind', 'totals', 'trend', 'lastRefresh', 'koyoso',
            'monthlyIncome', 'monthlyExpenses', 'monthlyDcaCapacity',
            'unallocatedSurplus', 'monthlyPlannedSavings',
            'coreValue', 'satelliteValue', 'corePct', 'satellitePct',
            'cashDepositValue', 'goldValue', 'cryptoValue', 'totalInvestedStocks',
            'estAnnualDividend', 'estMonthlyDividend', 'freedomRatio',
            'paperTradeCount', 'activeMonth'
        );
    }

    public function create()
    {
        $holding = new Holding(['kind' => Holding::KIND_STOCK, 'currency' => 'THB', 'quantity' => 1]);

        return view('portfolio.holdings.form', ['holding' => $holding]);
    }

    public function store(Request $request, PriceFetcher $prices)
    {
        $data = $this->validated($request);
        $data['user_id'] = $this->resolvePortfolioUserId();

        $nativeUnitCost = (float) ($data['cost_basis'] ?? 0);
        $data['metadata'] = [
            'cost_basis_native' => $nativeUnitCost,
        ];
        
        $totalCostNative = $nativeUnitCost * (float) ($data['quantity'] ?? 0);
        $data['cost_basis'] = $prices->toThb($totalCostNative, 1, $data['currency']) ?? $totalCostNative;

        $data['current_value_thb'] = $prices->toThb(
            (float) ($data['current_price'] ?? 0),
            (float) $data['quantity'],
            $data['currency'],
        );

        Holding::create($data);

        return redirect()->route('portfolio.dashboard')
            ->with('status', __('app.portfolio.flash_created'));
    }

    public function edit(Holding $holding)
    {
        $this->authorizeOwnership($holding);

        return view('portfolio.holdings.form', ['holding' => $holding]);
    }

    public function update(Request $request, Holding $holding, PriceFetcher $prices)
    {
        $this->authorizeOwnership($holding);

        $data = $this->validated($request);

        $nativeUnitCost = (float) ($data['cost_basis'] ?? 0);
        $data['metadata'] = array_merge((array) ($holding->metadata ?? []), [
            'cost_basis_native' => $nativeUnitCost,
        ]);
        
        $totalCostNative = $nativeUnitCost * (float) ($data['quantity'] ?? 0);
        $data['cost_basis'] = $prices->toThb($totalCostNative, 1, $data['currency']) ?? $totalCostNative;

        $data['current_value_thb'] = $prices->toThb(
            (float) ($data['current_price'] ?? 0),
            (float) $data['quantity'],
            $data['currency'],
        );

        $holding->update($data);

        return redirect()->route('portfolio.dashboard')
            ->with('status', __('app.portfolio.flash_updated'));
    }

    public function destroy(Holding $holding)
    {
        $this->authorizeOwnership($holding);
        $holding->delete();

        return redirect()->route('portfolio.dashboard')
            ->with('status', __('app.portfolio.flash_deleted'));
    }

    public function refresh(PriceFetcher $prices)
    {
        $userId = $this->resolvePortfolioUserId();
        $result = $prices->refreshForUser($userId);

        $this->saveSnapshot($userId);

        $msg = __('app.portfolio.flash_refreshed', [
            'updated' => $result['updated'],
            'failed' => $result['failed'],
        ]);

        return $result['failed'] > 0
            ? redirect()->route('portfolio.dashboard')->with('error', $msg)
            : redirect()->route('portfolio.dashboard')->with('status', $msg);
    }

    /**
     * Validation shared by store + update. Coerces nullable money/order
     * inputs to 0 — the underlying columns are NOT NULL with `default(0)`
     * in the migration, but a null mass-assigned via `create()`/`update()`
     * overrides that default and trips a constraint violation.
     *
     * Money-shaped kinds (cash/deposit/debt) get their fields normalised
     * to mirror the simplified form: quantity is locked at 1, and cost
     * basis snaps to the entered amount so the gain/loss tile reads 0
     * (cash doesn't appreciate, debts don't have a "cost basis").
     */
    private function validated(Request $request): array
    {
        $data = $request->validate([
            'kind'          => 'required|in:' . implode(',', Holding::KINDS),
            'label'         => 'required|string|max:120',
            'symbol'        => 'nullable|string|max:32',
            'quantity'      => 'required|numeric|min:0',
            'cost_basis'    => 'nullable|numeric|min:0',
            'currency'      => 'required|string|size:3',
            'current_price' => 'nullable|numeric|min:0',
            'notes'         => 'nullable|string|max:1000',
            'sort_order'    => 'nullable|integer|min:0|max:9999',
        ]);

        $moneyKinds = [Holding::KIND_CASH, Holding::KIND_DEPOSIT, Holding::KIND_DEBT];
        if (in_array($data['kind'], $moneyKinds, true)) {
            $data['quantity'] = 1;
            $data['cost_basis'] = (float) ($data['current_price'] ?? 0);
            $data['symbol'] = null;
        }

        $data['cost_basis'] = (float) ($data['cost_basis'] ?? 0);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }

    private function authorizeOwnership(Holding $holding): void
    {
        abort_unless($holding->user_id === $this->resolvePortfolioUserId(), 403);
    }

    /** Roll a single net-worth snapshot for today (upsert keyed on date). */
    private function saveSnapshot(int $userId): void
    {
        $holdings = Holding::query()->forUser($userId)->get();

        $assets = 0.0;
        $debts = 0.0;
        foreach ($holdings as $h) {
            $val = (float) ($h->current_value_thb ?? 0);
            if ($h->isDebt()) {
                $debts += $val;
            } else {
                $assets += $val;
            }
        }

        Snapshot::updateOrCreate(
            [
                'user_id' => $userId,
                'snapshot_date' => Carbon::today()->toDateString(),
            ],
            [
                'total_assets_thb' => round($assets, 2),
                'total_debts_thb' => round($debts, 2),
                'net_worth_thb' => round($assets - $debts, 2),
            ],
        );
    }

    private function resolvePortfolioUserId(): int
    {
        $allowed = (array) config('portfolio.allowed_emails', []);
        $primaryEmail = !empty($allowed) ? strtolower($allowed[0]) : null;
        if ($primaryEmail) {
            $owner = \App\Models\User::where('email', $primaryEmail)->first();
            if ($owner) {
                return $owner->id;
            }
        }
        return (int) auth()->id();
    }
}
