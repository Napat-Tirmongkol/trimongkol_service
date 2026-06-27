<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('portfolio_ledger', function (Blueprint $table) {
            $table->unsignedBigInteger('subscription_id')->nullable()->after('income_id');
            $table->unsignedBigInteger('debt_id')->nullable()->after('subscription_id');

            $table->foreign('subscription_id')
                ->references('id')->on('portfolio_subscriptions')->onDelete('set null');
            $table->foreign('debt_id')
                ->references('id')->on('portfolio_debts')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::table('portfolio_ledger', function (Blueprint $table) {
            $table->dropForeign(['subscription_id']);
            $table->dropForeign(['debt_id']);
            $table->dropColumn(['subscription_id', 'debt_id']);
        });
    }
};
