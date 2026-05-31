<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('queue_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('plan_key', 32);
            $table->unsignedInteger('amount');          // THB, whole baht
            $table->unsignedSmallInteger('months')->default(1);
            $table->string('status', 16)->default('pending'); // pending | verified | rejected
            $table->string('slip_path')->nullable();
            $table->string('trans_ref')->nullable()->unique(); // SlipOK transRef — blocks reuse
            $table->string('note')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->index(['workspace_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('queue_payments');
    }
};
