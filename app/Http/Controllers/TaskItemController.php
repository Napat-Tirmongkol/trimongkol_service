<?php

namespace App\Http\Controllers;

use App\Models\Task;
use App\Models\TaskItem;
use Illuminate\Http\Request;

class TaskItemController extends Controller
{
    public function store(Request $request, Task $task)
    {
        $this->authorizeTask($task);

        $data = $request->validate([
            'title' => 'required|string|max:200',
        ]);

        $item = $task->items()->create([
            'title' => $data['title'],
            'sort_order' => (int) $task->items()->max('sort_order') + 1,
        ]);

        return response()->json([
            'id' => $item->id,
            'title' => $item->title,
            'is_done' => $item->is_done,
        ]);
    }

    public function toggle(TaskItem $item)
    {
        $this->authorizeTask($item->task);

        $item->update(['is_done' => ! $item->is_done]);

        return response()->json(['id' => $item->id, 'is_done' => $item->is_done]);
    }

    public function destroy(TaskItem $item)
    {
        $this->authorizeTask($item->task);
        $item->delete();

        return response()->json(['ok' => true]);
    }

    private function authorizeTask(Task $task): void
    {
        abort_unless($task->canBeAccessedBy(auth()->user()), 403);
    }
}
