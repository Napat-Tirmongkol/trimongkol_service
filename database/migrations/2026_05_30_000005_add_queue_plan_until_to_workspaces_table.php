<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            // When a paid queue plan expires (null = no expiry, e.g. free or an
            // admin-granted plan). Past this, QueuePlan drops back to free.
            $table->timestamp('queue_plan_until')->nullable()->after('queue_plan');
        });
    }

    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropColumn('queue_plan_until');
        });
    }
};
