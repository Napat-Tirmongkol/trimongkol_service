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

        // Always default to the current calendar month so the page opens
        // on "now" regardless of how far debt schedules extend.
        $activeMonth = $request->query('month');
        if (!$activeMonth || !preg_match('/^\d{4}-\d{2}$/', $activeMonth)) {
            $activeMonth = date('Y-m');
        }

        // Auto-seed the verified Nov 2026 financial plan when 2026-11 is empty
        // or when explicitly requested via ?sync_plan=1.
        if ($request->boolean('sync_plan')) {
            $this->seedVerifiedNovember2026Plan($userId, $activeMonth);
            return redirect()->route('portfolio.budget.index', ['month' => $activeMonth])
                ->with('status', "ซิงก์ข้อมูลแผนการเงินจริง (สลิปเงินเดือน 29,911 ฿ + หอพัก 7,292 ฿ + หนี้สิน + Subscriptions) สำหรับเดือน {$activeMonth} เรียบร้อยแล้ว");
        }
        if (
            $activeMonth === '2026-11'
            && Income::query()->forUser($userId)->where('month', '2026-11')->doesntExist()
            && BudgetItem::query()->forUser($userId)->where('month', '2026-11')->doesntExist()
            && Installment::query()->forUser($userId)->where('month', '2026-11')->doesntExist()
        ) {
            $this->seedVerifiedNovember2026Plan($userId, '2026-11');
        }

        // Migrate labels to verified specific names
        Debt::query()->forUser($userId)->where('label', 'ใบแจ้งยอดล่วงหน้า (ครบกำหนดทุกวันที่ 1)')->update([
            'label' => 'TikTok PayLater (ครบกำหนดทุกวันที่ 1)'
        ]);
        Installment::query()->forUser($userId)->where('label', 'สินเชื่อ Tiktok')->update([
            'label' => 'สินเชื่อเงินสด TikTok'
        ]);

        // 1. Months for the dropdown = real budget activity + past 24 months + next 2 months
        $recentMonths = collect();
        $baseDate = \Carbon\Carbon::now()->startOfMonth();
        for ($i = -2; $i <= 24; $i++) {
            $recentMonths->push($baseDate->copy()->subMonths($i)->format('Y-m'));
        }
        $recentMonths->push($activeMonth);

        $allMonths = collect()
            ->merge($recentMonths)
            ->merge(BudgetItem::query()->forUser($userId)->pluck('month'))
            ->merge(Installment::query()->forUser($userId)->pluck('month'))
            ->merge(Subscription::query()->forUser($userId)->pluck('month'))
            ->merge(Income::query()->forUser($userId)->pluck('month'))
            ->unique()
            ->sort()
            ->reverse()
            ->values();

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

    public function storePayslip(Request $request)
    {
        $userId = $this->resolvePortfolioUserId();

        $data = $request->validate([
            'month'           => 'required|string|regex:/^\d{4}-\d{2}$/',
            'company'         => 'required|string|max:120',
            'base_salary'     => 'required|numeric|min:0',
            'ot'              => 'nullable|numeric|min:0',
            'bonus'           => 'nullable|numeric|min:0',
            'social_security' => 'nullable|numeric|min:0',
            'tax'             => 'nullable|numeric|min:0',
            'other_deduct'    => 'nullable|numeric|min:0',
            'notes'           => 'nullable|string|max:1000',
        ]);

        $baseSalary      = (float) $data['base_salary'];
        $ot              = (float) ($data['ot'] ?? 0);
        $bonus           = (float) ($data['bonus'] ?? 0);
        $socialSecurity  = (float) ($data['social_security'] ?? 0);
        $tax             = (float) ($data['tax'] ?? 0);
        $otherDeduct     = (float) ($data['other_deduct'] ?? 0);

        $grossIncome     = round($baseSalary + $ot + $bonus, 2);
        $totalDeductions = round($socialSecurity + $tax + $otherDeduct, 2);
        $netPay          = round(max(0, $grossIncome - $totalDeductions), 2);

        $payslipData = [
            'is_payslip'       => true,
            'company'          => trim($data['company']),
            'base_salary'      => $baseSalary,
            'ot'               => $ot,
            'bonus'            => $bonus,
            'gross_income'     => $grossIncome,
            'social_security'  => $socialSecurity,
            'tax'              => $tax,
            'other_deduct'     => $otherDeduct,
            'total_deductions' => $totalDeductions,
            'net_pay'          => $netPay,
            'custom_notes'     => $data['notes'] ?? '',
        ];

        $companyName = trim($data['company']);
        $label = "เงินเดือนประจำสุทธิ ({$companyName})";

        // Find existing salary income for this user & month (or create new)
        $income = Income::query()
            ->forUser($userId)
            ->where('month', $data['month'])
            ->where(function ($q) {
                $q->where('label', 'LIKE', 'เงินเดือน%')
                  ->orWhere('notes', 'LIKE', '%"is_payslip":true%');
            })
            ->first();

        if ($income) {
            $income->update([
                'label'  => $label,
                'amount' => $netPay,
                'notes'  => json_encode($payslipData, JSON_UNESCAPED_UNICODE),
            ]);
        } else {
            Income::create([
                'user_id' => $userId,
                'month'   => $data['month'],
                'label'   => $label,
                'amount'  => $netPay,
                'notes'   => json_encode($payslipData, JSON_UNESCAPED_UNICODE),
            ]);
        }

        // Clean up standalone OT row if OT was merged into this payslip to prevent double counting
        if ($ot > 0) {
            Income::query()
                ->forUser($userId)
                ->where('month', $data['month'])
                ->where(function ($q) {
                    $q->where('label', 'ค่าล่วงเวลา (OT)')
                      ->orWhere('label', 'ค่าล่วงเวลา')
                      ->orWhere('label', 'OT');
                })
                ->delete();
        }

        return redirect()->route('portfolio.budget.index', ['month' => $data['month']])
            ->with('status', "บันทึกสลิปเงินเดือนงวด {$data['month']} เรียบร้อยแล้ว (สุทธิ ฿" . number_format($netPay, 2) . ")");
    }

    public function copyMonth(Request $request)
    {
        $userId = $this->resolvePortfolioUserId();
        $targetMonth = $request->input('target_month');
        $sourceMonth = $request->input('source_month');

        if (!$targetMonth || !preg_match('/^\d{4}-\d{2}$/', $targetMonth)) {
            return redirect()->back()->with('error', 'ระบุเดือนปลายทางไม่ถูกต้อง');
        }

        if (!$sourceMonth) {
            $sourceMonth = BudgetItem::query()
                ->forUser($userId)
                ->where('month', '!=', $targetMonth)
                ->orderBy('month', 'desc')
                ->value('month') ?? '2026-11';
        }

        // Copy Incomes
        $sourceIncomes = Income::query()->forUser($userId)->where('month', $sourceMonth)->get();
        foreach ($sourceIncomes as $inc) {
            Income::firstOrCreate(
                ['user_id' => $userId, 'month' => $targetMonth, 'label' => $inc->label],
                ['amount' => $inc->amount, 'notes' => $inc->notes]
            );
        }

        // Copy BudgetItems
        $sourceItems = BudgetItem::query()->forUser($userId)->where('month', $sourceMonth)->get();
        foreach ($sourceItems as $item) {
            BudgetItem::firstOrCreate(
                ['user_id' => $userId, 'month' => $targetMonth, 'category' => $item->category, 'label' => $item->label],
                [
                    'amount'     => $item->amount,
                    'is_checked' => false,
                    'sort_order' => $item->sort_order,
                    'notes'      => $item->notes,
                ]
            );
        }

        // Copy Subscriptions
        $sourceSubs = Subscription::query()->forUser($userId)->where('month', $sourceMonth)->get();
        foreach ($sourceSubs as $sub) {
            Subscription::firstOrCreate(
                ['user_id' => $userId, 'month' => $targetMonth, 'label' => $sub->label],
                [
                    'monthly_payment' => $sub->monthly_payment,
                    'billing_day'     => $sub->billing_day,
                    'is_checked'      => false,
                    'notes'           => $sub->notes,
                ]
            );
        }

        // Copy Installments
        $sourceInst = Installment::query()->forUser($userId)->where('month', $sourceMonth)->get();
        foreach ($sourceInst as $inst) {
            Installment::firstOrCreate(
                ['user_id' => $userId, 'month' => $targetMonth, 'label' => $inst->label],
                [
                    'monthly_payment' => $inst->monthly_payment,
                    'total_amount'    => $inst->total_amount,
                    'total_months'    => $inst->total_months,
                    'paid_months'     => max(0, $inst->paid_months - 1),
                    'is_checked'      => false,
                    'notes'           => $inst->notes,
                ]
            );
        }

        return redirect()->route('portfolio.budget.index', ['month' => $targetMonth])
            ->with('status', "คัดลอกโครงร่างงบจากงวด {$sourceMonth} มายังงวด {$targetMonth} เรียบร้อยแล้ว");
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

        // Support one-click +1 / -1 paid_months step from /portfolio/debts
        if ($request->filled('step_paid')) {
            $step = (int) $request->input('step_paid');
            $newPaid = max(0, min((int) $installment->total_months, (int) $installment->paid_months + $step));
            $remMonths = (int) $installment->total_months - $newPaid;
            $remAmount = max(0.0, round((float) $installment->total_amount - ((float) $installment->monthly_payment * $newPaid), 2));

            $notes = $installment->notes;
            if ($notes && (str_contains($notes, 'เหลืออีก') || str_contains($notes, 'งวดสุดท้าย') || str_contains($notes, 'ผ่อนครบแล้ว'))) {
                $notes = $remMonths > 0
                    ? 'เหลืออีก ' . $remMonths . ' งวด (คงเหลือ ' . number_format($remAmount, 2) . ' ฿)'
                    : 'ผ่อนครบแล้ว (คงเหลือ 0.00 ฿)';
            }

            $installment->update([
                'paid_months' => $newPaid,
                'is_checked'  => $step > 0 ? true : $installment->is_checked,
                'notes'       => $notes,
            ]);

            return $this->redirectAfterAction(
                $installment->month,
                "อัปเดตงวดผ่อน '{$installment->label}' เป็น {$newPaid}/{$installment->total_months} งวดเรียบร้อยแล้ว"
            );
        }

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

        if ($type === 'debt-payment') {
            $statusMsg = $model->is_paid
                ? "บันทึกชำระงวด {$model->month} (฿" . number_format((float) $model->amount, 2) . ") เรียบร้อยแล้ว"
                : "ยกเลิกสถานะชำระงวด {$model->month} เรียบร้อยแล้ว";
            return redirect()->back()->with('status', $statusMsg);
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
        if ($request->has('notes')) {
            $payment->notes = $data['notes'] ?? null;
        }
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

        // Keep total_amount on non-กยศ variable debts in sync with their payment rows
        $parentDebt = $payment->debt;
        if ($parentDebt && !str_contains($parentDebt->label, 'กยศ')) {
            $parentDebt->total_amount = (float) $parentDebt->payments()->sum('amount');
            $parentDebt->save();
        }

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
        if ($request->boolean('sync_verified_plan')) {
            return $this->syncVerifiedPlan($request);
        }

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

        // 4. Copy Installments (incrementing paid_months if checked, skipping completed ones)
        $insts = Installment::query()->forUser($userId)->where('month', $currentMonth)->get();
        foreach ($insts as $inst) {
            $newPaid = (int) $inst->paid_months;
            if ($inst->is_checked) {
                $newPaid = min((int) $inst->total_months, $newPaid + 1);
            }

            // If this installment finished after paying the current month, mark the current record as completed
            // and do NOT copy it into the next month.
            if ($newPaid >= (int) $inst->total_months) {
                if ((int) $inst->paid_months !== $newPaid) {
                    $inst->update([
                        'paid_months' => $newPaid,
                        'notes' => 'ผ่อนครบแล้ว (คงเหลือ 0.00 ฿)',
                    ]);
                }
                continue;
            }

            $existsInNext = Installment::query()
                ->forUser($userId)
                ->where('month', $nextMonth)
                ->where('label', $inst->label)
                ->exists();

            if (!$existsInNext) {
                $remMonths = (int) $inst->total_months - $newPaid;
                $remAmount = max(0.0, round((float) $inst->total_amount - ((float) $inst->monthly_payment * $newPaid), 2));
                $notes = $inst->notes;
                if ($notes && (str_contains($notes, 'เหลืออีก') || str_contains($notes, 'งวดสุดท้าย'))) {
                    $notes = 'เหลืออีก ' . $remMonths . ' งวด (คงเหลือ ' . number_format($remAmount, 2) . ' ฿)';
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
                    'notes' => $notes,
                ]);
            }
        }

        return redirect()->route('portfolio.budget.index', ['month' => $nextMonth])
            ->with('status', "เริ่มต้นงบประมาณเดือนใหม่ ($nextMonth) และเตรียมข้อมูลสำเร็จแล้ว");
    }

    public function syncVerifiedPlan(Request $request)
    {
        $userId = $this->resolvePortfolioUserId();
        $month = $request->input('month', '2026-11');
        if (!preg_match('/^\d{4}-\d{2}$/', $month)) {
            $month = '2026-11';
        }

        $this->seedVerifiedNovember2026Plan($userId, $month);

        return $this->redirectAfterAction(
            $month,
            "ซิงก์ข้อมูลแผนการเงินและหนี้สินปัจจุบัน (พ.ย. 2569) เรียบร้อยแล้ว"
        );
    }

    public function seedVerifiedNovember2026Plan(int $userId, string $month = '2026-11'): void
    {
        // 1. Incomes (Pay Slip: Base net 28,911.00 + OT 1,000.00 = 29,911.00)
        Income::query()->forUser($userId)->where('month', $month)->delete();
        $incomes = [
            [
                'label' => 'เงินเดือนประจำสุทธิ (บจก. ดูดีพ)',
                'amount' => 28911.00,
                'notes' => 'ฐานเงินเดือน 30,000.00 หักประกันสังคม 875.00 และภาษี 214.00',
            ],
            [
                'label' => 'ค่าล่วงเวลา (OT)',
                'amount' => 1000.00,
                'notes' => 'รายได้ไม่ประจำตามสลิปเงินเดือน',
            ],
        ];
        foreach ($incomes as $inc) {
            Income::create(array_merge($inc, ['user_id' => $userId, 'month' => $month]));
        }

        // 2. Budget Items (Fixed Housing: Thatcharin Place 7,292 ฿ + Variable Living 6,600 ฿ + Emergency Fund Saving 2,021.08 ฿)
        BudgetItem::query()->forUser($userId)->where('month', $month)->delete();
        $budgetItems = [
            [
                'category' => BudgetItem::CATEGORY_FIXED,
                'label' => 'ค่าเช่าห้อง Thatcharin Place (ห้อง 410)',
                'amount' => 5100.00,
                'sort_order' => 1,
                'notes' => 'ครบกำหนดชำระวันที่ 7 ของเดือน (INV112610041)',
            ],
            [
                'category' => BudgetItem::CATEGORY_FIXED,
                'label' => 'ค่าส่วนกลาง + ที่จอดรถหอพัก',
                'amount' => 490.00,
                'sort_order' => 2,
                'notes' => 'ค่าส่วนกลาง 390.00 ฿ + ค่าที่จอดรถ 100.00 ฿',
            ],
            [
                'category' => BudgetItem::CATEGORY_FIXED,
                'label' => 'ค่าน้ำ + ค่าไฟหอพัก (332 ยูนิต)',
                'amount' => 1702.00,
                'sort_order' => 3,
                'notes' => 'ค่าน้ำ 5 ยูนิต (75 ฿) + ค่าไฟ 332 ยูนิต x 4.90 (1,627 ฿)',
            ],
            [
                'category' => BudgetItem::CATEGORY_VARIABLE,
                'label' => 'ค่าอาหารและเครื่องดื่มรายวัน',
                'amount' => 5400.00,
                'sort_order' => 1,
                'notes' => 'คุมงบเฉลี่ยวันละ 180 บาท',
            ],
            [
                'category' => BudgetItem::CATEGORY_VARIABLE,
                'label' => 'ค่าน้ำมันรถมอเตอร์ไซค์ & เดินทาง',
                'amount' => 1200.00,
                'sort_order' => 2,
                'notes' => 'เฉลี่ยสัปดาห์ละ 300 บาท (รวมค่ากิน+เดินทาง = 220 ฿/วัน)',
            ],
            [
                'category' => BudgetItem::CATEGORY_SAVING,
                'label' => 'เงินสำรองฉุกเฉิน (High-Yield e-Savings)',
                'amount' => 2021.08,
                'sort_order' => 1,
                'notes' => 'โอนเก็บทันทีที่เงินเดือนออก (เป้าหมายสำรอง 3 เดือน = 52,443 ฿)',
            ],
        ];
        foreach ($budgetItems as $item) {
            BudgetItem::create(array_merge($item, [
                'user_id' => $userId,
                'month' => $month,
                'is_checked' => false,
            ]));
        }

        // 3. Installments (6 items from Debt Excel = 7,679.02 ฿/month)
        // Remove discontinued/crossed-out installment labels from earlier months so /portfolio/debts stays clean
        $activeInstallmentLabels = ['S24 Ultra', 'SPayLater', 'สินเชื่อเงินสด TikTok', 'mono max', 'มอไซต์', 'คืนแม่'];
        Installment::query()->forUser($userId)->whereNotIn('label', $activeInstallmentLabels)->delete();
        Installment::query()->forUser($userId)->where('month', '>=', $month)->delete();

        $installments = [
            [
                'label' => 'S24 Ultra',
                'monthly_payment' => 1498.24,
                'total_amount' => 35957.76,
                'paid_months' => 14,
                'total_months' => 24,
                'notes' => 'เหลืออีก 10 งวด (คงเหลือ 14,982.40 ฿)',
            ],
            [
                'label' => 'SPayLater',
                'monthly_payment' => 1000.00,
                'total_amount' => 10000.00,
                'paid_months' => 4,
                'total_months' => 10,
                'notes' => 'เหลืออีก 6 งวด (คงเหลือ 6,000.00 ฿)',
            ],
            [
                'label' => 'สินเชื่อเงินสด TikTok',
                'monthly_payment' => 948.62,
                'total_amount' => 9000.00,
                'paid_months' => 3,
                'total_months' => 12,
                'notes' => 'เหลืออีก 9 งวด (คงเหลือ 6,154.14 ฿)',
            ],
            [
                'label' => 'mono max',
                'monthly_payment' => 243.16,
                'total_amount' => 2918.00,
                'paid_months' => 3,
                'total_months' => 12,
                'notes' => 'เหลืออีก 9 งวด (คงเหลือ 2,188.52 ฿)',
            ],
            [
                'label' => 'มอไซต์',
                'monthly_payment' => 2989.00,
                'total_amount' => 99972.00,
                'paid_months' => 3,
                'total_months' => 20,
                'notes' => 'คงเหลือ 91,005.00 ฿',
            ],
            [
                'label' => 'คืนแม่',
                'monthly_payment' => 1000.00,
                'total_amount' => 1000.00,
                'paid_months' => 0,
                'total_months' => 1,
                'notes' => 'งวดสุดท้าย พ.ย. 2026 (คงเหลือ 1,000.00 ฿)',
            ],
        ];
        foreach ($installments as $inst) {
            Installment::create(array_merge($inst, [
                'user_id' => $userId,
                'month' => $month,
                'is_checked' => false,
            ]));
        }

        // 4. Stepped Variable Debt Schedule (TikTok PayLater 6 งวด: พ.ย. 2026 – เม.ย. 2027)
        // Remove obsolete non-กยศ variable debts so only current stepped schedule + กยศ remain
        Debt::query()
            ->forUser($userId)
            ->where('label', 'not like', '%กยศ%')
            ->where('label', '!=', 'TikTok PayLater (ครบกำหนดทุกวันที่ 1)')
            ->where('label', '!=', 'ใบแจ้งยอดล่วงหน้า (ครบกำหนดทุกวันที่ 1)')
            ->delete();

        $steppedDebt = Debt::updateOrCreate(
            ['user_id' => $userId, 'label' => 'TikTok PayLater (ครบกำหนดทุกวันที่ 1)'],
            [
                'total_amount' => 14729.07,
                'notes' => 'ยอดค้างชำระรายเดือนลดลงตามขั้นบันได 6 งวด (พ.ย. 2026 - เม.ย. 2027)',
            ]
        );
        $steppedDebt->payments()->whereNotIn('month', ['2026-11', '2026-12', '2027-01', '2027-02', '2027-03', '2027-04'])->delete();
        $schedule = [
            '2026-11' => ['amount' => 4955.97, 'notes' => 'ครบกำหนดชำระ: 1 ธ.ค. 2026'],
            '2026-12' => ['amount' => 3867.24, 'notes' => 'ครบกำหนดชำระ: 1 ม.ค. 2027'],
            '2027-01' => ['amount' => 2614.94, 'notes' => 'ครบกำหนดชำระ: 1 ก.พ. 2027'],
            '2027-02' => ['amount' => 1429.25, 'notes' => 'ครบกำหนดชำระ: 1 มี.ค. 2027'],
            '2027-03' => ['amount' => 1099.97, 'notes' => 'ครบกำหนดชำระ: 1 เม.ย. 2027'],
            '2027-04' => ['amount' => 761.70,  'notes' => 'ครบกำหนดชำระ: 1 พ.ค. 2027 (งวดสุดท้าย)'],
        ];
        foreach ($schedule as $ym => $row) {
            DebtPayment::updateOrCreate(
                ['debt_id' => $steppedDebt->id, 'user_id' => $userId, 'month' => $ym],
                ['amount' => $row['amount'], 'notes' => $row['notes']]
            );
        }

        // 5. Subscriptions (7 items = 1,362.93 ฿/month)
        Subscription::query()->forUser($userId)->where('month', $month)->delete();
        $subscriptions = [
            ['label' => 'เน็ตรายเดือน', 'monthly_payment' => 426.93, 'billing_day' => 1, 'notes' => 'อินเทอร์เน็ตมือถือรายเดือน'],
            ['label' => 'YouTube', 'monthly_payment' => 240.00, 'billing_day' => 5, 'notes' => 'แนะนำย้ายไปสมัครผ่านเว็บโดยตรงเหลือ 179 ฿ (-61 ฿/ด.)'],
            ['label' => 'Spotify', 'monthly_payment' => 209.00, 'billing_day' => 10, 'notes' => 'ซ้ำซ้อนกับ YouTube Music (พักชั่วคราวประหยัด 209 ฿/ด.)'],
            ['label' => 'Google gemini pro', 'monthly_payment' => 189.00, 'billing_day' => 12, 'notes' => 'เครื่องมือ AI ช่วยทำงานสาย IT'],
            ['label' => 'ค่ากล้องวงจรปิดบ้านย่า', 'monthly_payment' => 169.00, 'billing_day' => 15, 'notes' => 'ดูแลความปลอดภัยครอบครัว'],
            ['label' => 'Google cloud แม่', 'monthly_payment' => 70.00, 'billing_day' => 18, 'notes' => 'พื้นที่สำรองข้อมูลให้คุณแม่'],
            ['label' => 'Whoscall', 'monthly_payment' => 59.00, 'billing_day' => 20, 'notes' => 'ใช้ Smart Call ใน S24 Ultra แทนได้ (-59 ฿/ด.)'],
        ];
        foreach ($subscriptions as $sub) {
            Subscription::create(array_merge($sub, [
                'user_id' => $userId,
                'month' => $month,
                'is_checked' => false,
            ]));
        }
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
