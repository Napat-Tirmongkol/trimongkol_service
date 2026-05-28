<?php

namespace App\Http\Controllers;

use App\Models\WorkspaceInvitation;
use App\Models\WorkspaceMember;
use App\Services\AuditLog;
use App\Services\CurrentWorkspace;
use App\Services\PlanGate;
use Illuminate\Http\Request;

class WorkspaceInvitationController extends Controller
{
    /**
     * Landing page for an invitation link. Branches on auth + expiry state
     * so a single URL handles guests, logged-in matching-email users,
     * logged-in mismatched-email users, and stale invites.
     */
    public function show(Request $request, string $token)
    {
        $invitation = WorkspaceInvitation::with('workspace', 'inviter')
            ->where('token', $token)
            ->firstOrFail();

        if ($invitation->isAccepted()) {
            return redirect()->route('dashboard')
                ->with('status', __('app.workspaces.invite_already_accepted'));
        }

        if ($invitation->isExpired()) {
            return view('workspaces.invitation-expired', compact('invitation'));
        }

        // Stash the invitation URL so the auth flow returns here after login/register.
        $request->session()->put('url.intended', route('workspace-invitations.show', $token));

        $user = $request->user();
        if (! $user) {
            return view('workspaces.invitation-guest', compact('invitation'));
        }

        $mismatchedEmail = strcasecmp($user->email, $invitation->email) !== 0;

        return view('workspaces.invitation-show', compact('invitation', 'mismatchedEmail'));
    }

    public function accept(Request $request, string $token)
    {
        $invitation = WorkspaceInvitation::with('workspace')->where('token', $token)->firstOrFail();
        $user = $request->user();
        abort_unless($user, 401);

        if ($invitation->isAccepted()) {
            return redirect()->route('dashboard')
                ->with('status', __('app.workspaces.invite_already_accepted'));
        }
        if ($invitation->isExpired()) {
            return redirect()->route('workspace-invitations.show', $token);
        }
        if (strcasecmp($user->email, $invitation->email) !== 0) {
            return back()->with('error', __('app.workspaces.invite_email_mismatch'));
        }

        // Skip if already a member somehow.
        if ($invitation->workspace->hasMember($user)) {
            $invitation->update([
                'accepted_at' => now(),
                'accepted_by' => $user->id,
            ]);

            return redirect()->route('dashboard');
        }

        if ($reason = PlanGate::reasonCannotAddMember($invitation->workspace)) {
            return back()->with('error', $reason);
        }

        WorkspaceMember::create([
            'workspace_id' => $invitation->workspace_id,
            'user_id' => $user->id,
            'role' => $invitation->role,
            'joined_at' => now(),
        ]);

        $invitation->update([
            'accepted_at' => now(),
            'accepted_by' => $user->id,
        ]);

        CurrentWorkspace::set($invitation->workspace);
        $request->session()->forget('url.intended');

        AuditLog::record('workspace.invite_accept', $invitation->workspace, $user->email, [
            'role' => $invitation->role,
        ]);

        return redirect()->route('dashboard')
            ->with('status', __('app.workspaces.invite_accepted', ['name' => $invitation->workspace->name]));
    }
}
