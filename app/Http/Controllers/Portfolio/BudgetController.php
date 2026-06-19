<?php

namespace App\Http\Controllers\Portfolio;

use App\Http\Controllers\Controller;
use App\Models\Portfolio\BudgetItem;
use App\Models\Portfolio\Debt;
use App\Models\Portfolio\DebtPayment;
use App\Models\Portfolio\DebtPaymentLog;
use App\Models\Portfolio\Installment;
use App\Models\Portfolio\Subscription;
use App\Models\Portfolio\Income;
use App\Models\Portfolio\Holding;
use App\Models\Portfolio\Transaction;
use Illuminate\Http\Request;

class BudgetController extends Controller
{
    public function index(Request $request)
    {
        $userId = $this->resolvePortfolioUserId();

        // 1. Months for the dropdown = only months with real budget activity.
        //    DebtPayment entries are a pre-seeded schedule stretching years into
        //    the future — including them would flood the list with 100+ months.
        $allMonths = collect()
            ->merge(BudgetItem::query()->forUser($userId)->pluck('month'))
            ->merge(Installment::query()->forUser($userId)->pluck('month'))
            ->merge(Subscription::query()->forUser($userId)->pluck('month'))
            ->merge(Income::query()->forUser($userId)->pluck('month'))
            ->unique()
            ->sort()
            ->reverse()
            ->values();

        // 2. Always default to the current calendar month so the page opens
        //    on "now" regardless of how far debt schedules extend.
        $activeMonth = $request->query('month');
        if (!$activeMonth || !preg_match('/^\d{4}-\d{2}$/', $activeMonth)) {
            $activeMonth = date('Y-m');
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

        // 7. Fetch Variable Debts (persistent, not per-month) with their full payment schedule
        $debts = Debt::query()
            ->forUser($userId)
            ->with([
                'payments' => fn ($q) => $q->orderBy('month'),
                'payments.logs' => fn ($q) => $q->orderBy('paid_on', 'desc'),
            ])
            ->orderBy('id')
            ->get();

        // 8. Calculations
        $incomeTotal = (float) $incomes->sum('amount');
        $installmentsPaymentSum = (float) $installments->sum('monthly_payment');
        $subscriptionsPaymentSum = (float) $subscriptions->sum('monthly_payment');
        $debtPaymentsSum = (float) $debts->sum(
            fn ($d) => $this->debtMonthlyAmount($d, $activeMonth)
        );

        $fixedTotal = (float) $fixedExpensesList->sum('amount')
            + $installmentsPaymentSum
            + $subscriptionsPaymentSum
            + $debtPaymentsSum;
        $variableTotal = (float) $variableExpensesList->sum('amount');
        $savingsTotal = (float) $savingsList->sum('amount');

        $totalExpenses = $fixedTotal + $variableTotal + $savingsTotal;
        $remainingAmount = $incomeTotal - $totalExpenses;

        // Calculate actual amounts spent/saved
        $actualFixedBudgetItemSum = (float) $fixedExpensesList->sum(
            fn ($item) => $item->actual_amount !== null ? $item->actual_amount : ($item->is_checked ? $item->amount : 0)
        );
        $actualInstallmentsPaymentSum = (float) $installments->filter(fn($inst) => $inst->is_checked)->sum('monthly_payment');
        $actualSubscriptionsPaymentSum = (float) $subscriptions->filter(fn($sub) => $sub->is_checked)->sum('monthly_payment');
        $actualDebtPaymentsSum = (float) $debts->sum(
            fn ($d) => $this->debtMonthlyActual($d, $activeMonth)
        );

        $actualFixedTotal = $actualFixedBudgetItemSum + $actualInstallmentsPaymentSum + $actualSubscriptionsPaymentSum + $actualDebtPaymentsSum;

        $actualVariableTotal = (float) $variableExpensesList->sum(
            fn ($item) => $item->actual_amount !== null ? $item->actual_amount : ($item->is_checked ? $item->amount : 0)
        );

        $actualSavingsTotal = (float) $savingsList->sum(
            fn ($item) => $item->actual_amount !== null ? $item->actual_amount : ($item->is_checked ? $item->amount : 0)
        );

        $actualExpensesTotal = $actualFixedTotal + $actualVariableTotal + $actualSavingsTotal;
        $actualRemainingAmount = $incomeTotal - $actualExpensesTotal;

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
            'debts',
            'installmentsPaymentSum',
            'subscriptionsPaymentSum',
            'debtPaymentsSum',
            'fixedTotal',
            'variableTotal',
            'savingsTotal',
            'totalExpenses',
            'remainingAmount',
            'actualFixedTotal',
            'actualVariableTotal',
            'actualSavingsTotal',
            'actualExpensesTotal',
            'actualRemainingAmount',
            'actualFixedBudgetItemSum',
            'actualInstallmentsPaymentSum',
            'actualSubscriptionsPaymentSum',
            'actualDebtPaymentsSum'
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
        $data['user_id'] = $this->resolvePortfolioUserId();

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
            'actual_amount' => 'nullable|numeric|min:0',
            'month' => 'required|string|regex:/^\d{4}-\d{2}$/',
            'notes' => 'nullable|string|max:1000',
        ]);
        $data['user_id'] = $this->resolvePortfolioUserId();

        $item = BudgetItem::create($data);
        $this->syncSavingToHolding($item);

        return redirect()->route('portfolio.budget.index', ['month' => $data['month']])
            ->with('status', __('app.portfolio.budget.item_created'));
    }

    public function updateBudgetItem(Request $request, BudgetItem $item)
    {
        $this->authorizeItem($item);

        $data = $request->validate([
            'label' => 'required|string|max:120',
            'amount' => 'required|numeric|min:0',
            'actual_amount' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:1000',
        ]);

        $item->update($data);
        $this->syncSavingToHolding($item);

        return redirect()->route('portfolio.budget.index', ['month' => $item->month])
            ->with('status', __('app.portfolio.budget.item_updated'));
    }

    public function destroyBudgetItem(BudgetItem $item)
    {
        $this->authorizeItem($item);
        $month = $item->month;

        // Clean up transaction if any
        if ($item->category === BudgetItem::CATEGORY_SAVING && $item->is_checked) {
            $item->is_checked = false;
            $this->syncSavingToHolding($item);
        }

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
        
        $data['user_id'] = $this->resolvePortfolioUserId();

        Installment::create($data);

        return $this->redirectAfterAction($data['month'], __('app.portfolio.budget.installment_created'));
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

        return $this->redirectAfterAction($installment->month, __('app.portfolio.budget.installment_updated'));
    }

    public function destroyInstallment(Installment $installment)
    {
        $this->authorizeInstallment($installment);
        $month = $installment->month;
        $installment->delete();

        return $this->redirectAfterAction($month, __('app.portfolio.budget.installment_deleted'));
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
        $data['user_id'] = $this->resolvePortfolioUserId();

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
        } elseif ($type === 'debt-payment') {
            $model = DebtPayment::findOrFail($id);
            $this->authorizeDebtPayment($model);
        } else {
            abort(400);
        }

        $field = ($type === 'debt-payment') ? 'is_paid' : 'is_checked';
        $model->update([$field => !$model->$field]);

        if ($type === 'item') {
            $this->syncSavingToHolding($model);
        }

        if ($request->wantsJson()) {
            $value = ($type === 'debt-payment') ? $model->is_paid : $model->is_checked;
            return response()->json([
                'success' => true,
                'is_checked' => $value,
                'totals' => $this->getBudgetTotals($model->month)
            ]);
        }

        return redirect()->back();
    }

    // ── Variable Debts ─────────────────────────────────────────────

    public function storeDebt(Request $request)
    {
        $data = $request->validate([
            'label'        => 'required|string|max:120',
            'total_amount' => 'nullable|numeric|min:0',
            'notes'        => 'nullable|string|max:1000',
        ]);
        $data['user_id']     = $this->resolvePortfolioUserId();
        $data['total_amount'] = $data['total_amount'] ?? 0;

        Debt::create($data);

        $month = $request->input('month', date('Y-m'));
        return $this->redirectAfterAction($month, 'เพิ่มรายการหนี้เรียบร้อยแล้ว');
    }

    public function updateDebt(Request $request, Debt $debt)
    {
        $this->authorizeDebt($debt);

        $data = $request->validate([
            'label'        => 'required|string|max:120',
            'total_amount' => 'nullable|numeric|min:0',
            'notes'        => 'nullable|string|max:1000',
        ]);
        $data['total_amount'] = $data['total_amount'] ?? 0;

        $debt->update($data);

        $month = $request->input('month', date('Y-m'));
        return $this->redirectAfterAction($month, 'อัปเดตข้อมูลหนี้เรียบร้อยแล้ว');
    }

    public function destroyDebt(Debt $debt)
    {
        $this->authorizeDebt($debt);
        $month = request()->input('month', date('Y-m'));
        $debt->delete();

        return $this->redirectAfterAction($month, 'ลบรายการหนี้เรียบร้อยแล้ว');
    }

    public function storeDebtPayment(Request $request)
    {
        $data = $request->validate([
            'debt_id' => 'required|integer|exists:portfolio_debts,id',
            'month'   => 'required|string|regex:/^\d{4}-\d{2}$/',
            'amount'  => 'required|numeric|min:0',
            'notes'   => 'nullable|string|max:1000',
        ]);

        $debt = Debt::findOrFail($data['debt_id']);
        $this->authorizeDebt($debt);

        // Prevent duplicate payment for same debt+month
        DebtPayment::updateOrCreate(
            ['debt_id' => $data['debt_id'], 'user_id' => $this->resolvePortfolioUserId(), 'month' => $data['month']],
            ['amount' => $data['amount'], 'notes' => $data['notes'] ?? null]
        );

        $month = $request->input('redirect_month', $data['month']);
        return $this->redirectAfterAction($month, 'เพิ่มงวดชำระเรียบร้อยแล้ว');
    }

    public function updateDebtPayment(Request $request, DebtPayment $payment)
    {
        $this->authorizeDebtPayment($payment);

        $data = $request->validate([
            'amount'    => 'required|numeric|min:0',
            'notes'     => 'nullable|string|max:1000',
            'due_month' => 'nullable|string|regex:/^\d{4}-\d{2}$/',
        ]);

        $payment->amount = $data['amount'];
        $payment->notes  = $data['notes'] ?? null;
        if (!empty($data['due_month'])) {
            $payment->month = $data['due_month']; // กยศ: editable งวด due month
        }
        // กยศ-style งวด (tracked via logged payments) derive is_paid from how
        // much has been logged, so re-evaluate after the target changes. Fixed
        // schedule rows have paid_amount = 0 and keep their toggled flag.
        if ((float) $payment->paid_amount > 0) {
            $payment->is_paid = (float) $payment->paid_amount >= (float) $payment->amount;
        }
        $payment->save();

        $month = $request->input('month', $payment->month);
        return $this->redirectAfterAction($month, 'อัปเดตงวดชำระเรียบร้อยแล้ว');
    }

    public function destroyDebtPayment(DebtPayment $payment)
    {
        $this->authorizeDebtPayment($payment);
        $month = request()->input('month', $payment->month);
        $payment->delete();

        return $this->redirectAfterAction($month, 'ลบงวดชำระเรียบร้อยแล้ว');
    }

    /** Record one real payment toward a per-งวด target (กยศ-style). */
    public function storeDebtPaymentLog(Request $request)
    {
        $data = $request->validate([
            'debt_payment_id' => 'required|integer|exists:portfolio_debt_payments,id',
            'paid_on'         => 'required|date',
            'amount'          => 'required|numeric|min:0.01',
            'reference'       => 'nullable|string|max:60',
            'notes'           => 'nullable|string|max:1000',
        ]);

        $payment = DebtPayment::findOrFail($data['debt_payment_id']);
        $this->authorizeDebtPayment($payment);

        DebtPaymentLog::create([
            'debt_payment_id' => $payment->id,
            'user_id'         => $this->resolvePortfolioUserId(),
            'paid_on'         => $data['paid_on'],
            'amount'          => $data['amount'],
            'reference'       => $data['reference'] ?? null,
            'notes'           => $data['notes'] ?? null,
        ]);

        $this->recalcDebtPayment($payment);

        $month = $request->input('redirect_month', date('Y-m'));
        return $this->redirectAfterAction($month, 'บันทึกการจ่ายเรียบร้อยแล้ว');
    }

    public function destroyDebtPaymentLog(DebtPaymentLog $log)
    {
        abort_unless($log->user_id === $this->resolvePortfolioUserId(), 403);

        $payment = $log->payment;
        $month   = request()->input('redirect_month', date('Y-m'));
        $log->delete();

        if ($payment) {
            $this->recalcDebtPayment($payment);
        }

        return $this->redirectAfterAction($month, 'ลบรายการจ่ายเรียบร้อยแล้ว');
    }

    public function resetMonth(Request $request)
    {
        $userId = $this->resolvePortfolioUserId();
        $currentMonth = $request->input('current_month');
        if (!$currentMonth || !preg_match('/^\d{4}-\d{2}$/', $currentMonth)) {
            $currentMonth = date('Y-m');
        }

        // Calculate next month
        $time = strtotime($currentMonth . '-01');
        $nextMonth = date('Y-m', strtotime('+1 month', $time));

        // 1. Copy Income sources
        $incomes = Income::query()->forUser($userId)->where('month', $currentMonth)->get();
        foreach ($incomes as $inc) {
            $existsInNext = Income::query()
                ->forUser($userId)
                ->where('month', $nextMonth)
                ->where('label', $inc->label)
                ->exists();
            if (!$existsInNext) {
                Income::create([
                    'user_id' => $userId,
                    'month' => $nextMonth,
                    'label' => $inc->label,
                    'amount' => $inc->amount,
                    'notes' => $inc->notes,
                ]);
            }
        }

        // 2. Copy Budget Items (Fixed, Variable, Savings)
        $items = BudgetItem::query()->forUser($userId)->where('month', $currentMonth)->get();
        foreach ($items as $item) {
            $existsInNext = BudgetItem::query()
                ->forUser($userId)
                ->where('month', $nextMonth)
                ->where('category', $item->category)
                ->where('label', $item->label)
                ->exists();
            if (!$existsInNext) {
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
        }

        // 3. Copy Subscriptions
        $subs = Subscription::query()->forUser($userId)->where('month', $currentMonth)->get();
        foreach ($subs as $sub) {
            $existsInNext = Subscription::query()
                ->forUser($userId)
                ->where('month', $nextMonth)
                ->where('label', $sub->label)
                ->exists();
            if (!$existsInNext) {
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
        }

        // 4. Copy Installments (incrementing paid_months if checked)
        $insts = Installment::query()->forUser($userId)->where('month', $currentMonth)->get();
        foreach ($insts as $inst) {
            $existsInNext = Installment::query()
                ->forUser($userId)
                ->where('month', $nextMonth)
                ->where('label', $inst->label)
                ->exists();
            if (!$existsInNext && $inst->paid_months < $inst->total_months) {
                $newPaid = $inst->paid_months;
                if ($inst->is_checked) {
                    $newPaid = min($inst->total_months, $inst->paid_months + 1);
                }
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

        return redirect()->route('portfolio.budget.index', ['month' => $nextMonth])
            ->with('status', "เริ่มต้นงบประมาณเดือนใหม่ ($nextMonth) และเตรียมข้อมูลสำเร็จแล้ว");
    }

    private function authorizeIncome(Income $income): void
    {
        abort_unless($income->user_id === $this->resolvePortfolioUserId(), 403);
    }

    private function authorizeItem(BudgetItem $item): void
    {
        abort_unless($item->user_id === $this->resolvePortfolioUserId(), 403);
    }

    private function authorizeInstallment(Installment $installment): void
    {
        abort_unless($installment->user_id === $this->resolvePortfolioUserId(), 403);
    }

    private function authorizeSubscription(Subscription $subscription): void
    {
        abort_unless($subscription->user_id === $this->resolvePortfolioUserId(), 403);
    }

    private function authorizeDebt(Debt $debt): void
    {
        abort_unless($debt->user_id === $this->resolvePortfolioUserId(), 403);
    }

    private function authorizeDebtPayment(DebtPayment $payment): void
    {
        abort_unless($payment->user_id === $this->resolvePortfolioUserId(), 403);
    }

    /**
     * กยศ-style debts are paid flexibly via dated logs; every other debt uses
     * its fixed monthly schedule row. Returns the cash for the given month.
     */
    private function debtMonthlyAmount(Debt $debt, string $month): float
    {
        if (str_contains($debt->label, 'กยศ')) {
            return (float) $debt->payments
                ->flatMap->logs
                ->filter(fn ($log) => $log->paid_on->format('Y-m') === $month)
                ->sum('amount');
        }

        return (float) ($debt->payments->firstWhere('month', $month)?->amount ?? 0);
    }

    /** Actual cash out this month (กยศ logs are already real payments). */
    private function debtMonthlyActual(Debt $debt, string $month): float
    {
        if (str_contains($debt->label, 'กยศ')) {
            return $this->debtMonthlyAmount($debt, $month);
        }

        $payment = $debt->payments->firstWhere('month', $month);
        return ($payment && $payment->is_paid) ? (float) $payment->amount : 0.0;
    }

    /** Re-sum a งวด's logs into paid_amount / is_paid after a log changes. */
    private function recalcDebtPayment(DebtPayment $payment): void
    {
        $paid = (float) $payment->logs()->sum('amount');
        $payment->update([
            'paid_amount' => $paid,
            'is_paid'     => $paid >= (float) $payment->amount,
        ]);
    }

    private function getBudgetTotals(string $activeMonth): array
    {
        $userId = $this->resolvePortfolioUserId();

        // 1. Incomes
        $incomes = Income::query()
            ->forUser($userId)
            ->where('month', $activeMonth)
            ->orderBy('id')
            ->get();

        // 2. Budget Items
        $items = BudgetItem::query()
            ->forUser($userId)
            ->where('month', $activeMonth)
            ->get();

        $fixedExpensesList = $items->where('category', BudgetItem::CATEGORY_FIXED);
        $variableExpensesList = $items->where('category', BudgetItem::CATEGORY_VARIABLE);
        $savingsList = $items->where('category', BudgetItem::CATEGORY_SAVING);

        // 3. Installments
        $installments = Installment::query()
            ->forUser($userId)
            ->where('month', $activeMonth)
            ->orderBy('id')
            ->get();

        // 4. Subscriptions
        $subscriptions = Subscription::query()
            ->forUser($userId)
            ->where('month', $activeMonth)
            ->orderBy('id')
            ->get();

        // 5. Debts
        $debts = Debt::query()
            ->forUser($userId)
            ->with([
                'payments' => fn ($q) => $q->orderBy('month'),
                'payments.logs' => fn ($q) => $q->orderBy('paid_on', 'desc'),
            ])
            ->orderBy('id')
            ->get();

        $incomeTotal = (float) $incomes->sum('amount');
        $installmentsPaymentSum = (float) $installments->sum('monthly_payment');
        $subscriptionsPaymentSum = (float) $subscriptions->sum('monthly_payment');
        $debtPaymentsSum = (float) $debts->sum(
            fn ($d) => $this->debtMonthlyAmount($d, $activeMonth)
        );

        $fixedTotal = (float) $fixedExpensesList->sum('amount')
            + $installmentsPaymentSum
            + $subscriptionsPaymentSum
            + $debtPaymentsSum;
        $variableTotal = (float) $variableExpensesList->sum('amount');
        $savingsTotal = (float) $savingsList->sum('amount');

        $totalExpenses = $fixedTotal + $variableTotal + $savingsTotal;
        $remainingAmount = $incomeTotal - $totalExpenses;

        // Calculate actual amounts spent/saved
        $actualFixedBudgetItemSum = (float) $fixedExpensesList->sum(
            fn ($item) => $item->actual_amount !== null ? $item->actual_amount : ($item->is_checked ? $item->amount : 0)
        );
        $actualInstallmentsPaymentSum = (float) $installments->filter(fn($inst) => $inst->is_checked)->sum('monthly_payment');
        $actualSubscriptionsPaymentSum = (float) $subscriptions->filter(fn($sub) => $sub->is_checked)->sum('monthly_payment');
        $actualDebtPaymentsSum = (float) $debts->sum(
            fn ($d) => $this->debtMonthlyActual($d, $activeMonth)
        );

        $actualFixedTotal = $actualFixedBudgetItemSum + $actualInstallmentsPaymentSum + $actualSubscriptionsPaymentSum + $actualDebtPaymentsSum;

        $actualVariableTotal = (float) $variableExpensesList->sum(
            fn ($item) => $item->actual_amount !== null ? $item->actual_amount : ($item->is_checked ? $item->amount : 0)
        );

        $actualSavingsTotal = (float) $savingsList->sum(
            fn ($item) => $item->actual_amount !== null ? $item->actual_amount : ($item->is_checked ? $item->amount : 0)
        );

        $actualExpensesTotal = $actualFixedTotal + $actualVariableTotal + $actualSavingsTotal;
        $actualRemainingAmount = $incomeTotal - $actualExpensesTotal;

        return [
            'incomeTotal' => $incomeTotal,
            'fixedTotal' => $fixedTotal,
            'variableTotal' => $variableTotal,
            'savingsTotal' => $savingsTotal,
            'totalExpenses' => $totalExpenses,
            'remainingAmount' => $remainingAmount,
            'actualFixedTotal' => $actualFixedTotal,
            'actualVariableTotal' => $actualVariableTotal,
            'actualSavingsTotal' => $actualSavingsTotal,
            'actualExpensesTotal' => $actualExpensesTotal,
            'actualRemainingAmount' => $actualRemainingAmount,
            'plannedFixedItem' => (float) $fixedExpensesList->sum('amount'),
            'plannedInstallments' => $installmentsPaymentSum,
            'plannedSubscriptions' => $subscriptionsPaymentSum,
            'plannedDebts' => $debtPaymentsSum,
            'actualFixedItem' => $actualFixedBudgetItemSum,
            'actualInstallments' => $actualInstallmentsPaymentSum,
            'actualSubscriptions' => $actualSubscriptionsPaymentSum,
            'actualDebts' => $actualDebtPaymentsSum,
        ];
    }

    private function syncSavingToHolding(BudgetItem $item): void
    {
        // Only run for savings category
        if ($item->category !== BudgetItem::CATEGORY_SAVING) {
            return;
        }

        $userId = $item->user_id;
        $prices = app(\App\Services\Portfolio\PriceFetcher::class);

        // 1. Find and delete any existing auto-created transaction for this budget item
        $oldTransactions = Transaction::query()
            ->whereHas('holding', fn ($q) => $q->where('user_id', $userId))
            ->where('notes', 'like', "%(ID: {$item->id})%")
            ->get();

        $affectedHoldingIds = [];
        foreach ($oldTransactions as $t) {
            $affectedHoldingIds[] = $t->holding_id;
            $t->delete();
        }

        // 2. If it is currently checked, find a matching holding and create a new transaction
        if ($item->is_checked) {
            $matchingHolding = Holding::query()
                ->forUser($userId)
                ->whereIn('kind', [Holding::KIND_DEPOSIT, Holding::KIND_CASH])
                ->whereRaw('LOWER(TRIM(label)) = ?', [strtolower(trim($item->label))])
                ->first();

            if ($matchingHolding) {
                $amount = $item->actual_amount !== null ? (float) $item->actual_amount : (float) $item->amount;

                $matchingHolding->transactions()->create([
                    'type'             => 'in',
                    'amount'           => $amount,
                    'transaction_date' => $item->month . '-01',
                    'notes'            => "[รายจ่ายเงินออมอัตโนมัติ] จากแผนงบประมาณเดือน {$item->month} (ID: {$item->id})",
                ]);

                $affectedHoldingIds[] = $matchingHolding->id;
            }
        }

        // 3. Recalculate all affected holdings
        $uniqueHoldingIds = array_unique($affectedHoldingIds);
        foreach ($uniqueHoldingIds as $holdingId) {
            $holding = Holding::find($holdingId);
            if ($holding) {
                $holding->recalculateFromTransactions($prices);
            }
        }
    }

    private function redirectAfterAction(string $month, string $msg)
    {
        if (request()->input('redirect_to') === 'debts') {
            return redirect()->route('portfolio.debts.index')->with('status', $msg);
        }
        return redirect()->route('portfolio.budget.index', ['month' => $month])->with('status', $msg);
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
