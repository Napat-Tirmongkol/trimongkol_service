<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Force-reseed กยศ debt schedule regardless of prior state.
 *
 * 000008 already ran once but the debt may have been deleted via the UI
 * afterwards. This migration unconditionally wipes any existing กยศ record
 * and inserts a fresh set of payment rows.
 */
return new class extends Migration
{
    private string $label = 'กองทุนเงินกู้ยืม กยศ';

    public function up(): void
    {
        if (
            !Schema::hasTable('portfolio_debts') ||
            !Schema::hasTable('portfolio_debt_payments')
        ) {
            return;
        }

        $userId = $this->resolveUserId();
        if (!$userId) return;

        // Always wipe and recreate so this is safe to run in any state
        $old = DB::table('portfolio_debts')
            ->where('user_id', $userId)
            ->where('label', $this->label)
            ->first();

        if ($old) {
            DB::table('portfolio_debt_payments')->where('debt_id', $old->id)->delete();
            DB::table('portfolio_debts')->where('id', $old->id)->delete();
        }

        $debtId = DB::table('portfolio_debts')->insertGetId([
            'user_id'      => $userId,
            'label'        => $this->label,
            'total_amount' => 296503.60,
            'notes'        => 'ชำระปีละ 10 งวด (ก.ย.–มิ.ย.) | ครบชำระ 5 ก.ค. ทุกปี | 15 ปี (2569–2583)',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        // Years 2–15: 10 payments each, September through June
        // Year 1 (Oct 2025 – Jul 2026, ฿407/mo) is considered settled
        $periods = [
            ['2026-09', 10,   945.63],
            ['2027-09', 10,  1074.51],
            ['2028-09', 10,  1202.04],
            ['2029-09', 10,  1328.21],
            ['2030-09', 10,  1453.02],
            ['2031-09', 10,  1576.48],
            ['2032-09', 10,  1834.24],
            ['2033-09', 10,  2089.30],
            ['2034-09', 10,  2341.64],
            ['2035-09', 10,  2591.26],
            ['2036-09', 10,  2838.17],
            ['2037-09', 10,  3082.37],
            ['2038-09', 10,  3323.86],
            ['2039-09', 10,  3562.63],
        ];

        $rows = [];
        $now  = now()->toDateTimeString();

        foreach ($periods as [$start, $count, $amount]) {
            [$sy, $sm] = array_map('intval', explode('-', $start));
            for ($i = 0; $i < $count; $i++) {
                $total = $sm + $i - 1;
                $y = $sy + intdiv($total, 12);
                $m = ($total % 12) + 1;
                $rows[] = [
                    'debt_id'    => $debtId,
                    'user_id'    => $userId,
                    'month'      => sprintf('%04d-%02d', $y, $m),
                    'amount'     => $amount,
                    'is_paid'    => false,
                    'notes'      => null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        DB::table('portfolio_debt_payments')->insert($rows);
    }

    public function down(): void
    {
        $userId = $this->resolveUserId();
        if (!$userId) return;

        $debt = DB::table('portfolio_debts')
            ->where('user_id', $userId)
            ->where('label', $this->label)
            ->first();

        if ($debt) {
            DB::table('portfolio_debt_payments')->where('debt_id', $debt->id)->delete();
            DB::table('portfolio_debts')->where('id', $debt->id)->delete();
        }
    }

    private function resolveUserId(): ?int
    {
        $allowed = (array) config('portfolio.allowed_emails', []);
        $email   = !empty($allowed) ? strtolower($allowed[0]) : null;

        if ($email) {
            $user = DB::table('users')->where('email', $email)->first();
            if ($user) return (int) $user->id;
        }

        return DB::table('portfolio_budget_items')->value('user_id')
            ?? DB::table('portfolio_installments')->value('user_id')
            ?? DB::table('portfolio_debts')->value('user_id')
            ?? null;
    }
};
