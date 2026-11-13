<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('portfolio_couple_items')) {
            Schema::create('portfolio_couple_items', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->enum('type', ['food', 'trip', 'wishlist', 'date_idea', 'movie', 'savings'])->default('date_idea');
                $table->string('title', 255);
                $table->text('description')->nullable();
                $table->string('location', 255)->nullable();
                $table->date('target_date')->nullable();
                $table->decimal('target_amount', 12, 2)->nullable();
                $table->decimal('saved_amount', 12, 2)->default(0);
                $table->boolean('is_done')->default(false);
                $table->timestamp('done_at')->nullable();
                $table->tinyInteger('rating')->nullable();
                $table->string('partner_name', 100)->default('แฟน');
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'type']);
                $table->index(['user_id', 'is_done']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_couple_items');
    }
};
