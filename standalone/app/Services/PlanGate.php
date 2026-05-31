<?php

namespace App\Services;

use App\Models\Workspace;

/**
 * Plan limit enforcement. Each method returns null if the action is allowed,
 * otherwise a translated reason string suitable for showing to the user.
 *
 * While free-launch mode is on (see App\Services\Billing) the paid-plan limits
 * are replaced by a single generous "launch" cap shared by every workspace.
 */
class PlanGate
{
    public static function reasonCannotAddMember(Workspace $workspace): ?string
    {
        $free = Billing::freeMode();
        $plan = $workspace->currentPlan();
        $limit = $free ? Billing::launchLimit('max_members') : $plan->limit('max_members');
        if ($limit === null) {
            return null;
        }
        if ($workspace->memberships()->count() >= $limit) {
            return $free
                ? __('app.plans.launch_limit_members', ['limit' => $limit])
                : __('app.plans.limit_members', ['plan' => $plan->name, 'limit' => $limit]);
        }
        return null;
    }
}
