<?php

namespace App\Services;

use App\Models\Setting;

/**
 * Free-launch mode. While `billing.free_mode` is on, the paid tiers are
 * dormant: every workspace shares the same generous "launch" caps (to keep
 * abuse in check) and the trial / upgrade UI is hidden. Flip it off in
 * /admin/site once real billing is wired up and the Plan / Subscription /
 * PlanGate machinery resumes exactly as configured.
 */
class Billing
{
    /** Limit dimensions the launch caps cover. */
    public const LIMITS = ['max_classrooms', 'max_members', 'max_students_per_classroom'];

    public static function freeMode(): bool
    {
        $v = Setting::get('billing.free_mode');
        if ($v === null) {
            return (bool) config('billing.free_mode_default', true);
        }
        return $v === '1';
    }

    /** Per-workspace cap applied to everyone during the free launch. null = unlimited. */
    public static function launchLimit(string $name): ?int
    {
        $override = Setting::get("billing.launch_{$name}");
        if ($override !== null && $override !== '') {
            return (int) $override;
        }
        $cfg = config("billing.launch.limits.{$name}");
        return $cfg === null ? null : (int) $cfg;
    }

    /** @return array<string, int|null> */
    public static function launchLimits(): array
    {
        $out = [];
        foreach (self::LIMITS as $name) {
            $out[$name] = self::launchLimit($name);
        }
        return $out;
    }
}
