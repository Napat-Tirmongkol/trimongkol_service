<?php

namespace App\Http\Controllers;

use App\Models\Queue;
use App\Models\QueueCounter;
use App\Models\QueueTicket;
use App\Services\CurrentWorkspace;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class QueueController extends Controller
{
    public function index()
    {
        $workspace = CurrentWorkspace::get();
        $queues = collect();

        if ($workspace) {
            $queues = Queue::where('workspace_id', $workspace->id)
                ->withCount([
                    'counters',
                    'tickets as waiting_count' => fn ($q) => $q->where('status', 'waiting'),
                ])
                ->latest()
                ->get();
        }

        return view('queues.index', compact('queues', 'workspace'));
    }

    public function create()
    {
        return view('queues.create');
    }

    public function store(Request $request)
    {
        $workspace = CurrentWorkspace::get();
        abort_unless($workspace, 422, __('app.workspaces.no_workspace'));

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'prefix' => 'nullable|string|max:8',
        ]);

        $queue = Queue::create([
            'name' => $data['name'],
            'prefix' => Str::upper(trim($data['prefix'] ?? '')),
            'user_id' => auth()->id(),
            'workspace_id' => $workspace->id,
        ]);

        // Seed a first counter so the operator can start calling immediately.
        $queue->counters()->create(['label' => '1']);

        return redirect()->route('queues.show', $queue)
            ->with('status', __('app.queue.created'));
    }

    public function show(Queue $queue)
    {
        $this->authorizeQueue($queue);

        return view('queues.show', compact('queue'));
    }

    public function edit(Queue $queue)
    {
        $this->authorizeQueue($queue);
        $queue->load('counters');

        return view('queues.edit', compact('queue'));
    }

    public function update(Request $request, Queue $queue)
    {
        $this->authorizeQueue($queue);

        $data = $request->validate([
            'name' => 'required|string|max:120',
            'prefix' => 'nullable|string|max:8',
            'voice_enabled' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
        ]);

        $queue->update([
            'name' => $data['name'],
            'prefix' => Str::upper(trim($data['prefix'] ?? '')),
            'voice_enabled' => $request->boolean('voice_enabled'),
            'is_active' => $request->boolean('is_active'),
        ]);

        return redirect()->route('queues.edit', $queue)
            ->with('status', __('app.queue.updated'));
    }

    public function destroy(Queue $queue)
    {
        $this->authorizeQueue($queue);
        $queue->delete();

        return redirect()->route('queues.index')
            ->with('status', __('app.queue.deleted'));
    }

    public function addCounter(Request $request, Queue $queue)
    {
        $this->authorizeQueue($queue);

        $data = $request->validate([
            'label' => 'required|string|max:40',
        ]);

        $queue->counters()->create(['label' => $data['label']]);

        return back()->with('status', __('app.queue.counter_added'));
    }

    public function removeCounter(Queue $queue, QueueCounter $counter)
    {
        $this->authorizeQueue($queue);
        abort_unless($counter->queue_id === $queue->id, 404);

        $counter->delete();

        return back()->with('status', __('app.queue.counter_removed'));
    }

    public function reset(Queue $queue)
    {
        $this->authorizeQueue($queue);

        // Close out the current round: clear anyone still waiting/called and
        // restart numbering from 1. Served tickets stay for history/stats.
        QueueTicket::where('queue_id', $queue->id)
            ->whereIn('status', ['waiting', 'called', 'serving'])
            ->update(['status' => 'skipped']);

        $queue->update(['last_number' => 0]);

        return back()->with('status', __('app.queue.reset_done'));
    }

    public function poster(Queue $queue)
    {
        $this->authorizeQueue($queue);

        return view('queues.poster', compact('queue'));
    }

    private function authorizeQueue(Queue $queue): void
    {
        abort_unless($queue->canBeAccessedBy(auth()->user()), 403);
    }
}
