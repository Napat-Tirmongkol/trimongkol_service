<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Supplier bills (AP). Posting one records the liability:
        // DR expense + DR input VAT / CR accounts payable.
        Schema::create('accounting_bills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('no', 30);
            $table->foreignId('partner_id')->constrained('accounting_partners')->restrictOnDelete();
            $table->string('bill_ref')->nullable();          // the supplier's own document number
            $table->date('issue_date');
            $table->date('due_date')->nullable();
            $table->string('status', 12)->default('draft');  // draft|issued|partial|paid|void
            $table->char('currency', 3)->default('THB');
            $table->decimal('subtotal', 18, 2)->default(0);
            $table->decimal('vat_amount', 18, 2)->default(0);
            $table->decimal('total', 18, 2)->default(0);
            $table->string('memo')->nullable();
            $table->foreignId('journal_id')->nullable()->constrained('accounting_journals')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['workspace_id', 'no']);
            $table->index(['workspace_id', 'status', 'issue_date'], 'acc_bills_ws_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_bills');
    }
};
