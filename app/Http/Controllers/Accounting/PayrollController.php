<?php

namespace App\Http\Controllers\Accounting;

use App\Exceptions\Accounting\LedgerException;
use App\Mail\PayslipMail;
use App\Models\Accounting\Account;
use App\Models\Accounting\Employee;
use App\Services\Accounting\AccountingAuditLog;
use App\Models\Accounting\PayrollItem;
use App\Models\Accounting\PayrollRun;
use App\Services\Accounting\Payroll;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PayrollController extends AccountingController
{
    public function employees()
    {
        $workspace = $this->requireWorkspace();
        $employees = Employee::forWorkspace($workspace)->orderBy('code')->get();

        return view('accounting.payroll.employees', compact('workspace', 'employees'));
    }

    public function storeEmployee(Request $request)
    {
        $workspace = $this->requireWorkspace();
        $this->assertPoster($workspace);

        $data = $request->validate([
            'code' => 'required|string|max:40',
            'name' => 'required|string|max:160',
            'email' => 'nullable|email|max:160',
            'tax_id' => 'nullable|string|max:20',
            'social_security_id' => 'nullable|string|max:20',
            'base_salary' => 'required|numeric|min:0',
            'bank_account' => 'nullable|string|max:40',
            'position' => 'nullable|string|max:80',
            'hired_on' => 'nullable|date',
        ]);

        try {
            Payroll::registerEmployee($workspace, $data);
        } catch (LedgerException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        AccountingAuditLog::record($workspace, 'employee.added', null, $data['name']);

        return back()->with('status', __('app.accounting.employee_added'));
    }

    public function editEmployee(Employee $employee)
    {
        $workspace = $this->requireWorkspace();
        abort_unless($employee->workspace_id === $workspace->id, 404);

        return view('accounting.payroll.edit-employee', compact('workspace', 'employee'));
    }

    public function updateEmployee(Request $request, Employee $employee)
    {
        $workspace = $this->requireWorkspace();
        abort_unless($employee->workspace_id === $workspace->id, 404);
        $this->assertPoster($workspace);

        $data = $request->validate([
            'name' => 'required|string|max:160',
            'email' => 'nullable|email|max:160',
            'tax_id' => 'nullable|string|max:20',
            'social_security_id' => 'nullable|string|max:20',
            'base_salary' => 'required|numeric|min:0',
            'bank_account' => 'nullable|string|max:40',
            'position' => 'nullable|string|max:80',
            'hired_on' => 'nullable|date',
        ]);

        $employee->update($data);

        AccountingAuditLog::record($workspace, 'employee.updated', $employee, $employee->name);

        return redirect()->route('accounting.payroll.employees')
            ->with('status', __('app.accounting.employee_updated'));
    }

    public function toggleEmployee(Employee $employee)
    {
        $workspace = $this->requireWorkspace();
        abort_unless($employee->workspace_id === $workspace->id, 404);
        $this->assertPoster($workspace);

        $employee->update(['is_active' => ! $employee->is_active]);

        AccountingAuditLog::record($workspace, 'employee.toggled', $employee, $employee->name,
            ['is_active' => $employee->is_active]);

        return back()->with('status', __('app.accounting.employee_updated'));
    }

    public function runs()
    {
        $workspace = $this->requireWorkspace();
        $runs = PayrollRun::forWorkspace($workspace)->orderByDesc('period')->get();

        return view('accounting.payroll.runs', compact('workspace', 'runs'));
    }

    public function createRun()
    {
        $workspace = $this->requireWorkspace();

        $expenseAccounts = Account::forWorkspace($workspace)->where('type', 'expense')->where('is_active', true)->orderBy('code')->get();
        $liabilityAccounts = Account::forWorkspace($workspace)->where('type', 'liability')->where('is_active', true)->orderBy('code')->get();
        $assetAccounts = Account::forWorkspace($workspace)->where('type', 'asset')->where('is_active', true)->orderBy('code')->get();

        return view('accounting.payroll.create-run', compact('workspace', 'expenseAccounts', 'liabilityAccounts', 'assetAccounts'));
    }

    public function storeRun(Request $request)
    {
        $workspace = $this->requireWorkspace();
        $this->assertPoster($workspace);

        $data = $request->validate([
            'period' => 'required|regex:/^\d{4}-\d{2}$/',
            'payment_date' => 'required|date',
            'salary_expense_account_id' => 'required|integer',
            'ss_expense_account_id' => 'required|integer',
            'ss_payable_account_id' => 'required|integer',
            'wht_payable_account_id' => 'required|integer',
            'cash_account_id' => 'required|integer',
        ]);

        try {
            $run = Payroll::createRun($workspace, $data['period'], $data);
        } catch (LedgerException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        AccountingAuditLog::record($workspace, 'payroll_run.created', $run, $run->period);

        return redirect()->route('accounting.payroll.runs.show', $run)
            ->with('status', __('app.accounting.payroll_run_created'));
    }

    public function showRun(PayrollRun $payrollRun)
    {
        $workspace = $this->requireWorkspace();
        abort_unless($payrollRun->workspace_id === $workspace->id, 404);
        $payrollRun->load('items.employee', 'journal');

        return view('accounting.payroll.show-run', compact('workspace', 'payrollRun'));
    }

    public function updateItem(Request $request, PayrollItem $payrollItem)
    {
        $workspace = $this->requireWorkspace();
        abort_unless($payrollItem->workspace_id === $workspace->id, 404);
        $this->assertPoster($workspace);

        $data = $request->validate([
            'gross' => 'required|numeric|min:0',
            'overtime' => 'nullable|numeric|min:0',
            'ot' => 'nullable|numeric|min:0',
            'wht' => 'nullable|numeric|min:0',
            'other_deductions' => 'nullable|numeric|min:0',
        ]);

        try {
            Payroll::updateItem($payrollItem, $data);
        } catch (LedgerException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('app.accounting.payroll_item_updated'));
    }

    public function postRun(PayrollRun $payrollRun)
    {
        $workspace = $this->requireWorkspace();
        abort_unless($payrollRun->workspace_id === $workspace->id, 404);
        $this->assertPoster($workspace);

        try {
            Payroll::post($payrollRun);
        } catch (LedgerException $e) {
            return back()->with('error', $e->getMessage());
        }

        AccountingAuditLog::record($workspace, 'payroll_run.posted', $payrollRun, $payrollRun->period);

        $sent = $this->dispatchPayslipEmails($payrollRun);

        return back()->with('status', __('app.accounting.payroll_posted_with_slips', ['count' => $sent]));
    }

    /**
     * Email each item's payslip (PDF attached) to the employee. Failures are
     * logged but never block the post — payroll is already on the ledger.
     */
    private function dispatchPayslipEmails(PayrollRun $payrollRun): int
    {
        $payrollRun->loadMissing('items.employee', 'workspace');
        $sent = 0;

        foreach ($payrollRun->items as $item) {
            $email = $item->employee->email ?? null;
            if (! $email) {
                continue;
            }

            try {
                Mail::to($email)->send(new PayslipMail($item));
                $sent++;
            } catch (\Throwable $e) {
                Log::warning('Payslip mail failed', [
                    'payroll_item_id' => $item->id,
                    'employee' => $item->employee->code,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return $sent;
    }

    public function payslip(PayrollItem $payrollItem)
    {
        $workspace = $this->requireWorkspace();
        abort_unless($payrollItem->workspace_id === $workspace->id, 404);
        $payrollItem->load('employee', 'run');

        return view('accounting.payroll.payslip', compact('payrollItem', 'workspace'));
    }
}
