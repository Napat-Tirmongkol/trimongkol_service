<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounting_activity_log', function (Blueprint $table) {
            $table->foreignId('accounting_user_id')
                ->nullable()
                ->after('user_id')
                ->constrained('accounting_users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('accounting_activity_log', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Accounting\AccountingUser::class, 'accounting_user_id');
            $table->dropColumn('accounting_user_id');
        });
    }
};
