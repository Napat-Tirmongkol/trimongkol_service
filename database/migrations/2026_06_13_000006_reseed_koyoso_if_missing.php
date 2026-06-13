<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            !Schema::hasTable('portfolio_debts') ||
            !Schema::hasTable('portfolio_debt_payments') ||
            !Schema::hasTable('portfolio_budget_items')
        ) {
            return;
        }

        // Find the portfolio owner from existing budget data — avoids
        // depending on a hardcoded email that may differ in production.
        $userId = DB::table('portfolio_budget_items')->value('user_id')
            ?? DB::table('portfolio_holdings')->value('user_id');

        if (!$userId) return;

        // Idempotent — skip if กยศ debt already exists for this user
        if (DB::table('portfolio_debts')
            ->where('user_id', $userId)
            ->where('label', 'กองทุนเงินกู้ยืม กยศ')
            ->exists()
        ) {
            return;
        }

        $debtId = DB::table('portfolio_debts')->insertGetId([
            'user_id'      => $userId,
            'label'        => 'กองทุนเงินกู้ยืม กยศ',
            'total_amount' => 296503.60, // เงินต้น ฿271,332.84 + ดอกเบี้ย ฿25,170.76
            'notes'        => 'ชำระปีละครั้ง ทุกวันที่ 5 ก.ค. | 15 งวด (2569–2583) | ดอกเบี้ย 1% ของเงินต้นคงเหลือ',
            'created_at'   => now(),
            'updated_at'   => now(),
        ]);

        // Monthly collection schedule from the กยศ payment table
        // [start_month (YYYY-MM), number_of_months, monthly_amount]
        $periods = [
            ['2025-10', 10,   407.00],  // งวด 1: 10 mos (ต.ค. 2568–ก.ค. 2569)
            ['2026-08', 12,   945.63],  // งวด 2  (ส.ค. 2569–ก.ค. 2570)
            ['2027-08', 12,  1074.51],  // งวด 3
            ['2028-08', 12,  1202.04],  // งวด 4
            ['2029-08', 12,  1328.21],  // งวด 5
            ['2030-08', 12,  1453.02],  // งวด 6
            ['2031-08', 12,  1576.48],  // งวด 7
            ['2032-08', 12,  1834.24],  // งวด 8
            ['2033-08', 12,  2089.30],  // งวด 9
            ['2034-08', 12,  2341.64],  // งวด 10
            ['2035-08', 12,  2591.26],  // งวด 11
            ['2036-08', 12,  2838.17],  // งวด 12
            ['2037-08', 12,  3082.37],  // งวด 13
            ['2038-08', 12,  3323.86],  // งวด 14
            ['2039-08', 12,  3562.63],  // งวด 15
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
        $debt = DB::table('portfolio_debts')
            ->where('label', 'กองทุนเงินกู้ยืม กยศ')
            ->first();

        if ($debt) {
            DB::table('portfolio_debt_payments')->where('debt_id', $debt->id)->delete();
            DB::table('portfolio_debts')->where('id', $debt->id)->delete();
        }
    }
};
