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

        // Calculate remaining unpaid variable debts from budget
        $variableDebtsUnpaid = 0.0;
        $koyosoDebtsUnpaid = 0.0;
        if (\Illuminate\Support\Facades\Schema::hasTable('portfolio_debts')) {
            $variableDebts = \App\Models\Portfolio\Debt::query()
                ->forUser($userId)
                ->with('payments')
                ->get();
            foreach ($variableDebts as $d) {
                if ($d->total_amount > 0) {
                    $paidSum = $d->payments->where('is_paid', true)->sum('amount');
                    $unpaid = max(0.0, (float) $d->total_amount - $paidSum);
                    if (str_contains(strtolower($d->label), 'กยศ') || str_contains($d->label, 'กยศ')) {
                        $koyosoDebtsUnpaid += $unpaid;
                    } else {
                        $variableDebtsUnpaid += $unpaid;
                    }
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

        return view('portfolio.dashboard', compact(
            'holdings', 'byKind', 'totals', 'trend', 'lastRefresh',
        ));
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
