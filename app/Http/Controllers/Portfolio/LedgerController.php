<?php

namespace App\Http\Controllers\Portfolio;

use App\Http\Controllers\Controller;
use App\Models\Portfolio\BudgetItem;
use App\Models\Portfolio\Income;
use App\Models\Portfolio\Installment;
use App\Models\Portfolio\LedgerEntry;
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
            ->with(['budgetItem', 'installment', 'income'])
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

        return view('portfolio.ledger', compact(
            'allMonths', 'activeMonth', 'entries', 'byDate',
            'totalIncome', 'totalExpense', 'net',
            'budgetItems', 'budgetItemMonth',
            'installments', 'incomeItems'
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

        [$budgetItemId, $installmentId, $incomeId] = $this->resolveBudgetLink(
            $data['budget_link'] ?? '', $userId
        );

        unset($data['budget_link']);
        $data['budget_item_id'] = $budgetItemId;
        $data['installment_id'] = $installmentId;
        $data['income_id']      = $incomeId;
        $data['user_id']        = $userId;
        $data['month']          = substr($data['date'], 0, 7);

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

        [$budgetItemId, $installmentId, $incomeId] = $this->resolveBudgetLink(
            $data['budget_link'] ?? '', $userId
        );

        unset($data['budget_link']);
        $data['budget_item_id'] = $budgetItemId;
        $data['installment_id'] = $installmentId;
        $data['income_id']      = $incomeId;
        $data['month']          = substr($data['date'], 0, 7);

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

    // Decode "b:5", "i:3", "in:2" into [budget_item_id, installment_id, income_id]
    private function resolveBudgetLink(string $link, int $userId): array
    {
        $budgetItemId = null;
        $installmentId = null;
        $incomeId = null;

        if ($link === '' || $link === '0') {
            return [$budgetItemId, $installmentId, $incomeId];
        }

        if (str_starts_with($link, 'b:')) {
            $id = (int) substr($link, 2);
            if ($id && BudgetItem::where('id', $id)->where('user_id', $userId)->exists()) {
                $budgetItemId = $id;
            }
        } elseif (str_starts_with($link, 'i:')) {
            $id = (int) substr($link, 2);
            if ($id && Installment::where('id', $id)->where('user_id', $userId)->exists()) {
                $installmentId = $id;
            }
        } elseif (str_starts_with($link, 'in:')) {
            $id = (int) substr($link, 3);
            if ($id && Income::where('id', $id)->where('user_id', $userId)->exists()) {
                $incomeId = $id;
            }
        }

        return [$budgetItemId, $installmentId, $incomeId];
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
