<?php

namespace App\Http\Controllers\Accounting;

use App\Models\Accounting\BillLine;
use App\Models\Accounting\Department;
use App\Models\Accounting\InvoiceLine;
use App\Models\Accounting\JournalLine;
use App\Models\Accounting\RecurringJournalLine;
use App\Models\Workspace;
use App\Services\Accounting\AccountingAuditLog;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Cost centres / departments — the company's "by branch/team" dimension on
 * the P&L. Every posting line can optionally carry a department_id, so books
 * still work for tenants that don't use it.
 */
class DepartmentController extends AccountingController
{
    public function index()
    {
        $workspace = $this->requireWorkspace();

        $departments = Department::forWorkspace($workspace)->orderBy('code')->get();

        return view('accounting.departments.index', compact('workspace', 'departments'));
    }

    public function create()
    {
        $this->requireWorkspace();

        return view('accounting.departments.form', [
            'department' => new Department(['is_active' => true]),
        ]);
    }

    public function store(Request $request)
    {
        $workspace = $this->requireWorkspace();
        $data = $this->validated($request, $workspace);

        Department::create([
            'workspace_id' => $workspace->id,
            'code' => $data['code'],
            'name' => $data['name'],
            'is_active' => $request->boolean('is_active'),
        ]);

        AccountingAuditLog::record($workspace, 'department.created', null, "{$data['code']} {$data['name']}");

        return redirect()->route('accounting.departments.index')
            ->with('status', __('app.accounting.department_saved'));
    }

    public function edit(Department $department)
    {
        $this->scopedDepartment($department);

        return view('accounting.departments.form', compact('department'));
    }

    public function update(Request $request, Department $department)
    {
        $workspace = $this->scopedDepartment($department);
        $data = $this->validated($request, $workspace, $department);

        $department->update([
            'code' => $data['code'],
            'name' => $data['name'],
            'is_active' => $request->boolean('is_active'),
        ]);

        AccountingAuditLog::record($workspace, 'department.updated', $department, "{$department->code} {$department->name}");

        return redirect()->route('accounting.departments.index')
            ->with('status', __('app.accounting.department_updated'));
    }

    public function destroy(Department $department)
    {
        $workspace = $this->scopedDepartment($department);

        $referenced = JournalLine::where('department_id', $department->id)->exists()
            || BillLine::where('department_id', $department->id)->exists()
            || InvoiceLine::where('department_id', $department->id)->exists()
            || RecurringJournalLine::where('department_id', $department->id)->exists();

        if ($referenced) {
            return back()->with('error', __('app.accounting.department_in_use'));
        }

        $label = "{$department->code} {$department->name}";
        $department->delete();

        AccountingAuditLog::record($workspace, 'department.deleted', null, $label);

        return redirect()->route('accounting.departments.index')
            ->with('status', __('app.accounting.department_deleted'));
    }

    private function scopedDepartment(Department $department): Workspace
    {
        $workspace = $this->requireWorkspace();
        abort_unless($department->workspace_id === $workspace->id, 404);

        return $workspace;
    }

    private function validated(Request $request, Workspace $workspace, ?Department $department = null): array
    {
        return $request->validate([
            'code' => ['required', 'string', 'max:20',
                Rule::unique('accounting_departments', 'code')
                    ->where('workspace_id', $workspace->id)
                    ->ignore($department?->id),
            ],
            'name' => 'required|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);
    }
}
