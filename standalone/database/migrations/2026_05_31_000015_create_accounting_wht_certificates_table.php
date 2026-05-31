<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Withholding-tax certificates (หนังสือรับรองหัก ณ ที่จ่าย / 50 ทวิ),
        // created when we withhold tax paying a supplier. Feeds ภงด.3/53.
        Schema::create('accounting_wht_certificates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('no', 30);
            $table->foreignId('payment_id')->nullable()->constrained('accounting_payments')->nullOnDelete();
            $table->foreignId('partner_id')->constrained('accounting_partners')->restrictOnDelete();
            $table->string('pnd_type', 8);          // PND3 (individual) | PND53 (company)
            $table->string('tax_id', 13)->nullable();
            $table->string('income_type')->nullable();
            $table->date('paid_on');
            $table->decimal('base_amount', 18, 2);
            $table->decimal('rate', 6, 3);
            $table->decimal('wht_amount', 18, 2);
            $table->timestamps();

            $table->unique(['workspace_id', 'no']);
            $table->index(['workspace_id', 'pnd_type', 'paid_on'], 'acc_wht_pnd_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_wht_certificates');
    }
};
