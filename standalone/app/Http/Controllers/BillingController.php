<?php

namespace App\Http\Controllers;

use App\Models\Plan;
use App\Models\Subscription;
use App\Services\CurrentWorkspace;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Subscription billing for the current workspace. Plans are config-backed
 * (config/plans.php); a workspace holds one Subscription row. Payment-gateway
 * capture is not wired yet — selecting a paid plan records the intent and
 * points the owner at manual payment / contact, which a later gateway pass
 * (Stripe/Omise) replaces with a checkout redirect + webhook.
 */
class BillingController extends Controller
{
    public function index()
    {
        $workspace = CurrentWorkspace::get();
        abort_unless($workspace, 422, __('app.workspaces.no_workspace'));

        $subscription = $workspace->subscription;

        return view('billing.index', [
            'workspace' => $workspace,
            'plans' => Plan::all(),
            'currentKey' => $subscription?->effectivePlanKey() ?? Plan::FREE,
            'subscription' => $subscription,
        ]);
    }

    /**
     * Record the chosen plan. Free downgrades immediately; a paid plan is
     * marked pending until payment is confirmed (gateway pass will flip it to
     * active on a successful webhook). Owners only.
     */
    public function subscribe(Request $request)
    {
        $workspace = CurrentWorkspace::get();
        abort_unless($workspace, 422, __('app.workspaces.no_workspace'));
        abort_unless($workspace->isOwnedBy($request->user()), 403, __('app.billing.owner_only'));

        $data = $request->validate([
            'plan_key' => ['required', 'string', Rule::in(array_keys(config('plans')))],
        ]);

        $plan = Plan::find($data['plan_key']);
        $subscription = $workspace->subscription ?? new Subscription(['workspace_id' => $workspace->id]);

        if ($plan->isFree()) {
            $subscription->fill([
                'plan_key' => Plan::FREE,
                'status' => Subscription::STATUS_ACTIVE,
                'trial_ends_at' => null,
                'current_period_end' => null,
            ])->save();

            return redirect()->route('billing.index')->with('status', __('app.billing.switched_free'));
        }

        // Paid plan: record the selection. Until a gateway confirms payment we
        // keep the workspace usable on its current effective plan.
        $subscription->fill([
            'plan_key' => $plan->key,
            'status' => $subscription->status === Subscription::STATUS_ACTIVE
                ? Subscription::STATUS_ACTIVE
                : Subscription::STATUS_TRIAL,
            'trial_ends_at' => $subscription->trial_ends_at ?? now()->addDays(14),
        ])->save();

        return redirect()->route('billing.index')->with('status', __('app.billing.selected_paid', ['plan' => $plan->name]));
    }
}
