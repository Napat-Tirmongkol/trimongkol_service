<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_recurring_journals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name', 120);
            $table->string('frequency', 20)->default('monthly'); // monthly | quarterly | weekly
            $table->date('next_run_date');
            $table->date('end_date')->nullable();                 // null = run indefinitely
            $table->timestamp('last_run_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['workspace_id', 'is_active', 'next_run_date']);
        });

        Schema::create('accounting_recurring_journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('recurring_journal_id')
                ->constrained('accounting_recurring_journals')->cascadeOnDelete();
            $table->unsignedSmallInteger('line_no');
            $table->foreignId('account_id')->constrained('accounting_accounts')->cascadeOnDelete();
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('credit', 15, 2)->default(0);
            $table->string('description', 255)->nullable();
            $table->foreignId('department_id')->nullable()
                ->constrained('accounting_departments')->nullOnDelete();
            $table->timestamps();

            $table->index(['workspace_id', 'recurring_journal_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_recurring_journal_lines');
        Schema::dropIfExists('accounting_recurring_journals');
    }
};
