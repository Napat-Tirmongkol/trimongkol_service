<?php

namespace App\Services\Accounting;

use App\Models\Accounting\Account;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

/**
 * The Tirmongkol Service chart of accounts, transcribed from the accountant's
 * trial balance (งบทดลอง 31-12-2568). Used to seed the company's own
 * workspace; other tenants would start from a generic Thai template.
 *
 * normal_balance is derived from `type` (debit for asset/expense), so it is
 * not repeated. `role` tags the accounts the posting/tax engine binds to;
 * `deductible => false` marks accounts excluded from CIT (ค่าใช้จ่ายต้องห้าม).
 */
class ChartOfAccounts
{
    public const TIRMONGKOL_SERVICE = [
        // ── 1xxx Assets (normal balance: debit) ───────────────────────────
        ['code' => '1111-00', 'name' => 'เงินสด', 'name_en' => 'Cash', 'type' => 'asset'],
        ['code' => '1113-01', 'name' => 'เงินฝากออมทรัพย์ ธ.ก.ส. 020235299249', 'name_en' => 'Savings — BAAC 020235299249', 'type' => 'asset'],
        ['code' => '1113-02', 'name' => 'เงินฝากออมทรัพย์ KTB 714-0-91907-0', 'name_en' => 'Savings — KTB 714-0-91907-0', 'type' => 'asset'],
        ['code' => '1113-03', 'name' => 'เงินฝากออมทรัพย์ KTB 017-0-49132-3', 'name_en' => 'Savings — KTB 017-0-49132-3', 'type' => 'asset'],
        ['code' => '1113-04', 'name' => 'เงินฝากออมทรัพย์ KTB 017-0-50272-4', 'name_en' => 'Savings — KTB 017-0-50272-4', 'type' => 'asset'],
        ['code' => '1113-05', 'name' => 'เงินฝากออมทรัพย์ KTB 017-0-56078-3', 'name_en' => 'Savings — KTB 017-0-56078-3', 'type' => 'asset'],
        ['code' => '1113-06', 'name' => 'เงินฝากออมทรัพย์ KTB 017-0-60376-8', 'name_en' => 'Savings — KTB 017-0-60376-8', 'type' => 'asset'],
        ['code' => '1114-01', 'name' => 'สลากออมทรัพย์ ธ.ก.ส. 400046598996', 'name_en' => 'Savings lottery — BAAC 400046598996', 'type' => 'asset'],
        ['code' => '1130-01', 'name' => 'ลูกหนี้การค้า', 'name_en' => 'Trade accounts receivable', 'type' => 'asset', 'role' => 'ar_control'],
        ['code' => '1130-02', 'name' => 'เช็ครับลงวันที่ล่วงหน้า', 'name_en' => 'Post-dated cheques received', 'type' => 'asset'],
        ['code' => '1151-02', 'name' => 'ภาษีนิติบุคคลจ่ายล่วงหน้า', 'name_en' => 'Prepaid corporate income tax', 'type' => 'asset', 'role' => 'wht_receivable'],
        ['code' => '1151-05', 'name' => 'ภาษีเงินได้นิติบุคคลจ่ายล่วงหน้า ภงด.51', 'name_en' => 'Prepaid CIT (PND.51)', 'type' => 'asset'],
        ['code' => '1151-06', 'name' => 'ภาษีเงินได้นิติบุคคลจ่ายล่วงหน้าปี 2567', 'name_en' => 'Prepaid CIT (FY2567)', 'type' => 'asset'],
        ['code' => '1154-00', 'name' => 'ภาษีซื้อ', 'name_en' => 'Input VAT', 'type' => 'asset', 'role' => 'vat_input'],
        ['code' => '1155-00', 'name' => 'ภาษีซื้อ-ยังไม่ถึงกำหนด', 'name_en' => 'Input VAT — not yet due', 'type' => 'asset', 'role' => 'vat_input_deferred'],
        ['code' => '1155-01', 'name' => 'ภาษีซื้อ-รอใบกำกับ', 'name_en' => 'Input VAT — awaiting tax invoice', 'type' => 'asset', 'role' => 'vat_input_pending'],
        ['code' => '1156-00', 'name' => 'ลูกหนี้-กรมสรรพากร', 'name_en' => 'Receivable — Revenue Department', 'type' => 'asset'],

        // ── 2xxx Liabilities (normal balance: credit) ─────────────────────
        ['code' => '2120-01', 'name' => 'เจ้าหนี้การค้า', 'name_en' => 'Trade accounts payable', 'type' => 'liability', 'role' => 'ap_control'],
        ['code' => '2131-09', 'name' => 'ค่าใช้จ่ายค้างจ่าย-อื่น ๆ', 'name_en' => 'Accrued expenses — other', 'type' => 'liability'],
        ['code' => '2131-10', 'name' => 'ค่าสอบบัญชีค้างจ่าย', 'name_en' => 'Accrued audit fee', 'type' => 'liability'],
        ['code' => '2131-11', 'name' => 'ค่าประกาศหนังสือพิมพ์ค้างจ่าย', 'name_en' => 'Accrued newspaper announcement', 'type' => 'liability'],
        ['code' => '2131-12', 'name' => 'ค่าจ้างค่าบริการค้างจ่าย', 'name_en' => 'Accrued service fees', 'type' => 'liability'],
        ['code' => '2132-03', 'name' => 'ภาษีหัก ณ ที่จ่ายค้างจ่าย ภงด.3', 'name_en' => 'WHT payable (PND.3)', 'type' => 'liability', 'role' => 'wht_payable_pnd3'],
        ['code' => '2132-04', 'name' => 'ภาษีหัก ณ ที่จ่ายค้างจ่าย ภงด.53', 'name_en' => 'WHT payable (PND.53)', 'type' => 'liability', 'role' => 'wht_payable_pnd53'],
        ['code' => '2135-00', 'name' => 'ภาษีขาย', 'name_en' => 'Output VAT', 'type' => 'liability', 'role' => 'vat_output'],
        ['code' => '2136-00', 'name' => 'ภาษีขาย-รอเรียกเก็บ', 'name_en' => 'Output VAT — deferred', 'type' => 'liability', 'role' => 'vat_output_deferred'],
        ['code' => '2137-00', 'name' => 'เจ้าหนี้กรมสรรพากร', 'name_en' => 'Payable — Revenue Department', 'type' => 'liability'],
        ['code' => '2138-00', 'name' => 'เงินกู้ยืมจากกรรมการ', 'name_en' => 'Loan from director', 'type' => 'liability', 'role' => 'director_loan'],

        // ── 3xxx Equity (normal balance: credit) ──────────────────────────
        ['code' => '3100-00', 'name' => 'ทุน', 'name_en' => 'Share capital', 'type' => 'equity', 'role' => 'capital'],
        ['code' => '3200-00', 'name' => 'กำไรสะสม', 'name_en' => 'Retained earnings', 'type' => 'equity', 'role' => 'retained_earnings'],

        // ── 4xxx Income (normal balance: credit) ──────────────────────────
        ['code' => '4100-02', 'name' => 'รายได้จากการให้บริการ', 'name_en' => 'Service income', 'type' => 'income', 'role' => 'service_revenue'],
        ['code' => '4200-01', 'name' => 'ดอกเบี้ยรับ', 'name_en' => 'Interest income', 'type' => 'income'],

        // ── 5xxx Expenses (normal balance: debit) ─────────────────────────
        ['code' => '5200-01', 'name' => 'ค่านายหน้า', 'name_en' => 'Commission expense', 'type' => 'expense'],
        ['code' => '5200-06', 'name' => 'ค่ารับรอง', 'name_en' => 'Entertainment expense', 'type' => 'expense'],
        ['code' => '5200-09', 'name' => 'ค่าใช้จ่ายเดินทางและยานพาหนะ', 'name_en' => 'Travel & vehicle expense', 'type' => 'expense'],
        ['code' => '5200-11', 'name' => 'ค่าติดต่อประสานงาน', 'name_en' => 'Coordination expense', 'type' => 'expense'],
        ['code' => '5310-12', 'name' => 'ค่าอบรมสัมมนา', 'name_en' => 'Training & seminar', 'type' => 'expense'],
        ['code' => '5310-17', 'name' => 'ค่าสวัสดิการอื่น ๆ', 'name_en' => 'Other staff welfare', 'type' => 'expense'],
        ['code' => '5310-18', 'name' => 'ค่าที่ปรึกษา', 'name_en' => 'Consultancy fee', 'type' => 'expense'],
        ['code' => '5310-19', 'name' => 'ค่าตอบแทนกรรมการ', 'name_en' => 'Director remuneration', 'type' => 'expense'],
        ['code' => '5320-01', 'name' => 'ค่าเครื่องเขียนแบบพิมพ์', 'name_en' => 'Stationery & printed forms', 'type' => 'expense'],
        ['code' => '5320-03', 'name' => 'วัสดุสิ้นเปลือง', 'name_en' => 'Consumable supplies', 'type' => 'expense'],
        ['code' => '5360-04', 'name' => 'ค่าธรรมเนียมธนาคาร', 'name_en' => 'Bank charges', 'type' => 'expense'],
        ['code' => '5360-06', 'name' => 'ค่าธรรมเนียมหนังสือค้ำประกัน', 'name_en' => 'Bank guarantee fee', 'type' => 'expense'],
        ['code' => '5360-07', 'name' => 'ค่าบริการทำบัญชี', 'name_en' => 'Bookkeeping fee', 'type' => 'expense'],
        ['code' => '5360-08', 'name' => 'ค่าธรรมเนียม', 'name_en' => 'Fees', 'type' => 'expense'],
        ['code' => '5360-09', 'name' => 'ค่าสอบบัญชี', 'name_en' => 'Audit fee', 'type' => 'expense'],
        ['code' => '5360-10', 'name' => 'ค่าประกาศหนังสือพิมพ์', 'name_en' => 'Newspaper announcement', 'type' => 'expense'],
        ['code' => '5360-11', 'name' => 'ค่าบริการจัดทำป้ายโครงการ', 'name_en' => 'Project signage service', 'type' => 'expense'],
        ['code' => '5360-12', 'name' => 'ค่าอากรแสตมป์', 'name_en' => 'Stamp duty', 'type' => 'expense'],
        ['code' => '5370-08', 'name' => 'ภาษีนิติบุคคลตัดจ่าย', 'name_en' => 'Corporate income tax expense', 'type' => 'expense', 'deductible' => false],
        ['code' => '5390-04', 'name' => 'ค่าใช้จ่ายต้องห้าม', 'name_en' => 'Non-deductible expenses', 'type' => 'expense', 'deductible' => false],
        ['code' => '5400-01', 'name' => 'ซื้อวัตถุดิบ', 'name_en' => 'Raw material purchases', 'type' => 'expense'],
        ['code' => '5400-04', 'name' => 'ค่าจ้างและค่าบริการ', 'name_en' => 'Wages & service fees', 'type' => 'expense'],
    ];

    /** @return array<int, array<string, mixed>> */
    public static function template(): array
    {
        return self::TIRMONGKOL_SERVICE;
    }

    /**
     * Seed $accounts (defaults to the Tirmongkol chart) into $workspace,
     * skipping codes that already exist. Idempotent and atomic.
     *
     * @return int  number of accounts created
     */
    public static function seed(Workspace $workspace, ?array $accounts = null): int
    {
        $accounts ??= self::TIRMONGKOL_SERVICE;

        return DB::transaction(function () use ($workspace, $accounts) {
            $existing = Account::query()->forWorkspace($workspace)->pluck('code')->all();
            $created = 0;

            foreach ($accounts as $row) {
                if (in_array($row['code'], $existing, true)) {
                    continue;
                }

                Account::create([
                    'workspace_id' => $workspace->id,
                    'code' => $row['code'],
                    'name' => $row['name'],
                    'name_en' => $row['name_en'] ?? null,
                    'type' => $row['type'],
                    'normal_balance' => Account::normalBalanceFor($row['type']),
                    'system_role' => $row['role'] ?? null,
                    'is_tax_deductible' => $row['deductible'] ?? true,
                ]);
                $created++;
            }

            return $created;
        });
    }
}
