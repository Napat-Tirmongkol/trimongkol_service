<?php

namespace App\Http\Controllers\Accounting;

use App\Models\Accounting\Journal;
use App\Models\Accounting\JournalLine;
use App\Services\Accounting\Reporting;
use App\Services\Accounting\TaxReporting;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends AccountingController
{
    public function index(Request $request)
    {
        $workspace = $this->requireWorkspace();
        if (! $this->isSetUp($workspace)) {
            return redirect()->route('accounting.dashboard')->with('error', __('app.accounting.setup_required'));
        }

        $from = ($request->date('from') ?? now()->startOfYear())->toDateString();
        $to = ($request->date('to') ?? now()->endOfYear())->toDateString();

        return view('accounting.reports', [
            'from' => $from,
            'to' => $to,
            'trialBalance' => Reporting::trialBalance($workspace, $to),
            'pnl' => Reporting::profitAndLoss($workspace, $from, $to),
            'balanceSheet' => Reporting::balanceSheet($workspace, $to),
        ]);
    }

    public function tax(Request $request)
    {
        $workspace = $this->requireWorkspace();
        if (! $this->isSetUp($workspace)) {
            return redirect()->route('accounting.dashboard')->with('error', __('app.accounting.setup_required'));
        }

        // VAT reports are filed monthly — default to the current month.
        $from = ($request->date('from') ?? now()->startOfMonth())->toDateString();
        $to = ($request->date('to') ?? now()->endOfMonth())->toDateString();

        return view('accounting.tax-reports', [
            'from' => $from,
            'to' => $to,
            'vatSales' => TaxReporting::vatSales($workspace, $from, $to),
            'vatPurchases' => TaxReporting::vatPurchases($workspace, $from, $to),
            'wht' => TaxReporting::wht($workspace, $from, $to),
        ]);
    }

    /** Posted journal lines for the period as CSV — the hand-off to the accountant. */
    public function exportJournal(Request $request): StreamedResponse
    {
        $workspace = $this->requireWorkspace();
        $from = ($request->date('from') ?? now()->startOfMonth())->toDateString();
        $to = ($request->date('to') ?? now()->endOfMonth())->toDateString();

        $lines = JournalLine::query()->forWorkspace($workspace)->with('account', 'journal')
            ->whereHas('journal', fn ($q) => $q->where('status', Journal::STATUS_POSTED)
                ->whereDate('date', '>=', $from)->whereDate('date', '<=', $to))
            ->get()
            ->sortBy(fn ($l) => sprintf('%s-%010d-%03d', $l->journal->date->toDateString(), $l->journal_id, $l->line_no));

        return response()->streamDownload(function () use ($lines) {
            $out = fopen('php://output', 'w');
            fprintf($out, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel reads Thai correctly
            fputcsv($out, ['Date', 'No', 'Type', 'Account code', 'Account', 'Debit', 'Credit', 'Description', 'Memo']);
            foreach ($lines as $line) {
                fputcsv($out, [
                    $line->journal->date->toDateString(),
                    $line->journal->no,
                    $line->journal->type,
                    $line->account?->code,
                    $line->account?->name,
                    $line->debit,
                    $line->credit,
                    $line->description,
                    $line->journal->memo,
                ]);
            }
            fclose($out);
        }, "journal-{$from}-to-{$to}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
