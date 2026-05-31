<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Existing workspaces predate the subscription model — drop them on
     * the Free plan in 'active' state so nothing immediately locks them
     * out. New workspaces (post-migration) will start with a 14-day
     * Basic trial via the Workspace::created listener.
     */
    public function up(): void
    {
        $now = now();
        $orphans = DB::table('workspaces')
            ->leftJoin('subscriptions', 'subscriptions.workspace_id', '=', 'workspaces.id')
            ->whereNull('subscriptions.id')
            ->pluck('workspaces.id');

        foreach ($orphans as $workspaceId) {
            DB::table('subscriptions')->insert([
                'workspace_id' => $workspaceId,
                'plan_key' => 'free',
                'status' => 'active',
                'trial_ends_at' => null,
                'current_period_end' => null,
                'cancelled_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }
    }

    public function down(): void
    {
        DB::table('subscriptions')->delete();
    }
};
