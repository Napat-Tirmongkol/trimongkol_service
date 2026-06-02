<?php

namespace App\Http\Controllers\Admin\Products;

use App\Http\Controllers\Controller;
use App\Models\Accounting\Account;
use App\Models\Accounting\AccountingUser;
use App\Models\Accounting\Journal;
use App\Models\Workspace;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

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

    public function users()
    {
        $workspaces = Workspace::with('accountingUsers')->orderBy('name')->get();

        return view('admin.products.accounting.users', compact('workspaces'));
    }

    public function storeUser(Request $request)
    {
        $data = $request->validate([
            'workspace_id' => ['required', 'exists:workspaces,id'],
            'name'         => ['required', 'string', 'max:100'],
            'email'        => ['required', 'email', 'max:191'],
            'password'     => ['required', 'string', 'min:8'],
            'role'         => ['required', 'in:owner,admin,staff'],
        ]);

        $exists = AccountingUser::where('workspace_id', $data['workspace_id'])
            ->where('email', $data['email'])
            ->exists();
        abort_if($exists, 422, __('app.accounting.user_email_taken'));

        AccountingUser::create([
            'workspace_id' => $data['workspace_id'],
            'name'         => $data['name'],
            'email'        => $data['email'],
            'password'     => Hash::make($data['password']),
            'role'         => $data['role'],
        ]);

        return back()->with('status', __('app.accounting.user_created'));
    }

    public function destroyUser(AccountingUser $accountingUser)
    {
        $accountingUser->delete();

        return back()->with('status', __('app.accounting.user_deleted'));
    }
}
