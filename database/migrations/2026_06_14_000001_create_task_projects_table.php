<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_projects', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('name', 120);
            $table->text('description')->nullable();
            // Stored colour token (e.g. "brand", "emerald") — mapped to a full
            // Tailwind class set in the view, never interpolated dynamically.
            $table->string('color', 20)->default('brand');
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['workspace_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_projects');
    }
};
