<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Self-heal: an earlier run may have created this table but failed
        // before adding the morphs index (its auto-generated name exceeded
        // MySQL's 64-char limit), leaving the migration unrecorded. The table
        // is only ever created empty here, so dropping it first is safe and
        // lets a re-run succeed.
        Schema::dropIfExists('accounting_payment_allocations');

        // How much of a payment is applied to each document. Polymorphic so the
        // AP phase can allocate supplier payments to bills with the same table.
        Schema::create('accounting_payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('accounting_payments')->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            // Explicit, short index name — the default exceeds MySQL's 64-char limit.
            $table->morphs('allocatable', 'acc_pay_alloc_morph_idx'); // Invoice now; Bill later
            $table->decimal('amount', 18, 2);
            $table->timestamps();

            $table->index('payment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_payment_allocations');
    }
};
