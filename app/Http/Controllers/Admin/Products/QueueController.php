<?php

namespace App\Http\Controllers\Admin\Products;

use App\Http\Controllers\Controller;
use App\Models\Queue;
use App\Models\QueueTicket;
use App\Services\AuditLog;
use Illuminate\Http\Request;

class QueueController extends Controller
{
    public function dashboard()
    {
        $now = now();

        $stats = [
            'queues' => Queue::count(),
            'queues_week' => Queue::where('created_at', '>=', $now->copy()->subDays(7))->count(),
            'tickets' => QueueTicket::count(),
            'tickets_today' => QueueTicket::whereDate('created_at', $now->toDateString())->count(),
        ];

        $recent = Queue::query()
            ->with('user:id,name,email', 'workspace:id,name')
            ->withCount(['tickets as waiting_count' => fn ($q) => $q->where('status', 'waiting')])
            ->latest()
            ->limit(8)
            ->get();

        return view('admin.products.queue.dashboard', compact('stats', 'recent'));
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $queues = Queue::query()
            ->with('user:id,name,email', 'workspace:id,name')
            ->withCount([
                'counters',
                'tickets as waiting_count' => fn ($qb) => $qb->where('status', 'waiting'),
            ])
            ->when($q !== '', fn ($qb) => $qb->where(fn ($w) =>
                $w->where('name', 'like', "%{$q}%")
                  ->orWhereHas('user', fn ($u) => $u->where('email', 'like', "%{$q}%"))
            ))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.products.queue.index', compact('queues', 'q'));
    }

    public function destroy(Queue $queue)
    {
        $label = $queue->name . ' (' . optional($queue->user)->email . ')';
        $meta = [
            'tickets' => $queue->tickets()->count(),
            'owner_id' => $queue->user_id,
        ];

        $queue->delete();

        AuditLog::record('queue.delete', null, $label, $meta);

        return redirect()->route('admin.queue.index')
            ->with('status', __('app.admin.products.queue.deleted'));
    }
}
