<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskProject;
use App\Services\CurrentWorkspace;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskProjectController extends Controller
{
    public function index()
    {
        $workspace = CurrentWorkspace::get();
        $projects = collect();

        if ($workspace) {
            $projects = TaskProject::where('workspace_id', $workspace->id)
                ->withCount([
                    'tasks',
                    'tasks as done_count' => fn ($q) => $q->where('status', 'done'),
                ])
                ->orderBy('sort_order')
                ->orderByDesc('id')
                ->get();
        }

        return view('tasks.index', compact('projects', 'workspace'));
    }

    public function store(Request $request)
    {
        $workspace = CurrentWorkspace::get();
        abort_unless($workspace, 422, __('app.workspaces.no_workspace'));

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:1000',
            'color' => ['nullable', Rule::in(TaskProject::COLORS)],
        ]);

        $project = TaskProject::create([
            'workspace_id' => $workspace->id,
            'user_id' => auth()->id(),
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'color' => $data['color'] ?? 'brand',
            'sort_order' => (int) TaskProject::where('workspace_id', $workspace->id)->max('sort_order') + 1,
        ]);

        return redirect()->route('tasks.projects.show', $project)
            ->with('status', __('app.tasks.project_created'));
    }

    public function show(TaskProject $project)
    {
        $this->authorizeProject($project);

        $project->load([
            'tasks.items',
            'tasks.labels',
            'tasks.assignee:id,name',
        ]);

        // Group tasks into the fixed Kanban columns.
        $columns = collect(Task::STATUSES)->mapWithKeys(fn ($status) => [
            $status => $project->tasks->where('status', $status)->values(),
        ]);

        $members = $project->workspace->members()->orderBy('name')->get(['users.id', 'users.name']);
        $labels = $project->workspace->taskLabels()->orderBy('name')->get();

        return view('tasks.show', compact('project', 'columns', 'members', 'labels'));
    }

    public function update(Request $request, TaskProject $project)
    {
        $this->authorizeProject($project);

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'description' => 'nullable|string|max:1000',
            'color' => ['nullable', Rule::in(TaskProject::COLORS)],
        ]);

        $project->update([
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'color' => $data['color'] ?? $project->color,
        ]);

        return redirect()->route('tasks.projects.show', $project)
            ->with('status', __('app.tasks.project_updated'));
    }

    public function destroy(TaskProject $project)
    {
        $this->authorizeProject($project);
        $project->delete();

        return redirect()->route('tasks.index')
            ->with('status', __('app.tasks.project_deleted'));
    }

    private function authorizeProject(TaskProject $project): void
    {
        abort_unless($project->canBeAccessedBy(auth()->user()), 403);
    }
}
