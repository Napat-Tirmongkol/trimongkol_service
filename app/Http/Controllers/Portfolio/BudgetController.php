<?php

namespace App\Http\Controllers\Portfolio;

use App\Http\Controllers\Controller;
use App\Models\Portfolio\BudgetItem;
use App\Models\Portfolio\Installment;
use App\Models\Portfolio\Subscription;
use App\Models\Portfolio\Income;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function index()
    {
        $userId = (int) auth()->id();

        // 1. Fetch or create Income
        $income = Income::firstOrCreate(
            ['user_id' => $userId],
            ['income_amount' => 0.00]
        );

        // 2. Fetch Budget Items (ordered by sort_order, then ID)
        $items = BudgetItem::query()
            ->forUser($userId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $fixedExpensesList = $items->where('category', BudgetItem::CATEGORY_FIXED);
        $variableExpensesList = $items->where('category', BudgetItem::CATEGORY_VARIABLE);
        $savingsList = $items->where('category', BudgetItem::CATEGORY_SAVING);

        // 3. Fetch Installments
        $installments = Installment::query()
            ->forUser($userId)
            ->orderBy('id')
            ->get();

        // 4. Fetch Subscriptions
        $subscriptions = Subscription::query()
            ->forUser($userId)
            ->orderBy('billing_day')
            ->orderBy('id')
            ->get();

        // 5. Calculations
        $installmentsPaymentSum = (float) $installments->sum('monthly_payment');
        $subscriptionsPaymentSum = (float) $subscriptions->sum('monthly_payment');

        $fixedTotal = (float) $fixedExpensesList->sum('amount') + $installmentsPaymentSum + $subscriptionsPaymentSum;
        $variableTotal = (float) $variableExpensesList->sum('amount');
        $savingsTotal = (float) $savingsList->sum('amount');

        $totalExpenses = $fixedTotal + $variableTotal + $savingsTotal;
        $incomeAmount = (float) $income->income_amount;
        $remainingAmount = $incomeAmount - $totalExpenses;

        return view('portfolio.budget', compact(
            'income',
            'fixedExpensesList',
            'variableExpensesList',
            'savingsList',
            'installments',
            'subscriptions',
            'installmentsPaymentSum',
            'subscriptionsPaymentSum',
            'fixedTotal',
            'variableTotal',
            'savingsTotal',
            'totalExpenses',
            'remainingAmount'
        ));
    }

    public function updateIncome(Request $request)
    {
        $userId = (int) auth()->id();
        $data = $request->validate([
            'income_amount' => 'required|numeric|min:0',
        ]);

        Income::updateOrCreate(
            ['user_id' => $userId],
            ['income_amount' => $data['income_amount']]
        );

        return redirect()->route('portfolio.budget.index')
            ->with('status', __('app.portfolio.budget.income_updated'));
    }

    public function storeBudgetItem(Request $request)
    {
        $data = $request->validate([
            'category' => 'required|string|in:fixed_expense,variable_expense,saving',
            'label' => 'required|string|max:120',
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);
        $data['user_id'] = (int) auth()->id();

        BudgetItem::create($data);

        return redirect()->route('portfolio.budget.index')
            ->with('status', __('app.portfolio.budget.item_created'));
    }

    public function updateBudgetItem(Request $request, BudgetItem $item)
    {
        $this->authorizeItem($item);

        $data = $request->validate([
            'label' => 'required|string|max:120',
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $item->update($data);

        return redirect()->route('portfolio.budget.index')
            ->with('status', __('app.portfolio.budget.item_updated'));
    }

    public function destroyBudgetItem(BudgetItem $item)
    {
        $this->authorizeItem($item);
        $item->delete();

        return redirect()->route('portfolio.budget.index')
            ->with('status', __('app.portfolio.budget.item_deleted'));
    }

    public function storeInstallment(Request $request)
    {
        $data = $request->validate([
            'label' => 'required|string|max:120',
            'monthly_payment' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'total_months' => 'required|integer|min:1',
            'paid_months' => 'required|integer|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);
        
        if ($data['paid_months'] > $data['total_months']) {
            $data['paid_months'] = $data['total_months'];
        }
        
        $data['user_id'] = (int) auth()->id();

        Installment::create($data);

        return redirect()->route('portfolio.budget.index')
            ->with('status', __('app.portfolio.budget.installment_created'));
    }

    public function updateInstallment(Request $request, Installment $installment)
    {
        $this->authorizeInstallment($installment);

        $data = $request->validate([
            'label' => 'required|string|max:120',
            'monthly_payment' => 'required|numeric|min:0',
            'total_amount' => 'required|numeric|min:0',
            'total_months' => 'required|integer|min:1',
            'paid_months' => 'required|integer|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        if ($data['paid_months'] > $data['total_months']) {
            $data['paid_months'] = $data['total_months'];
        }

        $installment->update($data);

        return redirect()->route('portfolio.budget.index')
            ->with('status', __('app.portfolio.budget.installment_updated'));
    }

    public function destroyInstallment(Installment $installment)
    {
        $this->authorizeInstallment($installment);
        $installment->delete();

        return redirect()->route('portfolio.budget.index')
            ->with('status', __('app.portfolio.budget.installment_deleted'));
    }

    public function storeSubscription(Request $request)
    {
        $data = $request->validate([
            'label' => 'required|string|max:120',
            'monthly_payment' => 'required|numeric|min:0',
            'billing_day' => 'nullable|integer|between:1,31',
            'notes' => 'nullable|string|max:1000',
        ]);
        $data['user_id'] = (int) auth()->id();

        Subscription::create($data);

        return redirect()->route('portfolio.budget.index')
            ->with('status', __('app.portfolio.budget.subscription_created'));
    }

    public function updateSubscription(Request $request, Subscription $subscription)
    {
        $this->authorizeSubscription($subscription);

        $data = $request->validate([
            'label' => 'required|string|max:120',
            'monthly_payment' => 'required|numeric|min:0',
            'billing_day' => 'nullable|integer|between:1,31',
            'notes' => 'nullable|string|max:1000',
        ]);

        $subscription->update($data);

        return redirect()->route('portfolio.budget.index')
            ->with('status', __('app.portfolio.budget.subscription_updated'));
    }

    public function destroySubscription(Subscription $subscription)
    {
        $this->authorizeSubscription($subscription);
        $subscription->delete();

        return redirect()->route('portfolio.budget.index')
            ->with('status', __('app.portfolio.budget.subscription_deleted'));
    }

    public function toggleCheck(Request $request, string $type, int $id)
    {
        $userId = (int) auth()->id();

        if ($type === 'item') {
            $model = BudgetItem::findOrFail($id);
            $this->authorizeItem($model);
        } elseif ($type === 'installment') {
            $model = Installment::findOrFail($id);
            $this->authorizeInstallment($model);
        } elseif ($type === 'subscription') {
            $model = Subscription::findOrFail($id);
            $this->authorizeSubscription($model);
        } else {
            abort(400);
        }

        $model->update([
            'is_checked' => !$model->is_checked
        ]);

        if ($request->wantsJson()) {
            return response()->json(['success' => true, 'is_checked' => $model->is_checked]);
        }

        return redirect()->back();
    }

    public function resetMonth()
    {
        $userId = (int) auth()->id();

        // 1. For all checked installments, increment paid_months (not exceeding total_months)
        $installments = Installment::query()
            ->forUser($userId)
            ->where('is_checked', true)
            ->get();

        foreach ($installments as $inst) {
            $newPaid = min($inst->total_months, $inst->paid_months + 1);
            $inst->update([
                'paid_months' => $newPaid,
                'is_checked' => false,
            ]);
        }

        // 2. Uncheck unchecked installments as well just in case, plus all budget items and subscriptions
        Installment::query()->forUser($userId)->update(['is_checked' => false]);
        BudgetItem::query()->forUser($userId)->update(['is_checked' => false]);
        Subscription::query()->forUser($userId)->update(['is_checked' => false]);

        return redirect()->route('portfolio.budget.index')
            ->with('status', __('app.portfolio.budget.month_reset_success'));
    }

    private function authorizeItem(BudgetItem $item): void
    {
        abort_unless($item->user_id === (int) auth()->id(), 403);
    }

    private function authorizeInstallment(Installment $installment): void
    {
        abort_unless($installment->user_id === (int) auth()->id(), 403);
    }

    private function authorizeSubscription(Subscription $subscription): void
    {
        abort_unless($subscription->user_id === (int) auth()->id(), 403);
    }
}
