<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio_ledger', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->date('date');
            $table->string('month', 7); // YYYY-MM — derived from date, stored for fast filtering
            $table->enum('type', ['income', 'expense']);
            $table->decimal('amount', 12, 2)->default(0);
            $table->string('label', 200);
            $table->unsignedBigInteger('budget_item_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'month']);
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('budget_item_id')->references('id')->on('portfolio_budget_items')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_ledger');
    }
};
