<?php

namespace App\Http\Controllers\Portfolio;

use App\Http\Controllers\Controller;
use App\Models\Portfolio\Holding;
use App\Models\Portfolio\Goal;
use App\Models\Portfolio\WatchlistItem;
use App\Services\Portfolio\PriceFetcher;
use Illuminate\Http\Request;

class PlannerController extends Controller
{
    public function index(PriceFetcher $prices)
    {
        $userId = $this->resolvePortfolioUserId();

        // 1. Fetch current holdings to calculate actual net worth and pass to What-if and Performance
        $holdings = Holding::query()
            ->forUser($userId)
            ->orderBy('sort_order')
            ->orderBy('kind')
            ->orderBy('label')
            ->get();

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
        $netWorth = $assets - $debts;

        // 2. Fetch Goals
        $goals = Goal::query()
            ->forUser($userId)
            ->orderBy('target_date')
            ->orderBy('id')
            ->get();

        // 3. Fetch Watchlist
        $watchlist = WatchlistItem::query()
            ->forUser($userId)
            ->orderBy('kind')
            ->orderBy('label')
            ->get();

        return view('portfolio.planner', compact(
            'holdings',
            'netWorth',
            'goals',
            'watchlist'
        ));
    }

    public function storeGoal(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:120',
            'target_amount' => 'required|numeric|min:0.01',
            'target_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ]);
        $data['user_id'] = $this->resolvePortfolioUserId();

        Goal::create($data);

        return redirect()->to(route('portfolio.planner') . '#goals')
            ->with('status', __('app.portfolio.planner.goal_created'));
    }

    public function updateGoal(Request $request, Goal $goal)
    {
        $this->authorizeGoal($goal);

        $data = $request->validate([
            'title' => 'required|string|max:120',
            'target_amount' => 'required|numeric|min:0.01',
            'target_date' => 'nullable|date',
            'notes' => 'nullable|string|max:1000',
        ]);

        $goal->update($data);

        return redirect()->to(route('portfolio.planner') . '#goals')
            ->with('status', __('app.portfolio.planner.goal_updated'));
    }

    public function destroyGoal(Goal $goal)
    {
        $this->authorizeGoal($goal);
        $goal->delete();

        return redirect()->to(route('portfolio.planner') . '#goals')
            ->with('status', __('app.portfolio.planner.goal_deleted'));
    }

    public function storeWatchlistItem(Request $request, PriceFetcher $prices)
    {
        $data = $request->validate([
            'kind' => 'required|in:stock,crypto,fund',
            'symbol' => 'required|string|max:32',
            'label' => 'required|string|max:120',
            'currency' => 'required|string|size:3',
            'notes' => 'nullable|string|max:1000',
        ]);
        $data['user_id'] = $this->resolvePortfolioUserId();

        $item = WatchlistItem::create($data);

        // Fetch price immediately
        $prices->refreshWatchlistForUser($this->resolvePortfolioUserId());

        return redirect()->to(route('portfolio.planner') . '#watchlist')
            ->with('status', __('app.portfolio.planner.watchlist_created'));
    }

    public function destroyWatchlistItem(WatchlistItem $item)
    {
        $this->authorizeWatchlist($item);
        $item->delete();

        return redirect()->to(route('portfolio.planner') . '#watchlist')
            ->with('status', __('app.portfolio.planner.watchlist_deleted'));
    }

    public function refreshWatchlist(PriceFetcher $prices)
    {
        $userId = $this->resolvePortfolioUserId();
        $result = $prices->refreshWatchlistForUser($userId);

        $msg = __('app.portfolio.planner.watchlist_refreshed', [
            'updated' => $result['updated'],
            'failed' => $result['failed'],
        ]);

        return $result['failed'] > 0
            ? redirect()->to(route('portfolio.planner') . '#watchlist')->with('error', $msg)
            : redirect()->to(route('portfolio.planner') . '#watchlist')->with('status', $msg);
    }

    private function authorizeGoal(Goal $goal): void
    {
        abort_unless($goal->user_id === $this->resolvePortfolioUserId(), 403);
    }

    private function authorizeWatchlist(WatchlistItem $item): void
    {
        abort_unless($item->user_id === $this->resolvePortfolioUserId(), 403);
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
