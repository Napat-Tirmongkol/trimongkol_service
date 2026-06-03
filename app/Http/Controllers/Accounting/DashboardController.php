<?php

namespace App\Http\Controllers\Accounting;

use App\Models\Accounting\Approval;
use App\Models\Accounting\Bill;
use App\Models\Accounting\Invoice;
use App\Models\Accounting\Journal;
use App\Models\Accounting\JournalLine;
use App\Models\Accounting\Partner;
use App\Services\Accounting\Money;
use App\Services\Accounting\Receipts;
use App\Services\Accounting\Reporting;
use App\Services\Accounting\SupplierPayments;
use App\Services\Accounting\TaxReporting;

class DashboardController extends AccountingController
{
    public function index()
    {
        $workspace = $this->currentWorkspace();
        if (! $workspace) {
            return view('accounting.dashboard', ['workspace' => null, 'isSetUp' => false]);
        }

        // First-run: send a brand-new workspace through the setup wizard.
        if (! $workspace->isOnboarded() && ! $this->isSetUp($workspace)) {
            return redirect()->route('accounting.onboarding');
        }

        $isSetUp = $this->isSetUp($workspace);
        $finance = null;
        $recent = collect();
        $pendingApprovalsCount = 0;
        $canPost = auth('accounting')->user()?->canPost() ?? false;

        if ($isSetUp) {
            $recent = Invoice::forWorkspace($workspace)->with('partner')
                ->latest('issue_date')->latest('id')->limit(8)->get();

            $finance = $this->finance($workspace);

            if ($canPost) {
                $pendingApprovalsCount = Approval::forWorkspace($workspace)->pending()->count();
            }
        }

        return view('accounting.dashboard', compact('workspace', 'isSetUp', 'finance', 'recent', 'canPost', 'pendingApprovalsCount'));
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
}
