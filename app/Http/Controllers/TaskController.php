<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskProject;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TaskController extends Controller
{
    public function store(Request $request, TaskProject $project)
    {
        $this->authorizeProject($project);

        $data = $this->validateTask($request, $project->workspace_id);

        $status = $data['status'] ?? 'todo';

        $task = Task::create([
            'task_project_id' => $project->id,
            'workspace_id' => $project->workspace_id,
            'user_id' => auth()->id(),
            'assignee_id' => $data['assignee_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => $status,
            'priority' => $data['priority'] ?? 'normal',
            'due_date' => $data['due_date'] ?? null,
            'completed_at' => $status === 'done' ? now() : null,
            'sort_order' => (int) Task::where('task_project_id', $project->id)
                ->where('status', $status)->max('sort_order') + 1,
        ]);

        $task->labels()->sync($data['labels'] ?? []);

        return redirect()->route('tasks.projects.show', $project)
            ->with('status', __('app.tasks.task_created'));
    }

    public function update(Request $request, Task $task)
    {
        $this->authorizeTask($task);

        $data = $this->validateTask($request, $task->workspace_id);

        $status = $data['status'] ?? $task->status;

        $task->update([
            'assignee_id' => $data['assignee_id'] ?? null,
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'status' => $status,
            'priority' => $data['priority'] ?? 'normal',
            'due_date' => $data['due_date'] ?? null,
            'completed_at' => $status === 'done' ? ($task->completed_at ?? now()) : null,
        ]);

        $task->labels()->sync($data['labels'] ?? []);

        return redirect()->route('tasks.projects.show', [$task->task_project_id, 'task' => $task->id])
            ->with('status', __('app.tasks.task_updated'));
    }

    public function destroy(Task $task)
    {
        $this->authorizeTask($task);
        $projectId = $task->task_project_id;
        $task->delete();

        return redirect()->route('tasks.projects.show', $projectId)
            ->with('status', __('app.tasks.task_deleted'));
    }

    /**
     * Drag-and-drop persistence. Receives the new status for the dragged card
     * plus the full ordered list of task ids now in that column, and rewrites
     * sort_order to match. Returns JSON for the board's fetch() call.
     */
    public function move(Request $request, Task $task)
    {
        $this->authorizeTask($task);

        $data = $request->validate([
            'status' => ['required', Rule::in(Task::STATUSES)],
            'order' => 'array',
            'order.*' => 'integer',
        ]);

        $task->update([
            'status' => $data['status'],
            'completed_at' => $data['status'] === 'done' ? ($task->completed_at ?? now()) : null,
        ]);

        foreach (($data['order'] ?? []) as $position => $id) {
            Task::where('task_project_id', $task->task_project_id)
                ->where('id', $id)
                ->update(['sort_order' => $position]);
        }

        return response()->json(['ok' => true]);
    }

    /** Quick done/undone toggle from the card or list view. */
    public function toggleComplete(Request $request, Task $task)
    {
        $this->authorizeTask($task);

        $done = ! $task->isDone();
        $task->update([
            'status' => $done ? 'done' : 'todo',
            'completed_at' => $done ? now() : null,
        ]);

        if ($request->wantsJson()) {
            return response()->json(['ok' => true, 'status' => $task->status]);
        }

        return back()->with('status', __('app.tasks.task_updated'));
    }

    private function validateTask(Request $request, int $workspaceId): array
    {
        return $request->validate([
            'title' => 'required|string|max:200',
            'description' => 'nullable|string|max:5000',
            'status' => ['nullable', Rule::in(Task::STATUSES)],
            'priority' => ['nullable', Rule::in(Task::PRIORITIES)],
            'due_date' => 'nullable|date',
            'assignee_id' => ['nullable', Rule::exists('workspace_members', 'user_id')->where('workspace_id', $workspaceId)],
            'labels' => 'nullable|array',
            'labels.*' => ['integer', Rule::exists('task_labels', 'id')->where('workspace_id', $workspaceId)],
        ]);
    }

    private function authorizeProject(TaskProject $project): void
    {
        abort_unless($project->canBeAccessedBy(auth()->user()), 403);
    }

    private function authorizeTask(Task $task): void
    {
        abort_unless($task->canBeAccessedBy(auth()->user()), 403);
    }
}
