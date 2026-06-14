<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Convert กยศ from a fixed per-month savings schedule to per-installment
 * (per-year) targets with a flexible actual-payment log.
 *
 * Reality: the loan is paid in irregular chunks (the user started Apr 2026,
 * ฿1,017.50 each) toward each year's lump due 5 July — not ฿407/฿945 every
 * month. So we track 15 yearly targets (เงินต้น+ดอกเบี้ย straight from the loan
 * schedule, summing to ฿296,503.60) and log the real payments against each;
 * paid_amount on a งวด row is the sum of its logs.
 *
 * Only the กยศ debt is touched — other variable debts keep their per-month rows.
 */
return new class extends Migration
{
    private string $label = 'กองทุนเงินกู้ยืม กยศ';

    /** [งวด due month (YYYY-07), year target = เงินต้น+ดอกเบี้ย]. Sum = ฿296,503.60 */
    private array $periods = [
        ['2026-07',  4069.99],
        ['2027-07',  9456.28],
        ['2028-07', 10745.11],
        ['2029-07', 12020.38],
        ['2030-07', 13282.07],
        ['2031-07', 14530.21],
        ['2032-07', 15764.77],
        ['2033-07', 18342.43],
        ['2034-07', 20892.96],
        ['2035-07', 23416.36],
        ['2036-07', 25912.62],
        ['2037-07', 28381.75],
        ['2038-07', 30823.74],
        ['2039-07', 33238.60],
        ['2040-07', 35626.33],
    ];

    public function up(): void
    {
        if (!Schema::hasTable('portfolio_debt_payments')) {
            return;
        }

        // 1) Cumulative paid-amount per งวด.
        if (!Schema::hasColumn('portfolio_debt_payments', 'paid_amount')) {
            Schema::table('portfolio_debt_payments', function (Blueprint $table) {
                $table->decimal('paid_amount', 14, 2)->default(0)->after('amount');
            });
        }

        // 2) One row per real payment.
        if (!Schema::hasTable('portfolio_debt_payment_logs')) {
            Schema::create('portfolio_debt_payment_logs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('debt_payment_id')->constrained('portfolio_debt_payments')->cascadeOnDelete();
                $table->foreignId('user_id')->constrained()->cascadeOnDelete();
                $table->date('paid_on');
                $table->decimal('amount', 14, 2);
                $table->string('reference', 60)->nullable();
                $table->text('notes')->nullable();
                $table->timestamps();

                $table->index(['user_id', 'paid_on']);
                $table->index('debt_payment_id');
            });
        }

        // 3) Rebuild กยศ as 15 per-งวด rows.
        $userId = $this->resolveUserId();
        if (!$userId) {
            return;
        }

        $debt = DB::table('portfolio_debts')
            ->where('user_id', $userId)
            ->where('label', $this->label)
            ->first();
        if (!$debt) {
            return;
        }

        // Wipe old per-month rows (logs cascade away with them).
        DB::table('portfolio_debt_payments')->where('debt_id', $debt->id)->delete();

        $now = now()->toDateTimeString();
        foreach ($this->periods as [$month, $target]) {
            $paymentId = DB::table('portfolio_debt_payments')->insertGetId([
                'debt_id'     => $debt->id,
                'user_id'     => $userId,
                'month'       => $month,   // due month (5 July of that year)
                'amount'      => $target,  // year target
                'paid_amount' => 0,
                'is_paid'     => false,
                'notes'       => null,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);

            // Seed real งวด 1 payments — matches the กยศ app payment history.
            if ($month === '2026-07') {
                $entries = [
                    ['2026-04-29', 1017.00, '6904290000000048689'],
                    ['2026-05-28', 1017.50, '6905280000000040978'],
                ];
                $sum = 0.0;
                foreach ($entries as [$paidOn, $amt, $ref]) {
                    DB::table('portfolio_debt_payment_logs')->insert([
                        'debt_payment_id' => $paymentId,
                        'user_id'         => $userId,
                        'paid_on'         => $paidOn,
                        'amount'          => $amt,
                        'reference'       => $ref,
                        'notes'           => null,
                        'created_at'      => $now,
                        'updated_at'      => $now,
                    ]);
                    $sum += $amt;
                }
                DB::table('portfolio_debt_payments')->where('id', $paymentId)->update([
                    'paid_amount' => $sum,
                    'is_paid'     => $sum >= $target,
                    'updated_at'  => $now,
                ]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('portfolio_debt_payment_logs')) {
            Schema::dropIfExists('portfolio_debt_payment_logs');
        }

        if (Schema::hasColumn('portfolio_debt_payments', 'paid_amount')) {
            Schema::table('portfolio_debt_payments', function (Blueprint $table) {
                $table->dropColumn('paid_amount');
            });
        }

        // Remove the per-งวด rows this migration created.
        $userId = $this->resolveUserId();
        if ($userId) {
            $debt = DB::table('portfolio_debts')
                ->where('user_id', $userId)
                ->where('label', $this->label)
                ->first();
            if ($debt) {
                DB::table('portfolio_debt_payments')->where('debt_id', $debt->id)->delete();
            }
        }
    }

    private function resolveUserId(): ?int
    {
        $allowed = (array) config('portfolio.allowed_emails', []);
        $email   = !empty($allowed) ? strtolower($allowed[0]) : null;

        if ($email) {
            $user = DB::table('users')->where('email', $email)->first();
            if ($user) {
                return (int) $user->id;
            }
        }

        return DB::table('portfolio_budget_items')->value('user_id')
            ?? DB::table('portfolio_installments')->value('user_id')
            ?? DB::table('portfolio_debts')->value('user_id')
            ?? null;
    }
};
