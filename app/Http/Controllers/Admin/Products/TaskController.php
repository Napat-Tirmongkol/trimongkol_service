<?php

namespace App\Http\Controllers\Admin\Products;

use App\Http\Controllers\Controller;
use App\Models\Task;
use App\Models\TaskProject;
use App\Services\AuditLog;
use Illuminate\Http\Request;

class TaskController extends Controller
{
    public function dashboard()
    {
        $now = now();

        $stats = [
            'projects' => TaskProject::count(),
            'tasks' => Task::count(),
            'tasks_done' => Task::where('status', 'done')->count(),
            'tasks_open' => Task::where('status', '!=', 'done')->count(),
            'tasks_today' => Task::whereDate('created_at', $now->toDateString())->count(),
            'workspaces' => TaskProject::distinct('workspace_id')->count('workspace_id'),
        ];

        $topProjects = TaskProject::query()
            ->with(['workspace:id,name', 'user:id,name,email'])
            ->withCount(['tasks', 'tasks as done_count' => fn ($q) => $q->where('status', 'done')])
            ->orderByDesc('tasks_count')
            ->limit(5)
            ->get();

        return view('admin.products.tasks.dashboard', compact('stats', 'topProjects'));
    }

    public function projects(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $projects = TaskProject::query()
            ->with(['workspace:id,name', 'user:id,name,email'])
            ->withCount(['tasks', 'tasks as done_count' => fn ($qb) => $qb->where('status', 'done')])
            ->when($q !== '', fn ($qb) => $qb->where(fn ($w) =>
                $w->where('name', 'like', "%{$q}%")
                  ->orWhereHas('workspace', fn ($ws) => $ws->where('name', 'like', "%{$q}%"))
                  ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$q}%"))
            ))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.products.tasks.projects', compact('projects', 'q'));
    }

    public function showProject(TaskProject $project)
    {
        $project->load(['workspace:id,name', 'user:id,name,email']);
        $project->loadCount(['tasks', 'tasks as done_count' => fn ($q) => $q->where('status', 'done')]);
        $tasks = $project->tasks()->with('assignee:id,name')->limit(50)->get();

        return view('admin.products.tasks.project_show', compact('project', 'tasks'));
    }

    public function destroyProject(TaskProject $project)
    {
        $label = $project->name . ' (' . optional($project->workspace)->name . ')';
        $meta = [
            'tasks' => $project->tasks()->count(),
            'workspace_id' => $project->workspace_id,
            'owner_id' => $project->user_id,
        ];

        $project->delete();

        AuditLog::record('task_project.delete', null, $label, $meta);

        return redirect()->route('admin.tasks.projects')
            ->with('status', __('app.admin.products.tasks.project_deleted', ['name' => $project->name]));
    }
}
