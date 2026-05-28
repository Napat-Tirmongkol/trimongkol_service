<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            // The score this assignment is graded out of.
            // Null = derive from mode (1 for check, default_score for fixed, 100 for custom).
            $table->decimal('max_score', 7, 2)->nullable()->after('default_score');

            // How much this assignment counts toward the classroom total.
            // 1.00 = equal weight; bump higher to make exams count more than daily HW.
            $table->decimal('weight', 6, 2)->default(1.00)->after('max_score');
        });
    }

    public function down(): void
    {
        Schema::table('assignments', function (Blueprint $table) {
            $table->dropColumn(['max_score', 'weight']);
        });
    }
};
