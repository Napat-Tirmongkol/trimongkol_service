<?php

namespace App\Http\Controllers\Accounting;

use App\Services\Accounting\Reporting;
use Illuminate\Http\Request;

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
}
