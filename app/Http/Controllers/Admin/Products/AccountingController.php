<?php

namespace App\Http\Controllers\Admin\Products;

use App\Http\Controllers\Controller;
use App\Models\Accounting\Account;
use App\Models\Accounting\Journal;

class AccountingController extends Controller
{
    /**
     * Platform-wide oversight of the accounting product — books across every
     * workspace. Tenants keep their own books in the (Phase 2) /accounting app;
     * this dashboard is read-only moderation, like the queue/scanner hubs.
     */
    public function dashboard()
    {
        $stats = [
            'accounts' => Account::count(),
            'workspaces' => Account::distinct()->count('workspace_id'),
            'journals' => Journal::count(),
            'posted' => Journal::where('status', Journal::STATUS_POSTED)->count(),
            'drafts' => Journal::where('status', Journal::STATUS_DRAFT)->count(),
        ];

        $recent = Journal::query()
            ->with('workspace:id,name')
            ->withSum('lines as total', 'debit')
            ->latest('date')
            ->latest('id')
            ->limit(10)
            ->get();

        return view('admin.products.accounting.dashboard', compact('stats', 'recent'));
    }
}
