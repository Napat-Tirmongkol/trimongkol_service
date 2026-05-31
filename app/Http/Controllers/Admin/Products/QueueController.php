<?php

namespace App\Http\Controllers\Admin\Products;

use App\Http\Controllers\Controller;
use App\Models\Queue;
use App\Models\QueueTicket;
use App\Models\Setting;
use App\Services\AuditLog;
use App\Services\Tts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

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

    public function updateSettings(Request $request)
    {
        $data = $request->validate([
            'provider' => 'required|in:browser,google',
            'voice' => 'nullable|string|max:60',
            'google_key' => 'nullable|string|max:300',
        ]);

        Setting::updateOrCreate(['key' => 'tts.provider'], ['value' => $data['provider']]);
        Setting::updateOrCreate(['key' => 'tts.voice'], ['value' => $data['voice'] ?: 'th-TH-Neural2-C']);

        // Only overwrite the key when a new one is typed — the form never
        // echoes the stored key back. Encrypted at rest.
        if (filled($data['google_key'] ?? null)) {
            Setting::updateOrCreate(['key' => 'tts.google_key'], ['value' => Crypt::encryptString(trim($data['google_key']))]);
        }

        AuditLog::record('queue.tts.settings', null, 'TTS provider: '.$data['provider']);

        return back()->with('status', __('app.admin.products.queue.tts_saved'));
    }

    public function testTts()
    {
        [$ok, $message] = Tts::test();

        return $ok
            ? back()->with('status', __('app.admin.products.queue.tts_test_ok'))
            : back()->with('error', __('app.admin.products.queue.tts_test_fail', ['error' => $message]));
    }
}
