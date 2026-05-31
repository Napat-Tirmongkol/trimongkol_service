<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Accounting product subscription tier per workspace — billed separately
     * from the platform and queue products (mirrors workspaces.queue_plan).
     * accounting_plan_until null = no expiry; a paid plan past it drops back to
     * free (see App\Services\AccountingPlan).
     */
    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->string('accounting_plan', 20)->nullable()->after('queue_plan_until');
            $table->timestamp('accounting_plan_until')->nullable()->after('accounting_plan');
        });
    }

    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropColumn(['accounting_plan', 'accounting_plan_until']);
        });
    }
};
