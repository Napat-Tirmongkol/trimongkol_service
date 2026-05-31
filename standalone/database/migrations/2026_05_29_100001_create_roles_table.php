<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('roles', function (Blueprint $table) {
            $table->id();
            $table->string('key', 64)->unique();          // slug, e.g. super-admin
            $table->string('name', 80);
            $table->string('description', 255)->nullable();
            $table->json('permissions');                  // array of permission keys, or ['*']
            $table->boolean('is_system')->default(false); // protected from deletion
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
