<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            // 'contact' = public sales form, 'feedback' = signed-in user bug report.
            $table->string('source', 32)->default('contact')->after('message');
            // Browser, current URL, workspace etc. — captured to help debug.
            $table->json('context')->nullable()->after('source');

            $table->index('source');
        });
    }

    public function down(): void
    {
        Schema::table('leads', function (Blueprint $table) {
            $table->dropIndex(['source']);
            $table->dropColumn(['source', 'context']);
        });
    }
};
