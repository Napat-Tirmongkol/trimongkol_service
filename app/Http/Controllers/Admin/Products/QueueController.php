<?php

namespace App\Http\Controllers\Admin\Products;

use App\Http\Controllers\Controller;
use App\Http\Controllers\QueueBillingController;
use App\Models\Queue;
use App\Models\QueuePayment;
use App\Models\QueueTicket;
use App\Models\Setting;
use App\Services\AuditLog;
use App\Services\SlipVerifier;
use App\Services\Tts;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Storage;

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

    public function updateBilling(Request $request)
    {
        $data = $request->validate([
            'enabled' => 'nullable|boolean',
            'method' => 'required|in:promptpay_phone,promptpay_id,bank_account',
            'promptpay' => 'nullable|string|max:30',
            'bank_name' => 'nullable|string|max:60',
            'account_no' => 'nullable|string|max:30',
            'account_name' => 'nullable|string|max:120',
            'slipok_branch' => 'nullable|string|max:60',
            'slipok_key' => 'nullable|string|max:300',
        ]);

        Setting::updateOrCreate(['key' => 'queue_billing.enabled'], ['value' => $request->boolean('enabled') ? '1' : '0']);
        Setting::updateOrCreate(['key' => 'queue_billing.method'], ['value' => $data['method']]);
        Setting::updateOrCreate(['key' => 'queue_billing.promptpay'], ['value' => trim($data['promptpay'] ?? '')]);
        Setting::updateOrCreate(['key' => 'queue_billing.bank_name'], ['value' => trim($data['bank_name'] ?? '')]);
        Setting::updateOrCreate(['key' => 'queue_billing.account_no'], ['value' => trim($data['account_no'] ?? '')]);
        Setting::updateOrCreate(['key' => 'queue_billing.account_name'], ['value' => trim($data['account_name'] ?? '')]);
        Setting::updateOrCreate(['key' => 'queue_billing.slipok_branch'], ['value' => trim($data['slipok_branch'] ?? '')]);

        if (filled($data['slipok_key'] ?? null)) {
            Setting::updateOrCreate(['key' => 'queue_billing.slipok_key'], ['value' => Crypt::encryptString(trim($data['slipok_key']))]);
        }

        AuditLog::record('queue.billing.settings', null, 'Queue billing updated');

        return back()->with('status', __('app.admin.products.queue.billing_saved'));
    }

    public function payments(Request $request)
    {
        $status = $request->query('status', 'pending');

        $payments = QueuePayment::query()
            ->with('workspace:id,name,queue_plan', 'user:id,name,email')
            ->when(in_array($status, ['pending', 'verified', 'rejected'], true), fn ($q) => $q->where('status', $status))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        return view('admin.products.queue.payments', compact('payments', 'status'));
    }

    public function approvePayment(QueuePayment $payment)
    {
        if ($payment->status !== QueuePayment::STATUS_PENDING) {
            return back()->with('error', __('app.admin.products.queue.pay_already_done'));
        }

        $payment->update(['status' => QueuePayment::STATUS_VERIFIED, 'verified_at' => now()]);
        QueueBillingController::activate($payment->workspace, $payment->plan_key, $payment->months);

        AuditLog::record('queue.payment.approve', $payment->workspace, $payment->workspace?->name, [
            'plan' => $payment->plan_key, 'amount' => $payment->amount,
        ]);

        return back()->with('status', __('app.admin.products.queue.pay_approved'));
    }

    public function rejectPayment(QueuePayment $payment)
    {
        if ($payment->status !== QueuePayment::STATUS_PENDING) {
            return back()->with('error', __('app.admin.products.queue.pay_already_done'));
        }

        $payment->update(['status' => QueuePayment::STATUS_REJECTED]);
        AuditLog::record('queue.payment.reject', $payment->workspace, $payment->workspace?->name);

        return back()->with('status', __('app.admin.products.queue.pay_rejected'));
    }

    public function slip(QueuePayment $payment)
    {
        abort_unless($payment->slip_path && Storage::disk('local')->exists($payment->slip_path), 404);

        return response(Storage::disk('local')->get($payment->slip_path), 200, [
            'Content-Type' => Storage::disk('local')->mimeType($payment->slip_path) ?: 'image/jpeg',
        ]);
    }

    /**
     * Admin tool: upload a real test-transfer slip to confirm the SlipOK key,
     * branch, and receiving account are wired correctly — without consuming the
     * slip, creating a payment, or activating a plan.
     */
    public function testSlip(Request $request)
    {
        $request->validate(['slip' => 'required|image|max:5120']);

        if (! SlipVerifier::enabled()) {
            return back()->with('error', __('app.admin.products.queue.slip_test_not_configured'));
        }

        $result = SlipVerifier::inspect($request->file('slip'));

        if (! $result['ok']) {
            // Translate the known failure tokens to an admin-facing hint; fall
            // back to the raw token for anything unmapped.
            $known = ['slipok_branch', 'slipok_auth', 'slipok_quota', 'bank_delay', 'bad_slip', 'slipok_unreachable'];
            $reason = in_array($result['message'], $known, true)
                ? __('app.admin.products.queue.slip_err_'.$result['message'])
                : $result['message'];

            return back()->with('error', __('app.admin.products.queue.slip_test_fail', ['error' => $reason]));
        }

        $summary = __('app.admin.products.queue.slip_test_summary', [
            'amount' => number_format((int) $result['amount']),
            'receiver' => $result['receiver'] ?: '—',
            'match' => $result['receiver_match']
                ? __('app.admin.products.queue.slip_test_match_ok')
                : __('app.admin.products.queue.slip_test_match_no'),
        ]);

        return $result['receiver_match']
            ? back()->with('status', $summary)
            : back()->with('error', $summary);
    }
}
