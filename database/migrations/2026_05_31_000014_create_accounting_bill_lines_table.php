<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_bill_lines', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bill_id')->constrained('accounting_bills')->cascadeOnDelete();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('line_no')->default(1);
            $table->foreignId('account_id')->constrained('accounting_accounts')->restrictOnDelete(); // expense
            $table->string('description')->nullable();
            $table->decimal('quantity', 18, 4)->default(1);
            $table->decimal('unit_price', 18, 4)->default(0);
            $table->decimal('amount', 18, 2)->default(0);
            $table->foreignId('tax_code_id')->nullable()->constrained('accounting_tax_codes')->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('accounting_departments')->nullOnDelete();
            $table->timestamps();

            $table->index('bill_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_bill_lines');
    }
};
