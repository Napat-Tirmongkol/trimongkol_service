<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Add 'month' column to portfolio_budget_items
        if (Schema::hasTable('portfolio_budget_items') && !Schema::hasColumn('portfolio_budget_items', 'month')) {
            Schema::table('portfolio_budget_items', function (Blueprint $table) {
                $table->string('month', 7)->default('')->after('user_id');
            });
            // Back-fill existing rows with current month
            DB::table('portfolio_budget_items')->where('month', '')->update(['month' => date('Y-m')]);
        }

        // 2. Add 'month' column to portfolio_installments
        if (Schema::hasTable('portfolio_installments') && !Schema::hasColumn('portfolio_installments', 'month')) {
            Schema::table('portfolio_installments', function (Blueprint $table) {
                $table->string('month', 7)->default('')->after('user_id');
            });
            DB::table('portfolio_installments')->where('month', '')->update(['month' => date('Y-m')]);
        }

        // 3. Add 'month' column to portfolio_subscriptions
        if (Schema::hasTable('portfolio_subscriptions') && !Schema::hasColumn('portfolio_subscriptions', 'month')) {
            Schema::table('portfolio_subscriptions', function (Blueprint $table) {
                $table->string('month', 7)->default('')->after('user_id');
            });
            DB::table('portfolio_subscriptions')->where('month', '')->update(['month' => date('Y-m')]);
        }

        // 4. Rebuild portfolio_income table to support multiple sources per month
        if (Schema::hasTable('portfolio_income')) {
            if (Schema::hasColumn('portfolio_income', 'income_amount')) {
                // Backup existing income data
                $existingIncomes = DB::table('portfolio_income')->get();

                Schema::dropIfExists('portfolio_income');

                Schema::create('portfolio_income', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                    $table->string('month', 7);
                    $table->string('label', 120);
                    $table->decimal('amount', 18, 2);
                    $table->text('notes')->nullable();
                    $table->timestamps();
                });

                // Migrate old data: each old row becomes a "เงินเดือน" entry
                foreach ($existingIncomes as $old) {
                    DB::table('portfolio_income')->insert([
                        'user_id' => $old->user_id,
                        'month' => date('Y-m'),
                        'label' => 'เงินเดือน',
                        'amount' => $old->income_amount,
                        'notes' => null,
                        'created_at' => $old->created_at ?? now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        } else {
            // Table doesn't exist yet, create it fresh
            Schema::create('portfolio_income', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->string('month', 7);
                $table->string('label', 120);
                $table->decimal('amount', 18, 2);
                $table->text('notes')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        // Remove month columns
        if (Schema::hasColumn('portfolio_budget_items', 'month')) {
            Schema::table('portfolio_budget_items', function (Blueprint $table) {
                $table->dropColumn('month');
            });
        }
        if (Schema::hasColumn('portfolio_installments', 'month')) {
            Schema::table('portfolio_installments', function (Blueprint $table) {
                $table->dropColumn('month');
            });
        }
        if (Schema::hasColumn('portfolio_subscriptions', 'month')) {
            Schema::table('portfolio_subscriptions', function (Blueprint $table) {
                $table->dropColumn('month');
            });
        }

        // Revert income table to single-row-per-user
        Schema::dropIfExists('portfolio_income');
        Schema::create('portfolio_income', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();
            $table->decimal('income_amount', 18, 2);
            $table->timestamps();
        });
    }
};
