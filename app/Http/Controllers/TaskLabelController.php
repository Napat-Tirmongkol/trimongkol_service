<?php

namespace App\Http\Controllers;

use App\Models\TaskLabel;
use App\Models\WorkspaceMember;
use App\Services\CurrentWorkspace;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskLabelController extends Controller
{
    public function store(Request $request)
    {
        $workspace = CurrentWorkspace::get();
        abort_unless($workspace, 422, __('app.workspaces.no_workspace'));

        $data = $request->validate([
            'name' => 'required|string|max:50',
            'color' => ['nullable', Rule::in(TaskLabel::COLORS)],
        ]);

        $workspace->taskLabels()->create([
            'name' => $data['name'],
            'color' => $data['color'] ?? 'slate',
        ]);

        return back()->with('status', __('app.tasks.label_created'));
    }

    public function destroy(TaskLabel $label)
    {
        $isMember = WorkspaceMember::query()
            ->where('workspace_id', $label->workspace_id)
            ->where('user_id', auth()->id())
            ->exists();
        abort_unless($isMember, 403);

        $label->delete();

        return back()->with('status', __('app.tasks.label_deleted'));
    }
}
