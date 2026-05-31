<?php

/**
 * Free-launch settings. While free mode is on, the paid tiers in
 * config/plans.php are dormant and every workspace shares the generous
 * "launch" caps below (to curb abuse). Toggle / tune live in /admin/site
 * (keys: billing.free_mode, billing.launch_*). See App\Services\Billing.
 */

return [
    // Default when the billing.free_mode setting is unset. true = free launch.
    'free_mode_default' => true,

    // Per-workspace caps applied to everyone during the free launch.
    // null = unlimited.
    'launch' => [
        'limits' => [
            'max_classrooms' => 15,
            'max_members' => 5,
            'max_students_per_classroom' => 80,
        ],
    ],
];
