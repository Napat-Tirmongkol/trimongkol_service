<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('portfolio_personal_profiles')) {
            Schema::create('portfolio_personal_profiles', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->unique();
                $table->string('mbti', 20)->nullable();
                $table->string('headline', 255)->nullable();
                $table->text('traits')->nullable();
                $table->text('strengths')->nullable();
                $table->text('weaknesses')->nullable();
                $table->text('energy_boosters')->nullable();
                $table->text('energy_drainers')->nullable();
                $table->text('coping_guide')->nullable();
                $table->text('communication_style')->nullable();
                $table->text('life_principles')->nullable();
                $table->text('favorite_things')->nullable();
                $table->timestamps();

                $table->index('user_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_personal_profiles');
    }
};
