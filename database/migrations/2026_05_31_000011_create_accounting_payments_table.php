<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Receipts (direction "in") and, later, supplier payments ("out").
        // `amount` is the cash actually moved; `wht_amount` is tax withheld.
        // Recording one posts a journal via LedgerPosting and settles invoices.
        Schema::create('accounting_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('no', 30);
            $table->foreignId('partner_id')->constrained('accounting_partners')->restrictOnDelete();
            $table->string('direction', 3);                  // in | out
            $table->date('payment_date');
            $table->string('method', 16)->default('transfer'); // cash|transfer|promptpay|cheque
            $table->foreignId('account_id')->constrained('accounting_accounts')->restrictOnDelete(); // cash/bank
            $table->decimal('amount', 18, 2)->default(0);
            $table->decimal('wht_amount', 18, 2)->default(0);
            $table->foreignId('wht_account_id')->nullable()->constrained('accounting_accounts')->nullOnDelete();
            $table->string('status', 12)->default('draft');  // draft|posted|void
            $table->foreignId('journal_id')->nullable()->constrained('accounting_journals')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->string('slip_path')->nullable();
            $table->string('slip_ref')->nullable();
            $table->string('note')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['workspace_id', 'no']);
            $table->unique(['workspace_id', 'slip_ref']);     // block reusing the same bank slip
            $table->index(['workspace_id', 'direction', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_payments');
    }
};
