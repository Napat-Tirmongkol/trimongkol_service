<?php

namespace App\Http\Controllers\Accounting;

use App\Models\Accounting\AccountingUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class AccountingUserController extends AccountingController
{
    public function index()
    {
        $workspace = $this->requireWorkspace();
        abort_unless(auth('accounting')->user()->canPost(), 403);

        $users = AccountingUser::where('workspace_id', $workspace->id)
            ->orderBy('name')
            ->get();

        return view('accounting.users.index', compact('workspace', 'users'));
    }

    public function store(Request $request)
    {
        $workspace = $this->requireWorkspace();
        abort_unless(auth('accounting')->user()->isOwner(), 403);

        $data = $request->validate([
            'name'     => ['required', 'string', 'max:100'],
            'email'    => ['required', 'email', 'max:191'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'role'     => ['required', 'in:owner,admin,staff'],
        ]);

        $exists = AccountingUser::where('workspace_id', $workspace->id)
            ->where('email', $data['email'])
            ->exists();

        abort_if($exists, 422, __('app.accounting.user_email_taken'));

        AccountingUser::create([
            'workspace_id' => $workspace->id,
            'name'         => $data['name'],
            'email'        => $data['email'],
            'password'     => Hash::make($data['password']),
            'role'         => $data['role'],
        ]);

        return back()->with('status', __('app.accounting.user_created'));
    }

    public function update(Request $request, AccountingUser $accountingUser)
    {
        $workspace = $this->requireWorkspace();
        abort_unless(auth('accounting')->user()->isOwner(), 403);
        abort_unless($accountingUser->workspace_id === $workspace->id, 404);

        $data = $request->validate([
            'name'      => ['required', 'string', 'max:100'],
            'role'      => ['required', 'in:owner,admin,staff'],
            'is_active' => ['boolean'],
        ]);

        $accountingUser->update($data);

        return back()->with('status', __('app.accounting.user_updated'));
    }

    public function resetPassword(Request $request, AccountingUser $accountingUser)
    {
        $workspace = $this->requireWorkspace();
        abort_unless(auth('accounting')->user()->isOwner(), 403);
        abort_unless($accountingUser->workspace_id === $workspace->id, 404);

        $data = $request->validate([
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $accountingUser->update(['password' => Hash::make($data['password'])]);

        return back()->with('status', __('app.accounting.password_reset_done'));
    }

    public function destroy(AccountingUser $accountingUser)
    {
        $workspace = $this->requireWorkspace();
        abort_unless(auth('accounting')->user()->isOwner(), 403);
        abort_unless($accountingUser->workspace_id === $workspace->id, 404);
        abort_if($accountingUser->id === auth('accounting')->id(), 422, __('app.accounting.cannot_delete_self'));

        $accountingUser->delete();

        return back()->with('status', __('app.accounting.user_deleted'));
    }
}
