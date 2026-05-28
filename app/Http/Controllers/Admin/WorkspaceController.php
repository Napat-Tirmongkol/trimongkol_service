<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Workspace;
use App\Services\AuditLog;
use Illuminate\Http\Request;

class WorkspaceController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $workspaces = Workspace::query()
            ->withCount(['members', 'classrooms'])
            ->with(['memberships' => fn ($qb) => $qb->where('role', 'owner')->with('user:id,name,email')])
            ->when($q !== '', fn ($qb) => $qb->where(fn ($w) =>
                $w->where('name', 'like', "%{$q}%")
                  ->orWhere('slug', 'like', "%{$q}%")
                  ->orWhereHas('members', fn ($u) => $u->where('email', 'like', "%{$q}%"))
            ))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.workspaces.index', compact('workspaces', 'q'));
    }

    public function show(Workspace $workspace)
    {
        $workspace->loadCount(['members', 'classrooms']);
        $memberships = $workspace->memberships()
            ->with('user:id,name,email,is_admin,is_active')
            ->orderByRaw("CASE role WHEN 'owner' THEN 1 WHEN 'admin' THEN 2 ELSE 3 END")
            ->orderBy('joined_at')
            ->get();
        $classrooms = $workspace->classrooms()
            ->withCount(['students', 'assignments'])
            ->with('user:id,name,email')
            ->latest()
            ->limit(50)
            ->get();
        $pendingInvitations = $workspace->hasMany(\App\Models\WorkspaceInvitation::class)
            ->whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->orderByDesc('created_at')
            ->get();

        return view('admin.workspaces.show', compact('workspace', 'memberships', 'classrooms', 'pendingInvitations'));
    }

    public function destroy(Workspace $workspace)
    {
        $label = $workspace->name;
        $meta = [
            'slug' => $workspace->slug,
            'members' => $workspace->memberships()->count(),
            'classrooms' => $workspace->classrooms()->count(),
        ];

        $workspace->delete();

        AuditLog::record('workspace.admin_delete', null, $label, $meta);

        return redirect()->route('admin.workspaces.index')
            ->with('status', __('app.admin.workspaces.deleted', ['name' => $label]));
    }
}
