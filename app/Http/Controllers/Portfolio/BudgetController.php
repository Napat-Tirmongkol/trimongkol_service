<?php

namespace App\Http\Controllers\Portfolio;

use App\Http\Controllers\Controller;
use App\Models\Portfolio\BudgetItem;
use App\Models\Portfolio\Debt;
use App\Models\Portfolio\DebtPayment;
use App\Models\Portfolio\DebtPaymentLog;
use App\Models\Portfolio\Installment;
use App\Models\Portfolio\LedgerEntry;
use App\Models\Portfolio\Subscription;
use App\Models\Portfolio\Income;
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
            ->with('ledgerEntries')
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
        // Footer "รวมผ่อนต่อเดือน" excludes กยศ (logs are both planned and actual,
        // making the ratio meaningless for tracking payment progress).
        $nonKoyosoDebtPaymentsSum = (float) $debts
            ->filter(fn ($d) => !str_contains($d->label, 'กยศ'))
            ->sum(fn ($d) => $this->debtMonthlyAmount($d, $activeMonth));

        $fixedTotal = (float) $fixedExpensesList->sum('amount')
            + $installmentsPaymentSum
            + $subscriptionsPaymentSum
            + $debtPaymentsSum;
        $variableTotal = (float) $variableExpensesList->sum('amount');
        $savingsTotal = (float) $savingsList->sum('amount');

        $totalExpenses = $fixedTotal + $variableTotal + $savingsTotal;
        $remainingAmount = $incomeTotal - $totalExpenses;
        $unallocatedAmount = $incomeTotal - $totalExpenses;

        // Helper to determine the actual amount for a budget item
        $getItemActual = fn ($item) => $item->actual_amount !== null 
            ? $item->actual_amount 
            : ($item->ledgerEntries->sum('amount') > 0 ? $item->ledgerEntries->sum('amount') : ($item->is_checked ? $item->amount : 0));

        // Ledger-driven actuals (this month) keyed by linked item label.
        $installmentLedger  = $this->ledgerActualByLabel($userId, $activeMonth, 'installment_id', 'portfolio_installments');
        $subscriptionLedger = $this->ledgerActualByLabel($userId, $activeMonth, 'subscription_id', 'portfolio_subscriptions');
        $debtLedger         = $this->ledgerActualByLabel($userId, $activeMonth, 'debt_id', 'portfolio_debts');

        // Calculate actual amounts spent/saved
        $actualFixedBudgetItemSum = (float) $fixedExpensesList->sum($getItemActual);
        $actualInstallmentsPaymentSum = (float) $installments->sum(
            fn ($inst) => $this->installmentActual($inst, $installmentLedger)
        );
        $actualSubscriptionsPaymentSum = (float) $subscriptions->sum(
            fn ($sub) => $this->subscriptionActual($sub, $subscriptionLedger)
        );
        $actualDebtPaymentsSum = (float) $debts->sum(
            fn ($d) => $this->debtActual($d, $activeMonth, $debtLedger)
        );
        $actualNonKoyosoDebtPaymentsSum = (float) $debts
            ->filter(fn ($d) => !str_contains($d->label, 'กยศ'))
            ->sum(fn ($d) => $this->debtActual($d, $activeMonth, $debtLedger));

        $actualFixedTotal = $actualFixedBudgetItemSum + $actualInstallmentsPaymentSum + $actualSubscriptionsPaymentSum + $actualDebtPaymentsSum;

        $actualVariableTotal = (float) $variableExpensesList->sum($getItemActual);

        $actualSavingsTotal = (float) $savingsList->sum($getItemActual);

        $actualExpensesTotal = $actualFixedTotal + $actualVariableTotal + $actualSavingsTotal;
        $actualRemainingAmount = $incomeTotal - $actualExpensesTotal;

        // Calculate Emergency Fund
        $emergencyFundGoal = $fixedTotal * 6;
        $emergencyFundTotal = (float) BudgetItem::query()
            ->forUser($userId)
            ->where('category', BudgetItem::CATEGORY_SAVING)
            ->where('label', 'like', '%สำรองฉุกเฉิน%')
            ->get()
            ->sum($getItemActual);

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
            'unallocatedAmount',
            'getItemActual',
            'actualFixedTotal',
            'actualVariableTotal',
            'actualSavingsTotal',
            'actualExpensesTotal',
            'actualRemainingAmount',
            'actualFixedBudgetItemSum',
            'actualInstallmentsPaymentSum',
            'actualSubscriptionsPaymentSum',
            'actualDebtPaymentsSum',
            'nonKoyosoDebtPaymentsSum',
            'actualNonKoyosoDebtPaymentsSum',
            'emergencyFundGoal',
            'emergencyFundTotal',
            'installmentLedger',
            'subscriptionLedger',
            'debtLedger'
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

    public function destroyInstallment(Request $request, Installment $installment)
    {
        $this->authorizeInstallment($installment);
        $month = $installment->month;

        if ($request->input('redirect_to') === 'debts') {
            // The /debts page collapses an installment to one row per label
            // (newest month shown). Installments are stored per-month, so a
            // single delete leaves older months behind and the row reappears.
            // Deleting the whole label removes the installment everywhere.
            Installment::forUser($installment->user_id)
                ->where('label', $installment->label)
                ->delete();
        } else {
            $installment->delete();
        }

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

        $debt = Debt::create($data);

        // Auto-generate payments if months and monthly payment are provided
        $tm = $request->input('total_months');
        $mp = $request->input('monthly_payment');
        
        if ($tm && $mp && $tm > 0 && $mp > 0) {
            $currentMonth = $request->input('month', date('Y-m'));
            // Use the first day of the selected month as base
            $baseDate = $currentMonth . '-01';
            
            for ($i = 0; $i < $tm; $i++) {
                $monthStr = date('Y-m', strtotime("+$i months", strtotime($baseDate)));
                \App\Models\Portfolio\DebtPayment::create([
                    'debt_id' => $debt->id,
                    'user_id' => $debt->user_id,
                    'month'   => $monthStr,
                    'amount'  => $mp,
                    'paid_amount' => 0,
                ]);
            }
        }

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
        $items = BudgetItem::query()->with('ledgerEntries')->forUser($userId)->where('month', $currentMonth)->get();
        $rolloverUnspent = $request->boolean('rollover_unspent');

        foreach ($items as $item) {
            $existsInNext = BudgetItem::query()
                ->forUser($userId)
                ->where('month', $nextMonth)
                ->where('category', $item->category)
                ->where('label', $item->label)
                ->exists();
            if (!$existsInNext) {
                $newAmount = $item->amount;
                if ($rolloverUnspent && in_array($item->category, [BudgetItem::CATEGORY_VARIABLE, BudgetItem::CATEGORY_SAVING])) {
                    $actual = $item->actual_amount !== null 
                        ? $item->actual_amount 
                        : ($item->ledgerEntries->sum('amount') > 0 ? $item->ledgerEntries->sum('amount') : ($item->is_checked ? $item->amount : 0));
                    $unspent = max(0, $item->amount - $actual);
                    $newAmount += $unspent;
                }

                BudgetItem::create([
                    'user_id' => $userId,
                    'month' => $nextMonth,
                    'category' => $item->category,
                    'label' => $item->label,
                    'amount' => $newAmount,
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

    /**
     * Sum of this month's ledger EXPENSES, keyed by the linked item's label.
     * Lets installment / subscription / debt "ใช้จริง" reflect real payments
     * recorded in the ledger — mirroring how BudgetItem pulls actual from its
     * linked ledger entries. Keyed by label (not record id) so it matches the
     * active month's installment/subscription record regardless of which
     * per-month row the ledger entry was originally linked to.
     */
    private function ledgerActualByLabel(int $userId, string $month, string $fkColumn, string $relatedTable)
    {
        return LedgerEntry::query()
            ->where('portfolio_ledger.user_id', $userId)
            ->where('portfolio_ledger.month', $month)
            ->where('portfolio_ledger.type', LedgerEntry::TYPE_EXPENSE)
            ->whereNotNull("portfolio_ledger.$fkColumn")
            ->join($relatedTable, "portfolio_ledger.$fkColumn", '=', "$relatedTable.id")
            ->groupBy("$relatedTable.label")
            ->selectRaw("$relatedTable.label as label, SUM(portfolio_ledger.amount) as total")
            ->pluck('total', 'label');
    }

    /** Installment actual: ledger payment if any, else the planned amount when ticked. */
    private function installmentActual(Installment $inst, $ledgerMap): float
    {
        $ledger = (float) ($ledgerMap[$inst->label] ?? 0);
        return $ledger > 0 ? $ledger : ($inst->is_checked ? (float) $inst->monthly_payment : 0.0);
    }

    /** Subscription actual: ledger payment if any, else the planned amount when ticked. */
    private function subscriptionActual(Subscription $sub, $ledgerMap): float
    {
        $ledger = (float) ($ledgerMap[$sub->label] ?? 0);
        return $ledger > 0 ? $ledger : ($sub->is_checked ? (float) $sub->monthly_payment : 0.0);
    }

    /**
     * Debt actual: a ledger payment takes precedence over the debt's own payment
     * logs / is_paid flag, so a payment recorded in the ledger is never double
     * counted with one recorded through the debt card.
     */
    private function debtActual(Debt $debt, string $month, $ledgerMap): float
    {
        $ledger = (float) ($ledgerMap[$debt->label] ?? 0);
        return $ledger > 0 ? $ledger : $this->debtMonthlyActual($debt, $month);
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
            ->with('ledgerEntries')
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
        $unallocatedAmount = $incomeTotal - $totalExpenses;

        // Helper to determine the actual amount for a budget item
        $getItemActual = fn ($item) => $item->actual_amount !== null 
            ? $item->actual_amount 
            : ($item->ledgerEntries->sum('amount') > 0 ? $item->ledgerEntries->sum('amount') : ($item->is_checked ? $item->amount : 0));

        // Ledger-driven actuals (this month) keyed by linked item label.
        $installmentLedger  = $this->ledgerActualByLabel($userId, $activeMonth, 'installment_id', 'portfolio_installments');
        $subscriptionLedger = $this->ledgerActualByLabel($userId, $activeMonth, 'subscription_id', 'portfolio_subscriptions');
        $debtLedger         = $this->ledgerActualByLabel($userId, $activeMonth, 'debt_id', 'portfolio_debts');

        // Calculate actual amounts spent/saved
        $actualFixedBudgetItemSum = (float) $fixedExpensesList->sum($getItemActual);
        $actualInstallmentsPaymentSum = (float) $installments->sum(
            fn ($inst) => $this->installmentActual($inst, $installmentLedger)
        );
        $actualSubscriptionsPaymentSum = (float) $subscriptions->sum(
            fn ($sub) => $this->subscriptionActual($sub, $subscriptionLedger)
        );
        $actualDebtPaymentsSum = (float) $debts->sum(
            fn ($d) => $this->debtActual($d, $activeMonth, $debtLedger)
        );
        $nonKoyosoDebtPaymentsSum = (float) $debts
            ->filter(fn ($d) => !str_contains($d->label, 'กยศ'))
            ->sum(fn ($d) => $this->debtMonthlyAmount($d, $activeMonth));
        $actualNonKoyosoDebtPaymentsSum = (float) $debts
            ->filter(fn ($d) => !str_contains($d->label, 'กยศ'))
            ->sum(fn ($d) => $this->debtActual($d, $activeMonth, $debtLedger));

        // Calculate Emergency Fund
        $emergencyFundGoal = $fixedTotal * 6;
        $emergencyFundTotal = (float) BudgetItem::query()
            ->forUser($userId)
            ->where('category', BudgetItem::CATEGORY_SAVING)
            ->where('label', 'like', '%สำรองฉุกเฉิน%')
            ->get()
            ->sum($getItemActual);

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
            'unallocatedAmount' => $unallocatedAmount,
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
            'plannedNonKoyosoDebts' => $nonKoyosoDebtPaymentsSum,
            'actualNonKoyosoDebts' => $actualNonKoyosoDebtPaymentsSum,
            'emergencyFundGoal' => $emergencyFundGoal,
            'emergencyFundTotal' => $emergencyFundTotal,
        ];
    }

    private function syncSavingToHolding(BudgetItem $item): void
    {
        // Shared with LedgerController so a saving deposit recorded in the ledger
        // flows into the matching holding the same way a ticked plan item does.
        app(\App\Services\Portfolio\SavingHoldingSync::class)->sync($item);
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
