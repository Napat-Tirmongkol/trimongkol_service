<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portfolio_ledger', function (Blueprint $table) {
            $table->unsignedBigInteger('installment_id')->nullable()->after('budget_item_id');
            $table->unsignedBigInteger('income_id')->nullable()->after('installment_id');

            $table->foreign('installment_id')
                ->references('id')->on('portfolio_installments')->onDelete('set null');
            $table->foreign('income_id')
                ->references('id')->on('portfolio_income')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('portfolio_ledger', function (Blueprint $table) {
            $table->dropForeign(['installment_id']);
            $table->dropForeign(['income_id']);
            $table->dropColumn(['installment_id', 'income_id']);
        });
    }
};
