<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounting_payroll_items', function (Blueprint $table) {
            $table->decimal('overtime', 15, 2)->default(0)->after('gross');
            $table->decimal('ot', 15, 2)->default(0)->after('overtime');
        });

        Schema::table('accounting_payroll_runs', function (Blueprint $table) {
            $table->decimal('total_overtime', 15, 2)->default(0)->after('total_gross');
            $table->decimal('total_ot', 15, 2)->default(0)->after('total_overtime');
        });
    }

    public function down(): void
    {
        Schema::table('accounting_payroll_items', function (Blueprint $table) {
            $table->dropColumn(['overtime', 'ot']);
        });

        Schema::table('accounting_payroll_runs', function (Blueprint $table) {
            $table->dropColumn(['total_overtime', 'total_ot']);
        });
    }
};
