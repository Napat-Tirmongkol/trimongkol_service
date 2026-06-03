<?php

namespace App\Http\Controllers\Accounting;

use App\Exceptions\Accounting\LedgerException;
use App\Models\Accounting\Account;
use App\Models\Accounting\RecurringJournal;
use App\Services\Accounting\AccountingAuditLog;
use App\Services\Accounting\RecurringJournals;
use Illuminate\Http\Request;

class RecurringJournalController extends AccountingController
{
    public function index()
    {
        $workspace = $this->requireWorkspace();

        $templates = RecurringJournal::forWorkspace($workspace)
            ->with('lines.account')
            ->orderByDesc('id')
            ->get();

        return view('accounting.recurring-journals.index', compact('workspace', 'templates'));
    }

    public function create()
    {
        $workspace = $this->requireWorkspace();

        $accounts = Account::forWorkspace($workspace)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('accounting.recurring-journals.create', compact('workspace', 'accounts'));
    }

    public function store(Request $request)
    {
        $workspace = $this->requireWorkspace();
        $this->assertPoster($workspace);

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'frequency' => 'required|in:monthly,quarterly,weekly',
            'next_run_date' => 'required|date',
            'end_date' => 'nullable|date|after:next_run_date',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|integer',
            'lines.*.debit' => 'required|numeric|min:0',
            'lines.*.credit' => 'required|numeric|min:0',
            'lines.*.description' => 'nullable|string|max:255',
        ]);

        try {
            RecurringJournals::create($workspace, [
                'name' => $data['name'],
                'frequency' => $data['frequency'],
                'next_run_date' => $data['next_run_date'],
                'end_date' => $data['end_date'] ?? null,
            ], $data['lines']);
        } catch (LedgerException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        AccountingAuditLog::record($workspace, 'recurring.created', null, $data['name']);

        return redirect()->route('accounting.recurring-journals.index')
            ->with('status', __('app.accounting.recurring_created'));
    }

    public function edit(RecurringJournal $recurringJournal)
    {
        $workspace = $this->requireWorkspace();
        abort_unless($recurringJournal->workspace_id === $workspace->id, 404);
        $this->assertPoster($workspace);

        $recurringJournal->load('lines');
        $accounts = Account::forWorkspace($workspace)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('accounting.recurring-journals.edit', compact('recurringJournal', 'accounts'));
    }

    public function update(Request $request, RecurringJournal $recurringJournal)
    {
        $workspace = $this->requireWorkspace();
        abort_unless($recurringJournal->workspace_id === $workspace->id, 404);
        $this->assertPoster($workspace);

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'frequency' => 'required|in:monthly,quarterly,weekly',
            'next_run_date' => 'required|date',
            'end_date' => 'nullable|date|after:next_run_date',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|integer',
            'lines.*.debit' => 'required|numeric|min:0',
            'lines.*.credit' => 'required|numeric|min:0',
            'lines.*.description' => 'nullable|string|max:255',
        ]);

        try {
            RecurringJournals::update($recurringJournal, [
                'name' => $data['name'],
                'frequency' => $data['frequency'],
                'next_run_date' => $data['next_run_date'],
                'end_date' => $data['end_date'] ?? null,
            ], $data['lines']);
        } catch (LedgerException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        AccountingAuditLog::record($workspace, 'recurring.updated', $recurringJournal, $recurringJournal->name);

        return redirect()->route('accounting.recurring-journals.index')
            ->with('status', __('app.accounting.recurring_updated'));
    }

    public function run(Request $request, RecurringJournal $recurringJournal)
    {
        $workspace = $this->requireWorkspace();
        abort_unless($recurringJournal->workspace_id === $workspace->id, 404);
        $this->assertPoster($workspace);

        $data = $request->validate([
            'date' => 'nullable|date',
        ]);

        try {
            RecurringJournals::run($recurringJournal, $data['date'] ?? null);
        } catch (LedgerException $e) {
            return back()->with('error', $e->getMessage());
        }

        AccountingAuditLog::record($workspace, 'recurring.run', $recurringJournal, $recurringJournal->name);

        return back()->with('status', __('app.accounting.recurring_run'));
    }

    public function toggle(RecurringJournal $recurringJournal)
    {
        $workspace = $this->requireWorkspace();
        abort_unless($recurringJournal->workspace_id === $workspace->id, 404);
        $this->assertPoster($workspace);

        $recurringJournal->update(['is_active' => ! $recurringJournal->is_active]);

        $key = $recurringJournal->is_active ? 'recurring_activated' : 'recurring_deactivated';

        AccountingAuditLog::record($workspace, 'recurring.toggled', $recurringJournal, $recurringJournal->name,
            ['is_active' => $recurringJournal->is_active]);

        return back()->with('status', __("app.accounting.{$key}"));
    }

    public function destroy(RecurringJournal $recurringJournal)
    {
        $workspace = $this->requireWorkspace();
        abort_unless($recurringJournal->workspace_id === $workspace->id, 404);
        $this->assertPoster($workspace);

        $label = $recurringJournal->name;
        $recurringJournal->delete();

        AccountingAuditLog::record($workspace, 'recurring.deleted', null, $label);

        return redirect()->route('accounting.recurring-journals.index')
            ->with('status', __('app.accounting.recurring_deleted'));
    }
}
