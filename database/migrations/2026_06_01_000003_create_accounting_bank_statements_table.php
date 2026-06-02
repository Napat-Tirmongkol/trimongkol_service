<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_bank_statements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->constrained('accounting_accounts')->cascadeOnDelete();
            $table->date('statement_date');
            $table->string('description', 255)->nullable();
            // Positive = deposit (money in), negative = withdrawal (money out)
            $table->decimal('amount', 15, 2);
            $table->string('reference', 120)->nullable();
            // Matched to a journal line once reconciled
            $table->foreignId('journal_line_id')->nullable()
                ->constrained('accounting_journal_lines')->nullOnDelete();
            $table->timestamps();

            $table->index(['workspace_id', 'account_id', 'statement_date'], 'bank_stmt_ws_acct_date_idx');
            $table->index(['workspace_id', 'journal_line_id'], 'bank_stmt_ws_jline_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_bank_statements');
    }
};
