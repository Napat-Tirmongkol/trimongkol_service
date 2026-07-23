<?php

namespace App\Http\Controllers\Portfolio;

use App\Http\Controllers\Controller;
use App\Models\Portfolio\BudgetItem;
use App\Models\Portfolio\Debt;
use App\Models\Portfolio\Income;
use App\Models\Portfolio\Installment;
use App\Models\Portfolio\LedgerEntry;
use App\Models\Portfolio\Subscription;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

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
            ->with(['budgetItem', 'installment', 'income', 'subscription', 'debt'])
            ->orderBy('date', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        $totalIncome  = (float) $entries->where('type', LedgerEntry::TYPE_INCOME)->sum('amount');
        $totalExpense = (float) $entries->where('type', LedgerEntry::TYPE_EXPENSE)->sum('amount');
        $net          = $totalIncome - $totalExpense;

        $byDate = $entries->groupBy(fn ($e) => $e->date->format('Y-m-d'));

        // ── BudgetItems for dropdown ──────────────────────────────────────
        $budgetItems     = collect();
        $budgetItemMonth = null;
        if (Schema::hasTable('portfolio_budget_items')) {
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

        // ── Installments for dropdown (newest record per label, still active) ─
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

        // ── Income sources for dropdown ───────────────────────────────────
        $incomeItems = collect();
        if (Schema::hasTable('portfolio_income')) {
            $incomeMonth = Income::forUser($userId)
                ->where('month', '<=', $activeMonth)
                ->orderBy('month', 'desc')
                ->value('month');
            if ($incomeMonth) {
                $incomeItems = Income::forUser($userId)
                    ->where('month', $incomeMonth)
                    ->orderBy('label')
                    ->get();
            }
        }

        // ── Subscriptions for dropdown (newest record per label) ──────────
        $subscriptions = collect();
        if (Schema::hasTable('portfolio_subscriptions')) {
            $subscriptions = Subscription::forUser($userId)
                ->orderBy('month', 'desc')
                ->get()
                ->unique('label')
                ->sortBy('label')
                ->values();
        }

        // ── Debts for dropdown (persistent — กยศ ฯลฯ) ─────────────────────
        $debtItems = collect();
        if (Schema::hasTable('portfolio_debts')) {
            $debtItems = Debt::forUser($userId)
                ->orderBy('label')
                ->get();
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
            'totalIncome', 'totalExpense', 'net',
            'budgetItems', 'budgetItemMonth',
            'installments', 'incomeItems', 'subscriptions', 'debtItems',
            'daysInMonth', 'chartEntries', 'chartPlanned'
        ));
    }

    public function store(Request $request)
    {
        $userId = $this->resolvePortfolioUserId();

        $data = $request->validate([
            'date'        => 'required|date',
            'type'        => 'required|in:income,expense',
            'amount'      => 'required|numeric|min:0.01',
            'label'       => 'required|string|max:200',
            'budget_link' => 'nullable|string|max:30',
            'notes'       => 'nullable|string|max:1000',
        ]);

        $links = $this->resolveBudgetLink($data['budget_link'] ?? '', $userId);

        unset($data['budget_link']);
        $data = array_merge($data, $links);
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

        $userId = $this->resolvePortfolioUserId();

        $data = $request->validate([
            'date'        => 'required|date',
            'type'        => 'required|in:income,expense',
            'amount'      => 'required|numeric|min:0.01',
            'label'       => 'required|string|max:200',
            'budget_link' => 'nullable|string|max:30',
            'notes'       => 'nullable|string|max:1000',
        ]);

        $oldBudgetItemId = $entry->budget_item_id;

        $links = $this->resolveBudgetLink($data['budget_link'] ?? '', $userId);

        unset($data['budget_link']);
        $data = array_merge($data, $links);
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

    // Decode an encoded link ("b:5" / "i:3" / "in:2" / "s:4" / "d:1") into the
    // matching FK column. Returns all five columns (only one is ever non-null).
    private function resolveBudgetLink(string $link, int $userId): array
    {
        $cols = [
            'budget_item_id'  => null,
            'installment_id'  => null,
            'income_id'       => null,
            'subscription_id' => null,
            'debt_id'         => null,
        ];

        if ($link === '' || $link === '0') {
            return $cols;
        }

        // prefix => [column, model class]
        $map = [
            'b:'  => ['budget_item_id',  BudgetItem::class],
            'i:'  => ['installment_id',  Installment::class],
            'in:' => ['income_id',       Income::class],
            's:'  => ['subscription_id', Subscription::class],
            'd:'  => ['debt_id',         Debt::class],
        ];

        // Longest prefix first so "in:" wins over "i:".
        uksort($map, fn ($a, $b) => strlen($b) - strlen($a));

        foreach ($map as $prefix => [$column, $model]) {
            if (str_starts_with($link, $prefix)) {
                $id = (int) substr($link, strlen($prefix));
                if ($id && $model::where('id', $id)->where('user_id', $userId)->exists()) {
                    $cols[$column] = $id;
                }
                break;
            }
        }

        return $cols;
    }

    private function syncActualAmount(?int $budgetItemId): void
    {
        if (!$budgetItemId) return;

        $item = BudgetItem::find($budgetItemId);
        if (!$item) return;

        $sum = LedgerEntry::where('budget_item_id', $budgetItemId)
            ->where('type', LedgerEntry::TYPE_EXPENSE)
            ->sum('amount');

        $item->update(['actual_amount' => $sum]);

        // Saving deposits recorded in the ledger flow into the matching holding
        // (เงินฝาก/เงินสด) so the portfolio / net worth stays in sync.
        if ($item->category === BudgetItem::CATEGORY_SAVING) {
            app(\App\Services\Portfolio\SavingHoldingSync::class)->sync($item->fresh());
        }
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
