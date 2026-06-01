<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Company profile per workspace — in a multi-tenant SaaS each workspace is
     * a different business, so the details printed on documents (name, tax id,
     * branch, address) belong on the workspace, not in global settings.
     * `onboarded_at` gates the first-run setup wizard.
     */
    public function up(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->string('company_name')->nullable()->after('slug');
            $table->string('tax_id', 13)->nullable()->after('company_name');
            $table->string('branch', 30)->nullable()->after('tax_id');     // "สำนักงานใหญ่" / "สาขา 00001"
            $table->string('phone', 30)->nullable()->after('branch');
            $table->string('company_address', 500)->nullable()->after('phone');
            $table->string('chart_template', 40)->nullable()->after('company_address');
            $table->timestamp('onboarded_at')->nullable()->after('chart_template');
        });
    }

    public function down(): void
    {
        Schema::table('workspaces', function (Blueprint $table) {
            $table->dropColumn([
                'company_name', 'tax_id', 'branch', 'phone',
                'company_address', 'chart_template', 'onboarded_at',
            ]);
        });
    }
};
