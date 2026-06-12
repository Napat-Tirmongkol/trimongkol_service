<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('holding_id')->constrained('portfolio_holdings')->cascadeOnDelete();
            $table->string('type', 8); // in (deposit/spend), out (withdrawal/payment)
            $table->decimal('amount', 18, 2);
            $table->date('transaction_date');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['holding_id', 'transaction_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_transactions');
    }
};
