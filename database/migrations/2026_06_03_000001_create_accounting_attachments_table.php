<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounting_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained('workspaces')->cascadeOnDelete();
            $table->string('attachable_type', 64);
            $table->unsignedBigInteger('attachable_id');
            $table->string('disk', 30)->default('local');
            $table->string('path', 500);
            $table->string('original_name', 255);
            $table->string('mime_type', 120)->nullable();
            $table->unsignedInteger('size')->nullable();
            $table->foreignId('accounting_user_id')->nullable()->nullOnDelete()->constrained('accounting_users');
            $table->timestamp('created_at')->nullable();

            $table->index(['attachable_type', 'attachable_id']);
            $table->index('workspace_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_attachments');
    }
};
