<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queues', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workspace_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('prefix', 8)->default('');
            $table->string('public_token', 32)->unique();
            $table->unsignedInteger('last_number')->default(0);
            $table->boolean('voice_enabled')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['workspace_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queues');
    }
};
