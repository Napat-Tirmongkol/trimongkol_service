<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // How much of a payment is applied to each document. Polymorphic so the
        // AP phase can allocate supplier payments to bills with the same table.
        Schema::create('accounting_payment_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_id')->constrained('accounting_payments')->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->morphs('allocatable');           // Invoice now; Bill in the AP phase
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
