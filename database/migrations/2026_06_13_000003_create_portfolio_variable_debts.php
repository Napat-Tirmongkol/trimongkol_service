<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('portfolio_debts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label', 120);
            $table->decimal('total_amount', 14, 2)->default(0)->comment('ยอดหนี้รวม (0 = ไม่กำหนด)');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('user_id');
        });

        Schema::create('portfolio_debt_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('debt_id')->constrained('portfolio_debts')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('month', 7); // YYYY-MM
            $table->decimal('amount', 14, 2);
            $table->boolean('is_paid')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'month']);
            $table->index(['debt_id', 'month']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_debt_payments');
        Schema::dropIfExists('portfolio_debts');
    }
};
