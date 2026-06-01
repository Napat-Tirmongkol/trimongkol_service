<?php

namespace App\Http\Controllers\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\BankStatement;
use App\Services\Accounting\BankReconciliation;
use Illuminate\Http\Request;

class BankReconciliationController extends AccountingController
{
    public function index(Request $request)
    {
        $workspace = $this->requireWorkspace();

        $bankAccounts = Account::forWorkspace($workspace)
            ->where('type', 'asset')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $account = $request->account_id
            ? Account::forWorkspace($workspace)->find($request->account_id)
            : $bankAccounts->first();

        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to = $request->get('to', now()->endOfMonth()->toDateString());

        $summary = null;
        $unmatchedLines = collect();
        if ($account) {
            $summary = BankReconciliation::summary($workspace, $account, $from, $to);
            $unmatchedLines = BankReconciliation::unmatchedJournalLines($workspace, $account, $from, $to);
        }

        return view('accounting.bank-reconciliation.index', compact(
            'workspace', 'bankAccounts', 'account', 'from', 'to', 'summary', 'unmatchedLines'
        ));
    }

    public function storeLine(Request $request)
    {
        $workspace = $this->requireWorkspace();

        $data = $request->validate([
            'account_id' => 'required|integer',
            'statement_date' => 'required|date',
            'description' => 'nullable|string|max:255',
            'amount' => 'required|numeric',
            'reference' => 'nullable|string|max:120',
        ]);

        $account = Account::forWorkspace($workspace)->findOrFail($data['account_id']);

        try {
            BankReconciliation::addLine($workspace, $account, $data);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('app.accounting.bank_line_added'));
    }

    public function importCsv(Request $request)
    {
        $workspace = $this->requireWorkspace();

        $data = $request->validate([
            'account_id' => 'required|integer',
            'csv' => 'required|string',
        ]);

        $account = Account::forWorkspace($workspace)->findOrFail($data['account_id']);

        try {
            $count = BankReconciliation::importCsv($workspace, $account, $data['csv']);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('app.accounting.bank_csv_imported', ['count' => $count]));
    }

    public function match(Request $request, BankStatement $statement)
    {
        $workspace = $this->requireWorkspace();
        abort_unless($statement->workspace_id === $workspace->id, 404);

        $data = $request->validate([
            'journal_line_id' => 'required|integer',
        ]);

        $journalLine = \App\Models\Accounting\JournalLine::forWorkspace($workspace)->with('journal')->findOrFail($data['journal_line_id']);

        try {
            BankReconciliation::match($statement, $journalLine);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('status', __('app.accounting.bank_matched'));
    }

    public function unmatch(BankStatement $statement)
    {
        $workspace = $this->requireWorkspace();
        abort_unless($statement->workspace_id === $workspace->id, 404);

        BankReconciliation::unmatch($statement);

        return back()->with('status', __('app.accounting.bank_unmatched'));
    }

    public function destroy(BankStatement $statement)
    {
        $workspace = $this->requireWorkspace();
        abort_unless($statement->workspace_id === $workspace->id, 404);

        $statement->delete();

        return back()->with('status', __('app.accounting.bank_line_deleted'));
    }
}
