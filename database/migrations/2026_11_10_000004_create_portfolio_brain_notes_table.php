<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('portfolio_brain_notes')) {
            Schema::create('portfolio_brain_notes', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->string('title', 255);
                $table->longText('content')->nullable();
                $table->enum('type', ['inbox', 'project', 'area', 'resource', 'archive'])->default('inbox');
                $table->string('category', 50)->default('general');
                $table->json('tags')->nullable();
                $table->string('color', 30)->default('slate');
                $table->string('url', 500)->nullable();
                $table->boolean('is_pinned')->default(false);
                $table->boolean('is_archived')->default(false);
                $table->integer('sort_order')->default(0);
                $table->timestamps();

                $table->index(['user_id', 'type']);
                $table->index(['user_id', 'is_pinned']);
                $table->index(['user_id', 'is_archived']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_brain_notes');
    }
};
