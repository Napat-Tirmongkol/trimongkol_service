<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The debit/credit lines. Money is DECIMAL — never float — and every
        // line carries exactly one positive side (enforced in LedgerPosting).
        Schema::create('accounting_journal_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_id')->constrained('accounting_journals')->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete(); // denormalised for scoped reports
            $table->unsignedSmallInteger('line_no')->default(1);
            $table->foreignId('account_id')->constrained('accounting_accounts')->restrictOnDelete();
            $table->decimal('debit', 18, 2)->default(0);
            $table->decimal('credit', 18, 2)->default(0);
            $table->string('description')->nullable();
            $table->foreignId('department_id')->nullable()->constrained('accounting_departments')->nullOnDelete();
            $table->unsignedBigInteger('partner_id')->nullable();   // FK added with Phase 2 (partners)
            $table->unsignedBigInteger('tax_code_id')->nullable();  // FK added with Phase 2 (tax_codes)
            $table->timestamps();

            $table->index(['workspace_id', 'account_id']);
            $table->index('journal_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_journal_lines');
    }
};
