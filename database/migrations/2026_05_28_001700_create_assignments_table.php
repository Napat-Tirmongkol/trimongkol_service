<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->date('due_date')->nullable();
            $table->string('scoring_mode', 16)->default('check'); // check | fixed | custom
            $table->unsignedSmallInteger('default_score')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['classroom_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('assignments');
    }
};
