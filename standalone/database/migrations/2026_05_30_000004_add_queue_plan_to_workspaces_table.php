<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            // Queue product plan, billed separately from the platform/Scanner
            // subscription. See config/queue-plans.php + App\Services\QueuePlan.
            $table->string('queue_plan')->default('free')->after('slug');
        });
    }

    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropColumn('queue_plan');
        });
    }
};
