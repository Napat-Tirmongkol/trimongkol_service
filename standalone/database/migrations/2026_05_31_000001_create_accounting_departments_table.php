<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Optional cost-centre dimension — the "แผนก" column on the company's
        // trial balance. Nullable on journal lines, so books work without it.
        Schema::create('accounting_departments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('code', 20);
            $table->string('name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['workspace_id', 'code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_departments');
    }
};
