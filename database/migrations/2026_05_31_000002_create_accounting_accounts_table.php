<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Chart of accounts — one tree per workspace (tenant). Code is the
        // Thai "XXXX-XX" control+sub format; reports roll up by the first 4.
        Schema::create('accounting_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);                 // e.g. "1113-01"
            $table->string('name');
            $table->string('name_en')->nullable();
            $table->enum('type', ['asset', 'liability', 'equity', 'income', 'expense']);
            $table->enum('normal_balance', ['debit', 'credit']);
            $table->foreignId('parent_id')->nullable()->constrained('accounting_accounts')->nullOnDelete();
            // Binds the handful of accounts the posting/tax engine resolves by
            // role (ar_control, ap_control, vat_input, wht_payable_pnd3, ...).
            $table->string('system_role', 40)->nullable();
            $table->boolean('is_active')->default(true);
            $table->boolean('is_tax_deductible')->default(true); // false = ค่าใช้จ่ายต้องห้าม
            $table->char('currency', 3)->default('THB');
            $table->timestamps();

            $table->unique(['workspace_id', 'code']);
            $table->index(['workspace_id', 'type']);
            $table->index(['workspace_id', 'system_role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_accounts');
    }
};
