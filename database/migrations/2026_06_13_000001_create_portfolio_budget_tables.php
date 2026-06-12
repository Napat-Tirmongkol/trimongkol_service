<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Budget Items (Fixed/Variable Expenses, Savings)
        Schema::create('portfolio_budget_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('category', 24); // fixed_expense, variable_expense, saving
            $table->string('label', 120);
            $table->decimal('amount', 18, 2);
            $table->boolean('is_checked')->default(false);
            $table->smallInteger('sort_order')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 2. Installments (Debts)
        Schema::create('portfolio_installments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label', 120);
            $table->decimal('monthly_payment', 18, 2);
            $table->decimal('total_amount', 18, 2);
            $table->integer('total_months');
            $table->integer('paid_months')->default(0);
            $table->boolean('is_checked')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 3. Subscriptions
        Schema::create('portfolio_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('label', 120);
            $table->decimal('monthly_payment', 18, 2);
            $table->unsignedTinyInteger('billing_day')->nullable();
            $table->boolean('is_checked')->default(false);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 4. Income
        Schema::create('portfolio_income', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('income_amount', 18, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_income');
        Schema::dropIfExists('portfolio_subscriptions');
        Schema::dropIfExists('portfolio_installments');
        Schema::dropIfExists('portfolio_budget_items');
    }
};
