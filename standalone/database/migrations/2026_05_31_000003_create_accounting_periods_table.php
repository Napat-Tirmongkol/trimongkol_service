<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Accounting periods. Posting is only allowed into an "open" period —
        // closing/locking a period freezes its journals from new entries.
        Schema::create('accounting_periods', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('name', 40);                       // e.g. "2569" or "2569-01"
            $table->date('starts_on');
            $table->date('ends_on');
            $table->string('status', 12)->default('open');    // open | closed | locked
            $table->timestamps();

            $table->unique(['workspace_id', 'starts_on']);
            $table->index(['workspace_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_periods');
    }
};
