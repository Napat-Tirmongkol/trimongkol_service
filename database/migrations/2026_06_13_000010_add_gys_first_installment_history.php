<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Add กยศ งวดที่ 1 (the first repayment year) back as paid history.
 *
 * 000009 reseeded the schedule starting at งวดที่ 2 (ก.ย. 2569 = Sep 2026,
 * ฿945.63) because งวดที่ 1's 10-month saving window (ก.ย. 2568 – มิ.ย. 2569 =
 * Sep 2025 – Jun 2026) is already in the past. That left the schedule one year
 * short of the source spreadsheet: the stored total_amount (฿296,503.60)
 * includes งวด 1, but no monthly rows did — so the budget progress bar and the
 * dashboard กยศ widget were both off by ฿4,069.99 (the "missing" ฿407 × 10).
 *
 * This inserts งวด 1 as 10 rows (฿407 each) marked paid, so the full
 * 15-installment schedule matches the CSV exactly. The forward debt plan
 * (/portfolio/debts) filters to month >= next month, so these past rows never
 * appear there — only in the budget list and dashboard history.
 */
return new class extends Migration
{
    private string $label = 'กองทุนเงินกู้ยืม กยศ';

    /** งวดที่ 1 saving months (Sep 2025 – Jun 2026), ฿407 each, already collected. */
    private array $months = [
        '2025-09', '2025-10', '2025-11', '2025-12',
        '2026-01', '2026-02', '2026-03', '2026-04', '2026-05', '2026-06',
    ];

    private float $amount = 407.00;

    public function up(): void
    {
        if (
            !Schema::hasTable('portfolio_debts') ||
            !Schema::hasTable('portfolio_debt_payments')
        ) {
            return;
        }

        $userId = $this->resolveUserId();
        if (!$userId) {
            return;
        }

        $debt = DB::table('portfolio_debts')
            ->where('user_id', $userId)
            ->where('label', $this->label)
            ->first();

        // 000009 creates the debt; if it is gone there is nothing to attach to.
        if (!$debt) {
            return;
        }

        // Idempotent: only insert months that are not already present.
        $existing = DB::table('portfolio_debt_payments')
            ->where('debt_id', $debt->id)
            ->whereIn('month', $this->months)
            ->pluck('month')
            ->all();

        $now  = now()->toDateTimeString();
        $rows = [];
        foreach ($this->months as $month) {
            if (in_array($month, $existing, true)) {
                continue;
            }
            $rows[] = [
                'debt_id'    => $debt->id,
                'user_id'    => $userId,
                'month'      => $month,
                'amount'     => $this->amount,
                'is_paid'    => true,
                'notes'      => null,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        if ($rows) {
            DB::table('portfolio_debt_payments')->insert($rows);
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('portfolio_debt_payments')) {
            return;
        }

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

        DB::table('portfolio_debt_payments')
            ->where('debt_id', $debt->id)
            ->whereIn('month', $this->months)
            ->delete();
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
