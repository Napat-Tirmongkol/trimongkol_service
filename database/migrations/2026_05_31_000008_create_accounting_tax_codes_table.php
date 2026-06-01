<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tax codes (VAT output/input, withholding). Each points at the ledger
        // account it posts to — VAT7 -> ภาษีขาย, WHT3 -> ภาษีหัก ณ ที่จ่ายค้างจ่าย.
        Schema::create('accounting_tax_codes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->enum('kind', ['vat_output', 'vat_input', 'wht']);
            $table->decimal('rate', 6, 3);        // percent, e.g. 7.000
            $table->foreignId('account_id')->constrained('accounting_accounts')->restrictOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['workspace_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_tax_codes');
    }
};
