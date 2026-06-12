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
    public function index(Request $request)
    {
        $userId = (int) auth()->id();

        // 1. Get all months that contain records to populate history dropdown
        $allMonths = collect()
            ->merge(BudgetItem::query()->forUser($userId)->pluck('month'))
            ->merge(Installment::query()->forUser($userId)->pluck('month'))
            ->merge(Subscription::query()->forUser($userId)->pluck('month'))
            ->merge(Income::query()->forUser($userId)->pluck('month'))
            ->unique()
            ->sort()
            ->reverse()
            ->values();

        // 2. Determine active month
        $activeMonth = $request->query('month');
        if (!$activeMonth || !preg_match('/^\d{4}-\d{2}$/', $activeMonth)) {
            $activeMonth = $allMonths->first() ?: date('Y-m');
        }

        // 3. Fetch Income Sources for active month
        $incomes = Income::query()
            ->forUser($userId)
            ->where('month', $activeMonth)
            ->orderBy('id')
            ->get();

        // 4. Fetch Budget Items for active month
        $items = BudgetItem::query()
            ->forUser($userId)
            ->where('month', $activeMonth)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $fixedExpensesList = $items->where('category', BudgetItem::CATEGORY_FIXED);
        $variableExpensesList = $items->where('category', BudgetItem::CATEGORY_VARIABLE);
        $savingsList = $items->where('category', BudgetItem::CATEGORY_SAVING);

        // 5. Fetch Installments for active month
        $installments = Installment::query()
            ->forUser($userId)
            ->where('month', $activeMonth)
            ->orderBy('id')
            ->get();

        // 6. Fetch Subscriptions for active month
        $subscriptions = Subscription::query()
            ->forUser($userId)
            ->where('month', $activeMonth)
            ->orderBy('billing_day')
            ->orderBy('id')
            ->get();

        // 7. Calculations
        $incomeTotal = (float) $incomes->sum('amount');
        $installmentsPaymentSum = (float) $installments->sum('monthly_payment');
        $subscriptionsPaymentSum = (float) $subscriptions->sum('monthly_payment');

        $fixedTotal = (float) $fixedExpensesList->sum('amount') + $installmentsPaymentSum + $subscriptionsPaymentSum;
        $variableTotal = (float) $variableExpensesList->sum('amount');
        $savingsTotal = (float) $savingsList->sum('amount');

        $totalExpenses = $fixedTotal + $variableTotal + $savingsTotal;
        $remainingAmount = $incomeTotal - $totalExpenses;

        return view('portfolio.budget', compact(
            'activeMonth',
            'allMonths',
            'incomes',
            'incomeTotal',
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

    public function storeIncome(Request $request)
    {
        $data = $request->validate([
            'label' => 'required|string|max:120',
            'amount' => 'required|numeric|min:0',
            'month' => 'required|string|regex:/^\d{4}-\d{2}$/',
            'notes' => 'nullable|string|max:1000',
        ]);
        $data['user_id'] = (int) auth()->id();

        Income::create($data);

        return redirect()->route('portfolio.budget.index', ['month' => $data['month']])
            ->with('status', 'เพิ่มแหล่งรายได้เรียบร้อยแล้ว');
    }

    public function updateIncome(Request $request, Income $income)
    {
        $this->authorizeIncome($income);

        $data = $request->validate([
            'label' => 'required|string|max:120',
            'amount' => 'required|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $income->update($data);

        return redirect()->route('portfolio.budget.index', ['month' => $income->month])
            ->with('status', 'อัปเดตแหล่งรายได้เรียบร้อยแล้ว');
    }

    public function destroyIncome(Income $income)
    {
        $this->authorizeIncome($income);
        $month = $income->month;
        $income->delete();

        return redirect()->route('portfolio.budget.index', ['month' => $month])
            ->with('status', 'ลบแหล่งรายได้เรียบร้อยแล้ว');
    }

    public function storeBudgetItem(Request $request)
    {
        $data = $request->validate([
            'category' => 'required|string|in:fixed_expense,variable_expense,saving',
            'label' => 'required|string|max:120',
            'amount' => 'required|numeric|min:0',
            'month' => 'required|string|regex:/^\d{4}-\d{2}$/',
            'notes' => 'nullable|string|max:1000',
        ]);
        $data['user_id'] = (int) auth()->id();

        BudgetItem::create($data);

        return redirect()->route('portfolio.budget.index', ['month' => $data['month']])
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

        return redirect()->route('portfolio.budget.index', ['month' => $item->month])
            ->with('status', __('app.portfolio.budget.item_updated'));
    }

    public function destroyBudgetItem(BudgetItem $item)
    {
        $this->authorizeItem($item);
        $month = $item->month;
        $item->delete();

        return redirect()->route('portfolio.budget.index', ['month' => $month])
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
            'month' => 'required|string|regex:/^\d{4}-\d{2}$/',
            'notes' => 'nullable|string|max:1000',
        ]);
        
        if ($data['paid_months'] > $data['total_months']) {
            $data['paid_months'] = $data['total_months'];
        }
        
        $data['user_id'] = (int) auth()->id();

        Installment::create($data);

        return redirect()->route('portfolio.budget.index', ['month' => $data['month']])
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

        return redirect()->route('portfolio.budget.index', ['month' => $installment->month])
            ->with('status', __('app.portfolio.budget.installment_updated'));
    }

    public function destroyInstallment(Installment $installment)
    {
        $this->authorizeInstallment($installment);
        $month = $installment->month;
        $installment->delete();

        return redirect()->route('portfolio.budget.index', ['month' => $month])
            ->with('status', __('app.portfolio.budget.installment_deleted'));
    }

    public function storeSubscription(Request $request)
    {
        $data = $request->validate([
            'label' => 'required|string|max:120',
            'monthly_payment' => 'required|numeric|min:0',
            'billing_day' => 'nullable|integer|between:1,31',
            'month' => 'required|string|regex:/^\d{4}-\d{2}$/',
            'notes' => 'nullable|string|max:1000',
        ]);
        $data['user_id'] = (int) auth()->id();

        Subscription::create($data);

        return redirect()->route('portfolio.budget.index', ['month' => $data['month']])
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

        return redirect()->route('portfolio.budget.index', ['month' => $subscription->month])
            ->with('status', __('app.portfolio.budget.subscription_updated'));
    }

    public function destroySubscription(Subscription $subscription)
    {
        $this->authorizeSubscription($subscription);
        $month = $subscription->month;
        $subscription->delete();

        return redirect()->route('portfolio.budget.index', ['month' => $month])
            ->with('status', __('app.portfolio.budget.subscription_deleted'));
    }

    public function toggleCheck(Request $request, string $type, int $id)
    {
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

    public function resetMonth(Request $request)
    {
        $userId = (int) auth()->id();
        $currentMonth = $request->input('current_month');
        if (!$currentMonth || !preg_match('/^\d{4}-\d{2}$/', $currentMonth)) {
            $currentMonth = date('Y-m');
        }

        // Calculate next month
        $time = strtotime($currentMonth . '-01');
        $nextMonth = date('Y-m', strtotime('+1 month', $time));

        // Check if next month already has records. If so, just redirect.
        $exists = BudgetItem::query()->forUser($userId)->where('month', $nextMonth)->exists()
            || Installment::query()->forUser($userId)->where('month', $nextMonth)->exists()
            || Subscription::query()->forUser($userId)->where('month', $nextMonth)->exists()
            || Income::query()->forUser($userId)->where('month', $nextMonth)->exists();

        if (!$exists) {
            // 1. Copy Income sources
            $incomes = Income::query()->forUser($userId)->where('month', $currentMonth)->get();
            foreach ($incomes as $inc) {
                Income::create([
                    'user_id' => $userId,
                    'month' => $nextMonth,
                    'label' => $inc->label,
                    'amount' => $inc->amount,
                    'notes' => $inc->notes,
                ]);
            }

            // 2. Copy Budget Items (Fixed, Variable, Savings)
            $items = BudgetItem::query()->forUser($userId)->where('month', $currentMonth)->get();
            foreach ($items as $item) {
                BudgetItem::create([
                    'user_id' => $userId,
                    'month' => $nextMonth,
                    'category' => $item->category,
                    'label' => $item->label,
                    'amount' => $item->amount,
                    'is_checked' => false,
                    'sort_order' => $item->sort_order,
                    'notes' => $item->notes,
                ]);
            }

            // 3. Copy Subscriptions
            $subs = Subscription::query()->forUser($userId)->where('month', $currentMonth)->get();
            foreach ($subs as $sub) {
                Subscription::create([
                    'user_id' => $userId,
                    'month' => $nextMonth,
                    'label' => $sub->label,
                    'monthly_payment' => $sub->monthly_payment,
                    'billing_day' => $sub->billing_day,
                    'is_checked' => false,
                    'notes' => $sub->notes,
                ]);
            }

            // 4. Copy Installments (incrementing paid_months if checked)
            $insts = Installment::query()->forUser($userId)->where('month', $currentMonth)->get();
            foreach ($insts as $inst) {
                $newPaid = $inst->paid_months;
                if ($inst->is_checked) {
                    $newPaid = min($inst->total_months, $inst->paid_months + 1);
                }

                // Copy the installment only if it's not fully paid in the previous month
                if ($inst->paid_months < $inst->total_months) {
                    Installment::create([
                        'user_id' => $userId,
                        'month' => $nextMonth,
                        'label' => $inst->label,
                        'monthly_payment' => $inst->monthly_payment,
                        'total_amount' => $inst->total_amount,
                        'total_months' => $inst->total_months,
                        'paid_months' => $newPaid,
                        'is_checked' => false,
                        'notes' => $inst->notes,
                    ]);
                }
            }
        }

        return redirect()->route('portfolio.budget.index', ['month' => $nextMonth])
            ->with('status', "เริ่มต้นงบประมาณเดือนใหม่ ($nextMonth) และเตรียมข้อมูลสำเร็จแล้ว");
    }

    private function authorizeIncome(Income $income): void
    {
        abort_unless($income->user_id === (int) auth()->id(), 403);
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
