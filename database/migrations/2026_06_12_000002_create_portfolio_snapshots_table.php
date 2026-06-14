<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Daily net-worth rollup, written every time prices are refreshed.
        // One row per user per date — the UNIQUE constraint lets us upsert
        // freely if the user refreshes multiple times in a single day.
        Schema::create('portfolio_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('snapshot_date');
            $table->decimal('total_assets_thb', 18, 2)->default(0);
            $table->decimal('total_debts_thb', 18, 2)->default(0);
            $table->decimal('net_worth_thb', 18, 2)->default(0);
            $table->timestamps();

            $table->unique(['user_id', 'snapshot_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_snapshots');
    }
};
