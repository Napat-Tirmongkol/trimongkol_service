<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use App\Services\AuditLog;
use App\Services\CurrentWorkspace;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WorkspaceController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $current = CurrentWorkspace::get($user);

        $workspaces = $user->workspaces()
            ->withCount(['members', 'classrooms'])
            ->orderBy('name')
            ->get();

        return view('workspaces.index', compact('workspaces', 'current'));
    }

    public function create()
    {
        return view('workspaces.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:80',
        ]);

        $workspace = Workspace::create([
            'name' => $data['name'],
            'slug' => self::uniqueSlugFor($data['name']),
        ]);

        WorkspaceMember::create([
            'workspace_id' => $workspace->id,
            'user_id' => $request->user()->id,
            'role' => 'owner',
            'joined_at' => now(),
        ]);

        CurrentWorkspace::set($workspace);

        return redirect()->route('workspaces.settings', $workspace)
            ->with('status', __('app.workspaces.created'));
    }

    public function switchTo(Request $request, Workspace $workspace)
    {
        $user = $request->user();
        abort_unless($workspace->hasMember($user), 403);

        CurrentWorkspace::set($workspace);

        return redirect()->route('dashboard')
            ->with('status', __('app.workspaces.switched', ['name' => $workspace->name]));
    }

    public function settings(Request $request, Workspace $workspace)
    {
        $user = $request->user();
        $role = $workspace->roleFor($user);
        abort_unless($role, 403);

        $members = $workspace->memberships()
            ->with('user:id,name,email')
            ->orderByRaw("CASE role WHEN 'owner' THEN 1 WHEN 'admin' THEN 2 ELSE 3 END")
            ->orderBy('joined_at')
            ->get();

        return view('workspaces.settings', compact('workspace', 'members', 'role'));
    }

    public function update(Request $request, Workspace $workspace)
    {
        $this->ensureManager($workspace, $request->user());

        $data = $request->validate([
            'name' => 'required|string|max:80',
        ]);

        $workspace->update(['name' => $data['name']]);

        return back()->with('status', __('app.workspaces.updated'));
    }

    public function destroy(Request $request, Workspace $workspace)
    {
        $user = $request->user();
        abort_unless($workspace->isOwnedBy($user), 403);

        $request->validate(['password' => 'required|current_password']);

        // Don't strand the user if they're deleting their current workspace.
        if (optional(CurrentWorkspace::get($user))->id === $workspace->id) {
            CurrentWorkspace::clear();
        }

        $label = $workspace->name;
        $workspace->delete();

        AuditLog::record('workspace.delete', null, $label);

        return redirect()->route('workspaces.index')
            ->with('status', __('app.workspaces.deleted', ['name' => $label]));
    }

    public function inviteMember(Request $request, Workspace $workspace)
    {
        $this->ensureManager($workspace, $request->user());

        $data = $request->validate([
            'email' => 'required|email|max:160',
            'role' => 'required|in:admin,member',
        ]);

        $invitee = User::where('email', $data['email'])->first();
        if (! $invitee) {
            throw ValidationException::withMessages([
                'email' => __('app.workspaces.invite_no_user'),
            ]);
        }

        if ($workspace->hasMember($invitee)) {
            throw ValidationException::withMessages([
                'email' => __('app.workspaces.invite_already_member'),
            ]);
        }

        WorkspaceMember::create([
            'workspace_id' => $workspace->id,
            'user_id' => $invitee->id,
            'role' => $data['role'],
            'joined_at' => now(),
        ]);

        AuditLog::record('workspace.invite_member', $workspace, $invitee->email, [
            'role' => $data['role'],
        ]);

        return back()->with('status', __('app.workspaces.invite_sent', ['email' => $invitee->email]));
    }

    public function removeMember(Request $request, Workspace $workspace, User $user)
    {
        $actor = $request->user();
        $this->ensureManager($workspace, $actor);

        if ($workspace->isOwnedBy($user)) {
            throw ValidationException::withMessages([
                'member' => __('app.workspaces.cannot_remove_owner'),
            ]);
        }

        WorkspaceMember::where('workspace_id', $workspace->id)
            ->where('user_id', $user->id)
            ->delete();

        AuditLog::record('workspace.remove_member', $workspace, $user->email);

        return back()->with('status', __('app.workspaces.member_removed', ['name' => $user->name]));
    }

    public function leave(Request $request, Workspace $workspace)
    {
        $user = $request->user();

        if ($workspace->isOwnedBy($user)) {
            return back()->with('error', __('app.workspaces.owner_cannot_leave'));
        }

        abort_unless($workspace->hasMember($user), 403);

        WorkspaceMember::where('workspace_id', $workspace->id)
            ->where('user_id', $user->id)
            ->delete();

        if (optional(CurrentWorkspace::get($user))->id === $workspace->id) {
            CurrentWorkspace::clear();
        }

        return redirect()->route('workspaces.index')
            ->with('status', __('app.workspaces.left', ['name' => $workspace->name]));
    }

    private function ensureManager(Workspace $workspace, User $user): void
    {
        $role = $workspace->roleFor($user);
        abort_unless(in_array($role, ['owner', 'admin'], true), 403);
    }

    private static function uniqueSlugFor(string $name): string
    {
        $base = Str::slug($name) ?: 'workspace';
        $slug = $base;
        $i = 1;
        while (Workspace::where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$i);
        }
        return $slug;
    }
}
