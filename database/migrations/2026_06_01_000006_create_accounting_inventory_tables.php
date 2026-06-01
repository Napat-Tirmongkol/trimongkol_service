<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('sku', 40);
            $table->string('name', 160);
            $table->string('unit', 20)->default('ชิ้น');       // หน่วยนับ (ชิ้น/กล่อง/กก./ลิตร)
            $table->decimal('unit_cost', 15, 2)->default(0);   // ราคาทุนเฉลี่ย (moving average)
            $table->decimal('on_hand', 15, 3)->default(0);     // จำนวนคงเหลือ (denormalized; ledger is source of truth)
            // Accounts used when posting movements
            $table->foreignId('inventory_account_id')
                ->constrained('accounting_accounts')->cascadeOnDelete();
            $table->foreignId('cogs_account_id')
                ->constrained('accounting_accounts')->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['workspace_id', 'sku']);
            $table->index(['workspace_id', 'is_active']);
        });

        Schema::create('accounting_stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('accounting_products')->cascadeOnDelete();
            $table->date('movement_date');
            $table->string('type', 16);                        // in | out | adjust
            $table->decimal('quantity', 15, 3);                // positive — direction comes from `type`
            $table->decimal('unit_cost', 15, 2);
            $table->decimal('total_value', 15, 2);             // qty × unit_cost (signed by type at post time)
            $table->string('reference', 120)->nullable();
            $table->string('note', 255)->nullable();
            $table->foreignId('journal_id')->nullable()
                ->constrained('accounting_journals')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['workspace_id', 'product_id', 'movement_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_stock_movements');
        Schema::dropIfExists('accounting_products');
    }
};
