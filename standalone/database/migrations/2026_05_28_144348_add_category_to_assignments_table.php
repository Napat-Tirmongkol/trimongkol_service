<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            // homework | classwork | quiz | exam | project | other
            $table->string('category', 16)->default('homework')->after('name');
            $table->index(['classroom_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropIndex(['classroom_id', 'category']);
            $table->dropColumn('category');
        });
    }
};
