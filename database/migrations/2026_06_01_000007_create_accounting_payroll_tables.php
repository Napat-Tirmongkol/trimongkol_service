<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('code', 40);
            $table->string('name', 160);
            $table->string('tax_id', 20)->nullable();
            $table->string('social_security_id', 20)->nullable();
            $table->decimal('base_salary', 15, 2)->default(0);
            $table->string('bank_account', 40)->nullable();
            $table->string('position', 80)->nullable();
            $table->date('hired_on')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['workspace_id', 'code']);
            $table->index(['workspace_id', 'is_active']);
        });

        Schema::create('accounting_payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('no', 30);                          // PR-2569-01
            $table->string('period', 7);                       // YYYY-MM
            $table->date('payment_date');                      // วันจ่ายเงิน
            $table->string('status', 12)->default('draft');    // draft | posted
            $table->decimal('total_gross', 15, 2)->default(0);
            $table->decimal('total_ss_employee', 15, 2)->default(0);
            $table->decimal('total_ss_employer', 15, 2)->default(0);
            $table->decimal('total_wht', 15, 2)->default(0);
            $table->decimal('total_net', 15, 2)->default(0);
            $table->foreignId('journal_id')->nullable()
                ->constrained('accounting_journals')->nullOnDelete();
            // Accounts used when posting
            $table->foreignId('salary_expense_account_id')
                ->constrained('accounting_accounts')->cascadeOnDelete();
            $table->foreignId('ss_expense_account_id')
                ->constrained('accounting_accounts')->cascadeOnDelete();
            $table->foreignId('ss_payable_account_id')
                ->constrained('accounting_accounts')->cascadeOnDelete();
            $table->foreignId('wht_payable_account_id')
                ->constrained('accounting_accounts')->cascadeOnDelete();
            $table->foreignId('cash_account_id')
                ->constrained('accounting_accounts')->cascadeOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['workspace_id', 'period']);
            $table->index(['workspace_id', 'status']);
        });

        Schema::create('accounting_payroll_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_run_id')
                ->constrained('accounting_payroll_runs')->cascadeOnDelete();
            $table->foreignId('employee_id')
                ->constrained('accounting_employees')->cascadeOnDelete();
            $table->decimal('gross', 15, 2);                   // เงินเดือน + OT + bonus
            $table->decimal('ss_employee', 15, 2)->default(0); // ประกันสังคม 5% (cap 750)
            $table->decimal('ss_employer', 15, 2)->default(0); // นายจ้างจ่ายอีก 5% (cap 750)
            $table->decimal('wht', 15, 2)->default(0);         // หัก ณ ที่จ่าย ภงด.1
            $table->decimal('other_deductions', 15, 2)->default(0);
            $table->decimal('net', 15, 2);                     // เงินที่จ่ายจริง
            $table->timestamps();

            $table->index(['workspace_id', 'payroll_run_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_payroll_items');
        Schema::dropIfExists('accounting_payroll_runs');
        Schema::dropIfExists('accounting_employees');
    }
};
