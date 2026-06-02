<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('accounting_fixed_assets');

        Schema::create('accounting_fixed_assets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('code', 40);
            $table->string('name', 120);
            $table->date('purchase_date');
            $table->decimal('cost', 15, 2);                      // acquisition cost
            $table->decimal('salvage_value', 15, 2)->default(0); // residual value at end of life
            $table->unsignedSmallInteger('useful_life_months');  // straight-line period
            // Accounts used for posting
            $table->foreignId('asset_account_id')
                ->constrained('accounting_accounts')->cascadeOnDelete();
            $table->foreignId('accumulated_account_id')
                ->constrained('accounting_accounts')->cascadeOnDelete();
            $table->foreignId('depreciation_expense_account_id')
                ->constrained('accounting_accounts')->cascadeOnDelete();
            $table->string('status', 12)->default('active');     // active | disposed
            $table->date('disposed_on')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['workspace_id', 'code']);
            $table->index(['workspace_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_fixed_assets');
    }
};
