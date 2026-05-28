<?php

namespace App\Http\Controllers;

use App\Models\Workspace;
use App\Services\CurrentWorkspace;
use Illuminate\Http\Request;

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

    public function switchTo(Request $request, Workspace $workspace)
    {
        $user = $request->user();
        abort_unless($workspace->hasMember($user), 403);

        CurrentWorkspace::set($workspace);

        return redirect()->route('dashboard')
            ->with('status', __('app.workspaces.switched', ['name' => $workspace->name]));
    }
}
