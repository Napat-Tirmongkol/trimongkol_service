<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Journal header = one transaction. Once status flips to "posted" it is
        // immutable (App\Models\Accounting\Journal guards it) — corrections are
        // made by posting a reversing entry linked via reverses_journal_id.
        Schema::create('accounting_journals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('workspace_id')->constrained()->cascadeOnDelete();
            $table->string('no', 30);                          // running number per workspace
            $table->date('date');
            $table->foreignId('period_id')->nullable()->constrained('accounting_periods')->nullOnDelete();
            $table->string('type', 16)->default('general');    // general|sales|purchase|receipt|payment|opening|closing|reversal
            $table->string('memo')->nullable();
            $table->string('status', 12)->default('draft');    // draft | posted | void
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->nullableMorphs('source');                  // links to Invoice/Bill/Payment in Phase 2
            $table->foreignId('reverses_journal_id')->nullable()->constrained('accounting_journals')->nullOnDelete();
            $table->timestamps();

            $table->unique(['workspace_id', 'no']);
            $table->index(['workspace_id', 'status', 'date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounting_journals');
    }
};
