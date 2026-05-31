<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_counters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('queue_id')->constrained()->cascadeOnDelete();
            $table->string('label', 40);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('queue_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_counters');
    }
};
