<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // cost_basis and sort_order were declared NOT NULL with default(0),
        // but the form's nullable inputs were tripping a 23000 constraint
        // violation whenever the user left them blank. The controller now
        // coerces null → 0, but make the columns nullable too so a stale
        // OPcache or a future code path can't reintroduce the regression.
        Schema::table('portfolio_holdings', function (Blueprint $table) {
            $table->decimal('cost_basis', 18, 2)->nullable()->default(0)->change();
            $table->unsignedSmallInteger('sort_order')->nullable()->default(0)->change();
        });

        // Backfill anything that did sneak through as NULL — null
        // cost basis effectively means "0 baht spent", so the gain/loss
        // tile still reads correctly after the relaxation.
        DB::table('portfolio_holdings')->whereNull('cost_basis')->update(['cost_basis' => 0]);
        DB::table('portfolio_holdings')->whereNull('sort_order')->update(['sort_order' => 0]);
    }

    public function down(): void
    {
        Schema::table('portfolio_holdings', function (Blueprint $table) {
            $table->decimal('cost_basis', 18, 2)->nullable(false)->default(0)->change();
            $table->unsignedSmallInteger('sort_order')->nullable(false)->default(0)->change();
        });
    }
};
