<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio_watchlist', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 16);             // stock, crypto, fund
            $table->string('symbol', 32);           // e.g. "AAPL", "bitcoin"
            $table->string('label', 120);           // Display name
            $table->string('currency', 3)->default('THB');
            $table->decimal('current_price', 24, 8)->nullable();
            $table->decimal('current_price_thb', 18, 2)->nullable();
            $table->timestamp('last_priced_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'kind']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_watchlist');
    }
};
