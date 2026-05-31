<?php

namespace App\Http\Controllers\Accounting;

use App\Exceptions\Accounting\ClosedPeriodException;
use App\Exceptions\Accounting\LedgerException;
use App\Exceptions\Accounting\UnbalancedJournalException;
use App\Models\Accounting\Account;
use App\Models\Accounting\Period;
use App\Models\Workspace;
use App\Services\Accounting\OpeningBalances;
use Illuminate\Http\Request;

class OpeningBalanceController extends AccountingController
{
    /** The opening-balance entry sheet (or the locked view once posted). */
    public function edit()
    {
        $workspace = $this->requireWorkspace();
        if (! $this->isSetUp($workspace)) {
            return redirect()->route('accounting.dashboard')->with('error', __('app.accounting.setup_required'));
        }

        $accounts = Account::forWorkspace($workspace)->where('is_active', true)->orderBy('code')->get();

        return view('accounting.opening-balances', [
            'accountsByType' => $accounts->groupBy('type'),
            'existing' => OpeningBalances::existing($workspace),
            'openingDate' => $this->openingDate($workspace),
        ]);
    }

    public function store(Request $request)
    {
        $workspace = $this->requireWorkspace();
        $this->assertPoster($workspace);
        if (! $this->isSetUp($workspace)) {
            return redirect()->route('accounting.dashboard')->with('error', __('app.accounting.setup_required'));
        }

        $data = $request->validate([
            'balances' => ['array'],
            'balances.*.debit' => ['nullable', 'numeric', 'min:0', 'max:99999999999'],
            'balances.*.credit' => ['nullable', 'numeric', 'min:0', 'max:99999999999'],
        ]);

        try {
            OpeningBalances::post($workspace, $data['balances'] ?? [], $this->openingDate($workspace));
        } catch (LedgerException | UnbalancedJournalException | ClosedPeriodException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('accounting.reports')->with('status', __('app.accounting.opening_done'));
    }

    /** Opening balances are posted on the first day of the earliest open period. */
    private function openingDate(Workspace $workspace): string
    {
        $period = Period::forWorkspace($workspace)
            ->where('status', Period::STATUS_OPEN)
            ->orderBy('starts_on')
            ->first();

        abort_unless($period, 422, __('app.accounting.no_open_period'));

        return $period->starts_on->toDateString();
    }
}
