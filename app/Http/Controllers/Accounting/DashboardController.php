<?php

namespace App\Http\Controllers\Accounting;

use App\Models\Accounting\Invoice;
use App\Models\Accounting\Partner;
use App\Models\Accounting\Period;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\Accounting\Money;
use App\Services\Accounting\Receipts;
use App\Services\Accounting\TaxCodes;
use Illuminate\Support\Facades\DB;

class DashboardController extends AccountingController
{
    public function index()
    {
        $workspace = $this->currentWorkspace();
        if (! $workspace) {
            return view('accounting.dashboard', ['workspace' => null, 'isSetUp' => false]);
        }

        $isSetUp = $this->isSetUp($workspace);
        $stats = null;
        $recent = collect();

        if ($isSetUp) {
            $recent = Invoice::forWorkspace($workspace)->with('partner')
                ->latest('issue_date')->latest('id')->limit(8)->get();

            $outstanding = Invoice::forWorkspace($workspace)
                ->whereIn('status', [Invoice::STATUS_ISSUED, Invoice::STATUS_PARTIAL])
                ->get()
                ->reduce(fn (int $carry, Invoice $i): int => $carry + Money::toMinor(Receipts::outstanding($i)), 0);

            $stats = [
                'outstanding' => Money::fromMinor($outstanding),
                'invoices' => Invoice::forWorkspace($workspace)->count(),
                'partners' => Partner::forWorkspace($workspace)->count(),
            ];
        }

        return view('accounting.dashboard', compact('workspace', 'isSetUp', 'stats', 'recent'));
    }

    /** One-click bootstrap: seed the chart of accounts, tax codes, and a period. */
    public function setup()
    {
        $workspace = $this->requireWorkspace();
        $this->assertPoster($workspace);

        if ($this->isSetUp($workspace)) {
            return redirect()->route('accounting.dashboard');
        }

        DB::transaction(function () use ($workspace) {
            ChartOfAccounts::seed($workspace);
            TaxCodes::seedDefault($workspace);

            $year = now()->year;
            Period::firstOrCreate(
                ['workspace_id' => $workspace->id, 'starts_on' => "{$year}-01-01"],
                ['name' => (string) ($year + 543), 'ends_on' => "{$year}-12-31", 'status' => Period::STATUS_OPEN],
            );
        });

        return redirect()->route('accounting.dashboard')->with('status', __('app.accounting.setup_done'));
    }
}
