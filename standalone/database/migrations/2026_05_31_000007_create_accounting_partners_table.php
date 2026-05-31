<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Customers & vendors (a partner can be both). Selecting one on a
        // document pulls its tax id, branch, and credit term automatically.
        Schema::create('accounting_partners', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('code', 30)->nullable();
            $table->string('name');
            $table->string('tax_id', 13)->nullable();
            $table->string('branch_code', 10)->default('00000'); // 00000 = สำนักงานใหญ่
            $table->boolean('is_customer')->default(true);
            $table->boolean('is_vendor')->default(false);
            $table->string('address')->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email')->nullable();
            $table->unsignedInteger('credit_days')->default(0);
            $table->foreignId('ar_account_id')->nullable()->constrained('accounting_accounts')->nullOnDelete();
            $table->foreignId('ap_account_id')->nullable()->constrained('accounting_accounts')->nullOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['workspace_id', 'code']);
            $table->index(['workspace_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_partners');
    }
};
