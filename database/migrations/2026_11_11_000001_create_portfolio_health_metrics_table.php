<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('portfolio_health_metrics')) {
            Schema::create('portfolio_health_metrics', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->date('date');
                $table->unsignedInteger('steps')->nullable();
                $table->decimal('distance_meters', 10, 2)->nullable();
                $table->decimal('active_calories', 8, 2)->nullable();
                $table->decimal('total_calories', 8, 2)->nullable();
                $table->unsignedSmallInteger('heart_rate_avg')->nullable();
                $table->unsignedSmallInteger('heart_rate_min')->nullable();
                $table->unsignedSmallInteger('heart_rate_max')->nullable();
                $table->unsignedSmallInteger('resting_heart_rate')->nullable();
                $table->unsignedSmallInteger('sleep_minutes')->nullable();
                $table->unsignedSmallInteger('deep_sleep_minutes')->nullable();
                $table->unsignedSmallInteger('rem_sleep_minutes')->nullable();
                $table->unsignedSmallInteger('light_sleep_minutes')->nullable();
                $table->decimal('spo2_avg', 5, 2)->nullable();
                $table->string('source', 50)->default('health_connect');
                $table->longText('raw_payload')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'date'], 'user_health_date_unique');
                $table->index('user_id');
                $table->index('date');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_health_metrics');
    }
};
