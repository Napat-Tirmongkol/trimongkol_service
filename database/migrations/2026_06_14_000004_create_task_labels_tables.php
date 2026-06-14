<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Re-usable, workspace-scoped labels (tags) that any task can wear.
        Schema::create('task_labels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name', 50);
            $table->string('color', 20)->default('slate');
            $table->timestamps();

            $table->index('workspace_id');
        });

        Schema::create('task_label_task', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->cascadeOnDelete();
            $table->foreignId('task_label_id')->constrained()->cascadeOnDelete();

            $table->unique(['task_id', 'task_label_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_label_task');
        Schema::dropIfExists('task_labels');
    }
};
