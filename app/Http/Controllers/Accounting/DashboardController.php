<?php

namespace App\Http\Controllers\Accounting;

use App\Models\Accounting\Bill;
use App\Models\Accounting\Invoice;
use App\Models\Accounting\Journal;
use App\Models\Accounting\JournalLine;
use App\Models\Accounting\Partner;
use App\Models\Accounting\Period;
use App\Services\Accounting\ChartOfAccounts;
use App\Services\Accounting\Money;
use App\Services\Accounting\Receipts;
use App\Services\Accounting\Reporting;
use App\Services\Accounting\SupplierPayments;
use App\Services\Accounting\TaxCodes;
use App\Services\Accounting\TaxReporting;
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
        $finance = null;
        $recent = collect();

        if ($isSetUp) {
            $recent = Invoice::forWorkspace($workspace)->with('partner')
                ->latest('issue_date')->latest('id')->limit(8)->get();

            $finance = $this->finance($workspace);
        }

        return view('accounting.dashboard', compact('workspace', 'isSetUp', 'finance', 'recent'));
    }

    /**
     * The numbers a front-office runs on day to day: cash on hand, who owes us
     * and who we owe, this month's profit, the taxes building up for filing,
     * and the documents still waiting to be dealt with.
     */
    private function finance($workspace): array
    {
        $today = now()->toDateString();
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        // Receivable / payable: the unpaid remainder of every open document.
        $ar = Invoice::forWorkspace($workspace)
            ->whereIn('status', [Invoice::STATUS_ISSUED, Invoice::STATUS_PARTIAL])->get()
            ->reduce(fn (int $c, Invoice $i): int => $c + Money::toMinor(Receipts::outstanding($i)), 0);

        $ap = Bill::forWorkspace($workspace)
            ->whereIn('status', [Bill::STATUS_ISSUED, Bill::STATUS_PARTIAL])->get()
            ->reduce(fn (int $c, Bill $b): int => $c + Money::toMinor(SupplierPayments::outstanding($b)), 0);

        // Overdue: open invoices past their due date — the ones to chase.
        $overdue = Invoice::forWorkspace($workspace)
            ->whereIn('status', [Invoice::STATUS_ISSUED, Invoice::STATUS_PARTIAL])
            ->whereNotNull('due_date')->whereDate('due_date', '<', $today)->get();
        $overdueAmount = $overdue->reduce(fn (int $c, Invoice $i): int => $c + Money::toMinor(Receipts::outstanding($i)), 0);

        // Mid-month VAT (output − input) and WHT withheld, both for filing.
        $vatSales = TaxReporting::vatSales($workspace, $monthStart, $monthEnd);
        $vatPurchases = TaxReporting::vatPurchases($workspace, $monthStart, $monthEnd);
        $vatNet = Money::toMinor($vatSales['vat_total']) - Money::toMinor($vatPurchases['vat_total']);
        $wht = TaxReporting::wht($workspace, $monthStart, $monthEnd);

        $pnl = Reporting::profitAndLoss($workspace, $monthStart, $monthEnd);

        return [
            'cash' => Money::fromMinor($this->cashOnHand($workspace)),
            'ar' => Money::fromMinor($ar),
            'ap' => Money::fromMinor($ap),
            'net_profit' => $pnl['net_profit'],
            'vat_net' => Money::fromMinor($vatNet),
            'wht_pnd3' => $wht['pnd3_total'],
            'wht_pnd53' => $wht['pnd53_total'],
            'draft_invoices' => Invoice::forWorkspace($workspace)->where('status', Invoice::STATUS_DRAFT)->count(),
            'draft_bills' => Bill::forWorkspace($workspace)->where('status', Bill::STATUS_DRAFT)->count(),
            'overdue_count' => $overdue->count(),
            'overdue_amount' => Money::fromMinor($overdueAmount),
            'month_label' => now()->format('m/Y'),
        ];
    }

    /** Net posted balance of the cash and bank accounts (codes 1111 / 1113). */
    private function cashOnHand($workspace): int
    {
        $row = JournalLine::forWorkspace($workspace)
            ->whereHas('journal', fn ($q) => $q->where('status', Journal::STATUS_POSTED))
            ->whereHas('account', fn ($q) => $q->where(function ($w) {
                $w->where('code', 'like', '1111%')->orWhere('code', 'like', '1113%');
            }))
            ->selectRaw('COALESCE(SUM(debit), 0) as d, COALESCE(SUM(credit), 0) as c')
            ->first();

        return Money::toMinor((string) ($row->d ?? 0)) - Money::toMinor((string) ($row->c ?? 0));
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
