<?php

namespace App\Http\Controllers\Accounting;

use App\Exceptions\Accounting\LedgerException;
use App\Models\Accounting\Account;
use App\Models\Accounting\Department;
use App\Models\Accounting\Journal;
use App\Services\Accounting\AccountingAuditLog;
use App\Services\Accounting\LedgerPosting;
use Illuminate\Http\Request;

/**
 * Free-form journal entries — for accruals, adjustments, depreciation tweaks,
 * opening-balance corrections, and anything the dedicated services (Sales,
 * Purchase, Receipts…) don't cover. Goes through LedgerPosting like everything
 * else, so the balance check and open-period guard still apply.
 */
class ManualJournalController extends AccountingController
{
    public function index()
    {
        $workspace = $this->requireWorkspace();

        $journals = Journal::forWorkspace($workspace)
            ->where('type', 'manual')
            ->with('lines.account')
            ->latest('date')->latest('id')
            ->paginate(25);

        return view('accounting.manual-journals.index', compact('workspace', 'journals'));
    }

    public function create()
    {
        $workspace = $this->requireWorkspace();
        if (! $this->isSetUp($workspace)) {
            return redirect()->route('accounting.dashboard')->with('error', __('app.accounting.setup_required'));
        }

        $accounts = Account::forWorkspace($workspace)->where('is_active', true)->orderBy('code')->get();
        $departments = Department::forWorkspace($workspace)->where('is_active', true)->orderBy('code')->get();

        return view('accounting.manual-journals.create', compact('accounts', 'departments'));
    }

    public function store(Request $request)
    {
        $workspace = $this->requireWorkspace();
        $this->assertPoster($workspace);

        $data = $request->validate([
            'date' => 'required|date',
            'memo' => 'nullable|string|max:255',
            'lines' => 'required|array|min:2',
            'lines.*.account_id' => 'required|integer',
            'lines.*.debit' => 'required|numeric|min:0',
            'lines.*.credit' => 'required|numeric|min:0',
            'lines.*.description' => 'nullable|string|max:255',
            'lines.*.department_id' => 'nullable|integer',
        ]);

        try {
            $journal = LedgerPosting::post($workspace, [
                'date' => $data['date'],
                'type' => 'manual',
                'memo' => $data['memo'] ?? null,
            ], $data['lines']);
        } catch (LedgerException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        AccountingAuditLog::record($workspace, 'manual_journal.created', $journal, $journal->no);

        return redirect()->route('accounting.manual-journals.show', $journal)
            ->with('status', __('app.accounting.manual_journal_created'));
    }

    public function show(Journal $manualJournal)
    {
        $workspace = $this->requireWorkspace();
        abort_unless($manualJournal->workspace_id === $workspace->id, 404);

        $manualJournal->load('lines.account', 'reverses', 'reversedBy');

        return view('accounting.manual-journals.show', [
            'journal' => $manualJournal,
            'canPost' => auth('accounting')->user()?->canPost() ?? false,
        ]);
    }

    public function void(Journal $manualJournal)
    {
        $workspace = $this->requireWorkspace();
        abort_unless($manualJournal->workspace_id === $workspace->id, 404);
        $this->assertPoster($workspace);

        try {
            LedgerPosting::reverse($manualJournal, [
                'memo' => "กลับรายการ {$manualJournal->no}",
            ]);
        } catch (LedgerException $e) {
            return back()->with('error', $e->getMessage());
        }

        AccountingAuditLog::record($workspace, 'manual_journal.voided', $manualJournal, $manualJournal->no);

        return redirect()->route('accounting.manual-journals.show', $manualJournal)
            ->with('status', __('app.accounting.manual_journal_voided'));
    }
}
