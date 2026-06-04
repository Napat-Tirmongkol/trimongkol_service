<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('feed_source_id')->nullable()->constrained('social_feed_sources')->nullOnDelete();
            $table->string('title');
            $table->text('source_url');
            $table->timestamp('source_published_at')->nullable();
            $table->text('ai_content')->nullable();
            $table->string('status')->default('pending_ai'); // pending_ai | draft | approved | published | failed
            $table->string('facebook_post_id')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_posts');
    }
};
