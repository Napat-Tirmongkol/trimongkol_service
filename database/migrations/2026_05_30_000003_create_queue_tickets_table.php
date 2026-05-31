<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('queue_id')->constrained()->cascadeOnDelete();
            $table->foreignId('counter_id')->nullable()->constrained('queue_counters')->nullOnDelete();
            $table->unsignedInteger('number');
            $table->string('status', 16)->default('waiting'); // waiting | called | serving | done | skipped
            $table->timestamp('called_at')->nullable();
            $table->timestamp('served_at')->nullable();
            $table->timestamps();

            $table->index(['queue_id', 'status']);
            $table->index(['queue_id', 'number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_tickets');
    }
};
