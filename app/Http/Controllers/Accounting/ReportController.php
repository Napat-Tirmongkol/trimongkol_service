<?php

namespace App\Http\Controllers\Accounting;

use App\Models\Accounting\Department;
use App\Models\Accounting\Journal;
use App\Models\Accounting\JournalLine;
use App\Models\Accounting\Partner;
use App\Services\Accounting\Reporting;
use App\Services\Accounting\TaxReporting;
use App\Services\AccountingPlan;
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

    public function budgetVsActual(Request $request)
    {
        $workspace = $this->requireWorkspace();
        if (! $this->isSetUp($workspace)) {
            return redirect()->route('accounting.dashboard')->with('error', __('app.accounting.setup_required'));
        }

        $year = (int) ($request->query('year') ?: now()->year);
        $monthRaw = $request->query('month');
        $month = ($monthRaw === null || $monthRaw === '') ? null : (int) $monthRaw;
        if ($month !== null && ($month < 1 || $month > 12)) {
            $month = null;
        }

        $deptRaw = $request->query('department');
        $deptId = match (true) {
            $deptRaw === null || $deptRaw === '' || $deptRaw === 'all' => null,
            $deptRaw === 'unassigned' => 'unassigned',
            default => (int) $deptRaw,
        };

        return view('accounting.reports.budget-vs-actual', [
            'year' => $year,
            'month' => $month,
            'departmentSelection' => $deptRaw === null || $deptRaw === '' ? 'all' : $deptRaw,
            'departments' => Department::forWorkspace($workspace)->orderBy('code')->get(),
            'report' => Reporting::budgetVsActual($workspace, $year, $month, $deptId),
        ]);
    }

    public function profitAndLossByDepartment(Request $request)
    {
        $workspace = $this->requireWorkspace();
        if (! $this->isSetUp($workspace)) {
            return redirect()->route('accounting.dashboard')->with('error', __('app.accounting.setup_required'));
        }

        $from = ($request->date('from') ?? now()->startOfYear())->toDateString();
        $to = ($request->date('to') ?? now()->endOfYear())->toDateString();

        return view('accounting.reports.pnl-by-department', [
            'from' => $from,
            'to' => $to,
            'report' => Reporting::profitAndLossByDepartment($workspace, $from, $to),
        ]);
    }

    public function salesByPartner(Request $request)
    {
        $workspace = $this->requireWorkspace();
        if (! $this->isSetUp($workspace)) {
            return redirect()->route('accounting.dashboard')->with('error', __('app.accounting.setup_required'));
        }

        $from = ($request->date('from') ?? now()->startOfYear())->toDateString();
        $to = ($request->date('to') ?? now()->endOfYear())->toDateString();

        return view('accounting.reports.sales-by-partner', [
            'from' => $from,
            'to' => $to,
            'report' => Reporting::salesByPartner($workspace, $from, $to),
        ]);
    }

    public function purchasesByPartner(Request $request)
    {
        $workspace = $this->requireWorkspace();
        if (! $this->isSetUp($workspace)) {
            return redirect()->route('accounting.dashboard')->with('error', __('app.accounting.setup_required'));
        }

        $from = ($request->date('from') ?? now()->startOfYear())->toDateString();
        $to = ($request->date('to') ?? now()->endOfYear())->toDateString();

        return view('accounting.reports.purchases-by-partner', [
            'from' => $from,
            'to' => $to,
            'report' => Reporting::purchasesByPartner($workspace, $from, $to),
        ]);
    }

    public function partnerStatement(Request $request, Partner $partner)
    {
        $workspace = $this->requireWorkspace();
        abort_unless($partner->workspace_id === $workspace->id, 404);

        $from = ($request->date('from') ?? now()->startOfYear())->toDateString();
        $to = ($request->date('to') ?? now()->endOfYear())->toDateString();

        return view('accounting.reports.partner-statement', [
            'partner' => $partner,
            'from' => $from,
            'to' => $to,
            'report' => Reporting::partnerStatement($workspace, $partner, $from, $to),
        ]);
    }

    public function agedReceivables(Request $request)
    {
        $workspace = $this->requireWorkspace();
        if (! $this->isSetUp($workspace)) {
            return redirect()->route('accounting.dashboard')->with('error', __('app.accounting.setup_required'));
        }

        $asOf = ($request->date('as_of') ?? now())->toDateString();

        return view('accounting.reports.aged-ar', [
            'asOf' => $asOf,
            'report' => Reporting::agedReceivables($workspace, $asOf),
        ]);
    }

    public function agedPayables(Request $request)
    {
        $workspace = $this->requireWorkspace();
        if (! $this->isSetUp($workspace)) {
            return redirect()->route('accounting.dashboard')->with('error', __('app.accounting.setup_required'));
        }

        $asOf = ($request->date('as_of') ?? now())->toDateString();

        return view('accounting.reports.aged-ap', [
            'asOf' => $asOf,
            'report' => Reporting::agedPayables($workspace, $asOf),
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
    public function exportJournal(Request $request)
    {
        $workspace = $this->requireWorkspace();
        if (! AccountingPlan::can($workspace, 'csv_export')) {
            return redirect()->route('accounting.reports.tax')->with('error', __('app.accounting.plan_feature_locked'));
        }
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
