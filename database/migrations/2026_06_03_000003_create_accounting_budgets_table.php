<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Planned revenue/expense per (account × department × fiscal period).
        // fiscal_month = null is an annual budget; 1-12 is a monthly entry. The
        // Budget vs Actual report joins this against posted ledger lines.
        Schema::create('accounting_budgets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounting_accounts')->cascadeOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('accounting_departments')->nullOnDelete();
            $table->unsignedSmallInteger('fiscal_year');
            $table->unsignedTinyInteger('fiscal_month')->nullable();
            $table->decimal('amount', 14, 2);
            $table->timestamps();

            $table->index(['workspace_id', 'fiscal_year']);
            $table->index(['workspace_id', 'account_id', 'fiscal_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_budgets');
    }
};
