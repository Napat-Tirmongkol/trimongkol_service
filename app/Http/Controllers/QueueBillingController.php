<?php

namespace App\Http\Controllers;

use App\Models\QueuePayment;
use App\Models\Workspace;
use App\Services\CurrentWorkspace;
use App\Services\QueuePlan;
use App\Services\SlipVerifier;
use App\Support\PromptPay;
use Illuminate\Http\Request;

class QueueBillingController extends Controller
{
    public static function enabled(): bool
    {
        return setting('queue_billing.enabled') === '1' && filled(setting('queue_billing.promptpay'));
    }

    public function show()
    {
        $workspace = CurrentWorkspace::get();
        abort_unless($workspace, 422, __('app.workspaces.no_workspace'));

        $plans = collect(QueuePlan::PURCHASABLE)
            ->mapWithKeys(fn ($k) => [$k => config("queue-plans.{$k}")])
            ->all();

        $pending = QueuePayment::where('workspace_id', $workspace->id)
            ->where('status', QueuePayment::STATUS_PENDING)
            ->latest()
            ->first();

        return view('queues.billing', [
            'workspace' => $workspace,
            'plans' => $plans,
            'currentKey' => QueuePlan::storedKey($workspace),
            'expiresAt' => QueuePlan::expiresAt($workspace),
            'isExpired' => QueuePlan::isExpired($workspace),
            'billingEnabled' => self::enabled(),
            'pending' => $pending,
        ]);
    }

    public function pay(string $plan)
    {
        $workspace = CurrentWorkspace::get();
        abort_unless($workspace, 422, __('app.workspaces.no_workspace'));
        abort_unless(self::enabled(), 404);
        abort_unless(in_array($plan, QueuePlan::PURCHASABLE, true), 404);

        $price = QueuePlan::price($plan);
        $promptpay = setting('queue_billing.promptpay');
        $payload = PromptPay::payload($promptpay, $price);

        return view('queues.pay', [
            'workspace' => $workspace,
            'plan' => $plan,
            'planConfig' => config("queue-plans.{$plan}"),
            'price' => $price,
            'promptpayPayload' => $payload,
            'accountName' => setting('queue_billing.account_name'),
            'slipAuto' => SlipVerifier::enabled(),
        ]);
    }

    public function submit(Request $request, string $plan)
    {
        $workspace = CurrentWorkspace::get();
        abort_unless($workspace, 422, __('app.workspaces.no_workspace'));
        abort_unless(self::enabled(), 404);
        abort_unless(in_array($plan, QueuePlan::PURCHASABLE, true), 404);

        $request->validate([
            'slip' => 'required|image|max:5120',
        ]);

        $price = (int) QueuePlan::price($plan);
        $slip = $request->file('slip');
        $path = $slip->store("slips/{$workspace->id}", 'local');

        // No auto-verification configured → leave it for an admin to approve.
        if (! SlipVerifier::enabled()) {
            QueuePayment::create([
                'workspace_id' => $workspace->id,
                'user_id' => auth()->id(),
                'plan_key' => $plan,
                'amount' => $price,
                'status' => QueuePayment::STATUS_PENDING,
                'slip_path' => $path,
                'note' => 'manual',
            ]);

            return redirect()->route('queues.billing')->with('status', __('app.queue.billing.slip_received'));
        }

        $result = SlipVerifier::verify($slip, $price);

        if (! $result['ok']) {
            // Hard rejects: show a clear reason and don't keep the record.
            if (in_array($result['message'], ['duplicate', 'amount_mismatch'], true)) {
                return back()->with('error', __('app.queue.billing.err_'.$result['message']));
            }

            // Transient/unknown failure → keep as pending for admin review.
            QueuePayment::create([
                'workspace_id' => $workspace->id,
                'user_id' => auth()->id(),
                'plan_key' => $plan,
                'amount' => $price,
                'status' => QueuePayment::STATUS_PENDING,
                'slip_path' => $path,
                'note' => $result['message'],
                'meta' => $result['raw'] ?: null,
            ]);

            return redirect()->route('queues.billing')->with('status', __('app.queue.billing.slip_received'));
        }

        // Defence in depth on top of SlipOK's own duplicate guard.
        if ($result['trans_ref'] && QueuePayment::where('trans_ref', $result['trans_ref'])->exists()) {
            return back()->with('error', __('app.queue.billing.err_duplicate'));
        }

        $payment = QueuePayment::create([
            'workspace_id' => $workspace->id,
            'user_id' => auth()->id(),
            'plan_key' => $plan,
            'amount' => $result['amount'] ?? $price,
            'status' => QueuePayment::STATUS_VERIFIED,
            'slip_path' => $path,
            'trans_ref' => $result['trans_ref'],
            'meta' => $result['raw'] ?: null,
            'verified_at' => now(),
        ]);

        self::activate($workspace, $plan, $payment->months);

        return redirect()->route('queues.billing')->with('status', __('app.queue.billing.activated', [
            'plan' => config("queue-plans.{$plan}.name"),
        ]));
    }

    /** Apply (or extend) a paid plan by $months. Renewing before expiry stacks. */
    public static function activate(Workspace $workspace, string $plan, int $months = 1): void
    {
        $current = $workspace->queue_plan_until;
        $base = ($workspace->queue_plan === $plan && $current && $current->isFuture())
            ? $current->copy()
            : now();

        $workspace->update([
            'queue_plan' => $plan,
            'queue_plan_until' => $base->addDays(30 * max(1, $months)),
        ]);
    }
}
