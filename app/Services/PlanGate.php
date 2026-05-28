<?php

namespace App\Services;

use App\Models\Classroom;
use App\Models\Workspace;

/**
 * Plan limit enforcement. Each method returns null if the action is
 * allowed, otherwise a translated reason string suitable for showing
 * to the user (e.g. as a flash toast).
 */
class PlanGate
{
    public static function reasonCannotAddClassroom(Workspace $workspace): ?string
    {
        $plan = $workspace->currentPlan();
        $limit = $plan->limit('max_classrooms');
        if ($limit === null) {
            return null;
        }
        $count = $workspace->classrooms()->count();
        if ($count >= $limit) {
            return __('app.plans.limit_classrooms', [
                'plan' => $plan->name,
                'limit' => $limit,
            ]);
        }
        return null;
    }

    public static function reasonCannotAddMember(Workspace $workspace): ?string
    {
        $plan = $workspace->currentPlan();
        $limit = $plan->limit('max_members');
        if ($limit === null) {
            return null;
        }
        $count = $workspace->memberships()->count();
        if ($count >= $limit) {
            return __('app.plans.limit_members', [
                'plan' => $plan->name,
                'limit' => $limit,
            ]);
        }
        return null;
    }

    public static function reasonCannotAddStudent(Classroom $classroom, int $newRows = 1): ?string
    {
        $workspace = $classroom->workspace;
        if (! $workspace) {
            return null;
        }
        $plan = $workspace->currentPlan();
        $limit = $plan->limit('max_students_per_classroom');
        if ($limit === null) {
            return null;
        }
        $count = $classroom->students()->count();
        if ($count + $newRows > $limit) {
            return __('app.plans.limit_students', [
                'plan' => $plan->name,
                'limit' => $limit,
            ]);
        }
        return null;
    }
}
