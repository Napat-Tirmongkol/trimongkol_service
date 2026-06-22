<?php

namespace App\Http\Controllers\Portfolio;

use App\Http\Controllers\Controller;
use App\Models\Portfolio\BudgetItem;
use App\Models\Portfolio\LedgerEntry;
use Illuminate\Http\Request;

class LedgerController extends Controller
{
    public function index(Request $request)
    {
        $userId = $this->resolvePortfolioUserId();

        $allMonths = LedgerEntry::query()
            ->forUser($userId)
            ->pluck('month')
            ->unique()
            ->sort()
            ->reverse()
            ->values();

        $activeMonth = $request->query('month');
        if (!$activeMonth || !preg_match('/^\d{4}-\d{2}$/', $activeMonth)) {
            $activeMonth = date('Y-m');
        }

        if (!$allMonths->contains($activeMonth)) {
            $allMonths = $allMonths->prepend($activeMonth);
        }

        $entries = LedgerEntry::query()
            ->forUser($userId)
            ->where('month', $activeMonth)
            ->with('budgetItem')
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $totalIncome  = (float) $entries->where('type', LedgerEntry::TYPE_INCOME)->sum('amount');
        $totalExpense = (float) $entries->where('type', LedgerEntry::TYPE_EXPENSE)->sum('amount');
        $net          = $totalIncome - $totalExpense;

        $byDate = $entries->groupBy(fn ($e) => $e->date->format('Y-m-d'));

        // Load budget items for the dropdown.
        // Try the active month first; if that month has no items, fall back to
        // the most recent month at or before it that does have items (never a
        // future month, even if one happens to have budget items set up).
        $budgetItems       = collect();
        $budgetItemMonth   = null;
        if (\Illuminate\Support\Facades\Schema::hasTable('portfolio_budget_items')) {
            $hasActive = BudgetItem::query()->forUser($userId)->where('month', $activeMonth)->exists();
            $budgetItemMonth = $hasActive
                ? $activeMonth
                : BudgetItem::query()->forUser($userId)->where('month', '<=', $activeMonth)->orderBy('month', 'desc')->value('month');

            if ($budgetItemMonth) {
                $budgetItems = BudgetItem::query()
                    ->forUser($userId)
                    ->where('month', $budgetItemMonth)
                    ->orderBy('category')
                    ->orderBy('label')
                    ->get();
            }
        }

        // ── Line-chart data (cumulative ต่อวัน, คำนวณฝั่ง client) ──
        // เชื่อมหมวดงบผ่าน budget_item_id ที่มีอยู่แล้ว — ไม่ต้องเพิ่มคอลัมน์
        $daysInMonth  = \Illuminate\Support\Carbon::parse($activeMonth . '-01')->daysInMonth;
        $chartEntries = $entries->map(fn ($e) => [
            'd'    => (int) $e->date->day,
            'amt'  => (float) $e->amount,
            'type' => $e->type,
            'bid'  => $e->budget_item_id,
        ])->values();
        $chartPlanned = $budgetItems->mapWithKeys(fn ($b) => [$b->id => (float) $b->amount]);

        return view('portfolio.ledger', compact(
            'allMonths', 'activeMonth', 'entries', 'byDate',
            'totalIncome', 'totalExpense', 'net', 'budgetItems', 'budgetItemMonth',
            'daysInMonth', 'chartEntries', 'chartPlanned'
        ));
    }

    public function store(Request $request)
    {
        $userId = $this->resolvePortfolioUserId();

        $data = $request->validate([
            'date'           => 'required|date',
            'type'           => 'required|in:income,expense',
            'amount'         => 'required|numeric|min:0.01',
            'label'          => 'required|string|max:200',
            'budget_item_id' => 'nullable|integer',
            'notes'          => 'nullable|string|max:1000',
        ]);

        // Only expenses can link to a budget item; validate ownership
        if ($data['type'] !== LedgerEntry::TYPE_EXPENSE || empty($data['budget_item_id'])) {
            $data['budget_item_id'] = null;
        } else {
            $exists = BudgetItem::where('id', $data['budget_item_id'])
                ->where('user_id', $userId)
                ->exists();
            if (!$exists) {
                $data['budget_item_id'] = null;
            }
        }

        $data['user_id'] = $userId;
        $data['month']   = substr($data['date'], 0, 7);

        $entry = LedgerEntry::create($data);

        $this->syncActualAmount($entry->budget_item_id);

        return redirect()
            ->route('portfolio.ledger.index', ['month' => $data['month']])
            ->with('status', __('app.portfolio.ledger.entry_created'));
    }

    public function update(Request $request, LedgerEntry $entry)
    {
        abort_unless($entry->user_id === $this->resolvePortfolioUserId(), 403);

        $data = $request->validate([
            'date'           => 'required|date',
            'type'           => 'required|in:income,expense',
            'amount'         => 'required|numeric|min:0.01',
            'label'          => 'required|string|max:200',
            'budget_item_id' => 'nullable|integer',
            'notes'          => 'nullable|string|max:1000',
        ]);

        $oldBudgetItemId = $entry->budget_item_id;

        if ($data['type'] !== LedgerEntry::TYPE_EXPENSE || empty($data['budget_item_id'])) {
            $data['budget_item_id'] = null;
        } else {
            $exists = BudgetItem::where('id', $data['budget_item_id'])
                ->where('user_id', $entry->user_id)
                ->exists();
            if (!$exists) {
                $data['budget_item_id'] = null;
            }
        }

        $data['month'] = substr($data['date'], 0, 7);

        $entry->update($data);

        $this->syncActualAmount($oldBudgetItemId);
        if ($entry->budget_item_id !== $oldBudgetItemId) {
            $this->syncActualAmount($entry->budget_item_id);
        }

        return redirect()
            ->route('portfolio.ledger.index', ['month' => $data['month']])
            ->with('status', __('app.portfolio.ledger.entry_updated'));
    }

    public function destroy(LedgerEntry $entry)
    {
        abort_unless($entry->user_id === $this->resolvePortfolioUserId(), 403);

        $month        = $entry->month;
        $budgetItemId = $entry->budget_item_id;

        $entry->delete();

        $this->syncActualAmount($budgetItemId);

        return redirect()
            ->route('portfolio.ledger.index', ['month' => $month])
            ->with('status', __('app.portfolio.ledger.entry_deleted'));
    }

    private function syncActualAmount(?int $budgetItemId): void
    {
        if (!$budgetItemId) return;

        $sum = LedgerEntry::where('budget_item_id', $budgetItemId)
            ->where('type', LedgerEntry::TYPE_EXPENSE)
            ->sum('amount');

        BudgetItem::where('id', $budgetItemId)->update(['actual_amount' => $sum]);
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
