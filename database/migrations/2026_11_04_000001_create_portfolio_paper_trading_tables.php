<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('portfolio_paper_accounts')) {
            Schema::create('portfolio_paper_accounts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->decimal('initial_balance', 15, 2)->default(100000.00);
                $table->decimal('cash_balance', 15, 2)->default(100000.00);
                $table->string('currency', 3)->default('THB');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->unique('user_id');
            });
        }

        if (!Schema::hasTable('portfolio_paper_trades')) {
            Schema::create('portfolio_paper_trades', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->foreignId('paper_account_id')->nullable()->constrained('portfolio_paper_accounts')->nullOnDelete();
                $table->string('symbol', 32);
                $table->string('label', 120)->nullable();
                $table->string('kind', 20)->default('stock');
                $table->string('currency', 3)->default('THB');
                $table->string('side', 10)->default('buy');
                $table->string('status', 20)->default('open'); // open, closed, cancelled
                $table->decimal('quantity', 15, 4);
                $table->decimal('entry_price', 15, 4);
                $table->decimal('entry_total_thb', 15, 2)->default(0.00);
                $table->decimal('current_price', 15, 4)->nullable();
                $table->decimal('current_value_thb', 15, 2)->nullable();
                $table->decimal('target_price', 15, 4)->nullable();
                $table->decimal('stop_loss', 15, 4)->nullable();
                $table->decimal('exit_price', 15, 4)->nullable();
                $table->decimal('exit_total_thb', 15, 2)->nullable();
                $table->decimal('realized_pnl_thb', 15, 2)->nullable();
                $table->decimal('realized_pnl_pct', 8, 2)->nullable();
                $table->text('trade_thesis')->nullable();
                $table->timestamp('opened_at')->useCurrent();
                $table->timestamp('closed_at')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'status']);
                $table->index('symbol');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_paper_trades');
        Schema::dropIfExists('portfolio_paper_accounts');
    }
};
