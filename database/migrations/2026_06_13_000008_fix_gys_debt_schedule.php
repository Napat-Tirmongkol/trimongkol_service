<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Re-seed กยศ debt with the CORRECT 10-month-per-year schedule (Sep–Jun).
 *
 * Previous migrations 000005/000006 assumed 12 months/year (wrong).
 * The actual กยศ schedule from the CSV is 10 monthly collections per year,
 * collected September through June, with the annual sum due on July 5.
 *
 * This migration:
 *   1. Removes any existing กยศ debt + payments for this user (cleanup).
 *   2. Re-creates the debt with correct payment rows from Year 2 onwards
 *      (Year 1: Oct 2025 – Jul 2026 is considered settled).
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

        // ── 1. Remove stale กยศ data ──────────────────────────────────────
        $existing = DB::table('portfolio_debts')
            ->where('user_id', $userId)
            ->where('label', $this->label)
            ->first();

        if ($existing) {
            DB::table('portfolio_debt_payments')->where('debt_id', $existing->id)->delete();
            DB::table('portfolio_debts')->where('id', $existing->id)->delete();
        }

        // ── 2. Create debt record ─────────────────────────────────────────
        $debtId = DB::table('portfolio_debts')->insertGetId([
            'user_id'      => $userId,
            'label'        => $this->label,
            'total_amount' => 296503.60, // เงินต้น ฿271,332.84 + ดอกเบี้ย ฿25,170.76
            'notes'        => 'ชำระปีละ 10 งวด (ก.ย.–มิ.ย.) | ครบชำระ 5 ก.ค. ทุกปี | 15 ปี (2569–2583)',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        // ── 3. Build payment rows ─────────────────────────────────────────
        //
        // Year 1 (Oct 2025 – Jul 2026, ฿407/mo) is treated as settled.
        // Years 2–15: 10 months each, September through June.
        //
        // [start_YYYY-MM, num_months, monthly_amount]
        $periods = [
            ['2026-09', 10,   945.63],  // Year  2  due 05/07/2027
            ['2027-09', 10,  1074.51],  // Year  3
            ['2028-09', 10,  1202.04],  // Year  4
            ['2029-09', 10,  1328.21],  // Year  5
            ['2030-09', 10,  1453.02],  // Year  6
            ['2031-09', 10,  1576.48],  // Year  7
            ['2032-09', 10,  1834.24],  // Year  8
            ['2033-09', 10,  2089.30],  // Year  9
            ['2034-09', 10,  2341.64],  // Year 10
            ['2035-09', 10,  2591.26],  // Year 11
            ['2036-09', 10,  2838.17],  // Year 12
            ['2037-09', 10,  3082.37],  // Year 13
            ['2038-09', 10,  3323.86],  // Year 14
            ['2039-09', 10,  3562.63],  // Year 15  due 05/07/2040
        ];

        $rows = [];
        $now  = now()->toDateTimeString();

        foreach ($periods as [$start, $count, $amount]) {
            [$sy, $sm] = array_map('intval', explode('-', $start));

            for ($i = 0; $i < $count; $i++) {
                $total = $sm + $i - 1;
                $y     = $sy + intdiv($total, 12);
                $m     = ($total % 12) + 1;

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
        // Prefer config email (portfolio owner), fall back to existing data
        $allowed = (array) config('portfolio.allowed_emails', []);
        $email   = !empty($allowed) ? strtolower($allowed[0]) : null;

        if ($email) {
            $user = DB::table('users')->where('email', $email)->first();
            if ($user) return $user->id;
        }

        return DB::table('portfolio_budget_items')->value('user_id')
            ?? DB::table('portfolio_debts')->value('user_id')
            ?? null;
    }
};
