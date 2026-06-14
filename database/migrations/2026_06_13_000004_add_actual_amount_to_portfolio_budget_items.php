<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('portfolio_budget_items') && !Schema::hasColumn('portfolio_budget_items', 'actual_amount')) {
            Schema::table('portfolio_budget_items', function (Blueprint $table) {
                $table->decimal('actual_amount', 18, 2)->nullable()->after('amount');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('portfolio_budget_items', 'actual_amount')) {
            Schema::table('portfolio_budget_items', function (Blueprint $table) {
                $table->dropColumn('actual_amount');
            });
        }
    }
};
