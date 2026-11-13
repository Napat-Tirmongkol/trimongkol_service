<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('portfolio_life_goals')) {
            Schema::create('portfolio_life_goals', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('title', 255);
                $table->text('description')->nullable();
                $table->enum('horizon', ['short', 'medium', 'long'])->default('short');
                $table->string('category', 50)->default('personal');
                $table->unsignedSmallInteger('target_year')->nullable()->default(2027);
                $table->date('target_date')->nullable();
                $table->unsignedTinyInteger('progress_percent')->default(0);
                $table->boolean('is_completed')->default(false);
                $table->timestamp('completed_at')->nullable();
                $table->integer('sort_order')->default(0);
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'horizon']);
                $table->index(['user_id', 'is_completed']);
                $table->index(['user_id', 'target_year']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_life_goals');
    }
};
