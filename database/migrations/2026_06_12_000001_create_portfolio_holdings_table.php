<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Personal-portfolio holdings — admin/owner only. Scoped to user_id so
        // each admin sees only their own assets and debts. Money is DECIMAL,
        // never float. Quantity carries 8 dp so crypto fractional units fit.
        Schema::create('portfolio_holdings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('kind', 32);                  // stock|deposit|cash|debt|gold|crypto|fund|realestate|other
            $table->string('label');                     // free-text display name
            $table->string('symbol')->nullable();        // e.g. 'PTT.BK', 'AAPL', 'bitcoin' (CoinGecko id)
            $table->decimal('quantity', 24, 8)->default(0);
            $table->decimal('cost_basis', 18, 2)->default(0); // total cost in THB
            $table->string('currency', 8)->default('THB');
            $table->decimal('current_price', 24, 8)->nullable(); // per-unit, in `currency`
            $table->decimal('current_value_thb', 18, 2)->nullable(); // quantity * price * fx
            $table->timestamp('last_priced_at')->nullable();
            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();        // broker, account no, due date for debts, etc.
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->index(['user_id', 'kind']);
            $table->index(['user_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('portfolio_holdings');
    }
};
