<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Portfolio\Holding;
use App\Models\Portfolio\Snapshot;
use App\Services\AuditLog;
use App\Services\Portfolio\PriceFetcher;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Personal-portfolio dashboard for the site owner. Every action is scoped
 * to `auth()->id()`, so this is a private workspace even on a multi-admin
 * install — each admin sees only their own holdings.
 */
class PortfolioController extends Controller
{
    public function dashboard()
    {
        $userId = (int) auth()->id();

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

        $lastRefresh = $holdings->max('last_priced_at');

        return view('admin.portfolio.dashboard', compact(
            'holdings', 'byKind', 'totals', 'trend', 'lastRefresh',
        ));
    }

    public function create()
    {
        $holding = new Holding(['kind' => Holding::KIND_STOCK, 'currency' => 'THB', 'quantity' => 1]);

        return view('admin.portfolio.holdings.form', ['holding' => $holding]);
    }

    public function store(Request $request, PriceFetcher $prices)
    {
        $data = $this->validated($request);
        $data['user_id'] = (int) auth()->id();
        $data['current_value_thb'] = $prices->toThb(
            (float) ($data['current_price'] ?? 0),
            (float) $data['quantity'],
            $data['currency'],
        );

        $holding = Holding::create($data);
        AuditLog::record('portfolio.holding.create', null, "{$holding->kind}:{$holding->label}");

        return redirect()->route('admin.portfolio.dashboard')
            ->with('status', __('app.admin.portfolio.flash_created'));
    }

    public function edit(Holding $holding)
    {
        $this->authorizeOwnership($holding);

        return view('admin.portfolio.holdings.form', ['holding' => $holding]);
    }

    public function update(Request $request, Holding $holding, PriceFetcher $prices)
    {
        $this->authorizeOwnership($holding);

        $data = $this->validated($request);
        $data['current_value_thb'] = $prices->toThb(
            (float) ($data['current_price'] ?? 0),
            (float) $data['quantity'],
            $data['currency'],
        );

        $holding->update($data);
        AuditLog::record('portfolio.holding.update', null, "{$holding->kind}:{$holding->label}");

        return redirect()->route('admin.portfolio.dashboard')
            ->with('status', __('app.admin.portfolio.flash_updated'));
    }

    public function destroy(Holding $holding)
    {
        $this->authorizeOwnership($holding);

        $label = "{$holding->kind}:{$holding->label}";
        $holding->delete();
        AuditLog::record('portfolio.holding.delete', null, $label);

        return redirect()->route('admin.portfolio.dashboard')
            ->with('status', __('app.admin.portfolio.flash_deleted'));
    }

    public function refresh(PriceFetcher $prices)
    {
        $userId = (int) auth()->id();
        $result = $prices->refreshForUser($userId);

        $this->saveSnapshot($userId);
        AuditLog::record('portfolio.refresh', null, "updated={$result['updated']} failed={$result['failed']}");

        $msg = __('app.admin.portfolio.flash_refreshed', [
            'updated' => $result['updated'],
            'failed' => $result['failed'],
        ]);

        return $result['failed'] > 0
            ? redirect()->route('admin.portfolio.dashboard')->with('error', $msg)
            : redirect()->route('admin.portfolio.dashboard')->with('status', $msg);
    }

    /** Validation shared by store + update. */
    private function validated(Request $request): array
    {
        return $request->validate([
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
    }

    private function authorizeOwnership(Holding $holding): void
    {
        abort_unless($holding->user_id === (int) auth()->id(), 403);
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
}
