<?php

namespace App\Services\Accounting;

use App\Models\Accounting\Account;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

/**
 * Chart-of-accounts templates a new workspace can start from.
 *
 * STANDARD_TH is a generic Thai SME chart and the default seeded for every new
 * tenant — it carries no company-specific data. Each template keeps the same
 * `system_role` tags the posting/tax engine binds to (ar_control, ap_control,
 * vat_input/out, wht_payable_*, etc.), so the engine works no matter which
 * template a tenant picks.
 *
 * normal_balance is derived from `type` (debit for asset/expense), so it is not
 * repeated. `deductible => false` marks accounts excluded from CIT
 * (ค่าใช้จ่ายต้องห้าม).
 */
class ChartOfAccounts
{
    /**
     * Generic Thai SME chart — the shipped default. Covers cash/bank, AR/AP,
     * VAT (input/output + deferred), withholding (PND.3/53), equity, common
     * income and a standard spread of operating expenses.
     */
    public const STANDARD_TH = [
        // ── 1xxx Assets (normal balance: debit) ───────────────────────────
        ['code' => '1111-00', 'name' => 'เงินสด', 'name_en' => 'Cash', 'type' => 'asset'],
        ['code' => '1112-00', 'name' => 'เงินสดย่อย', 'name_en' => 'Petty cash', 'type' => 'asset'],
        ['code' => '1113-01', 'name' => 'เงินฝากธนาคาร — กระแสรายวัน', 'name_en' => 'Bank — current account', 'type' => 'asset'],
        ['code' => '1113-02', 'name' => 'เงินฝากธนาคาร — ออมทรัพย์', 'name_en' => 'Bank — savings account', 'type' => 'asset'],
        ['code' => '1130-01', 'name' => 'ลูกหนี้การค้า', 'name_en' => 'Trade accounts receivable', 'type' => 'asset', 'role' => 'ar_control'],
        ['code' => '1130-02', 'name' => 'เช็ครับลงวันที่ล่วงหน้า', 'name_en' => 'Post-dated cheques received', 'type' => 'asset'],
        ['code' => '1140-00', 'name' => 'สินค้าคงเหลือ', 'name_en' => 'Inventory', 'type' => 'asset'],
        ['code' => '1150-00', 'name' => 'ลูกหนี้อื่น', 'name_en' => 'Other receivables', 'type' => 'asset'],
        ['code' => '1151-02', 'name' => 'ภาษีเงินได้นิติบุคคลถูกหัก ณ ที่จ่าย', 'name_en' => 'Withheld corporate income tax', 'type' => 'asset', 'role' => 'wht_receivable'],
        ['code' => '1154-00', 'name' => 'ภาษีซื้อ', 'name_en' => 'Input VAT', 'type' => 'asset', 'role' => 'vat_input'],
        ['code' => '1155-00', 'name' => 'ภาษีซื้อ-ยังไม่ถึงกำหนด', 'name_en' => 'Input VAT — not yet due', 'type' => 'asset', 'role' => 'vat_input_deferred'],
        ['code' => '1155-01', 'name' => 'ภาษีซื้อ-รอใบกำกับ', 'name_en' => 'Input VAT — awaiting tax invoice', 'type' => 'asset', 'role' => 'vat_input_pending'],
        ['code' => '1160-00', 'name' => 'ค่าใช้จ่ายจ่ายล่วงหน้า', 'name_en' => 'Prepaid expenses', 'type' => 'asset'],
        ['code' => '1210-00', 'name' => 'ที่ดิน อาคารและอุปกรณ์', 'name_en' => 'Property, plant & equipment', 'type' => 'asset'],
        ['code' => '1220-00', 'name' => 'ค่าเสื่อมราคาสะสม', 'name_en' => 'Accumulated depreciation', 'type' => 'asset'],

        // ── 2xxx Liabilities (normal balance: credit) ─────────────────────
        ['code' => '2110-00', 'name' => 'เงินเบิกเกินบัญชีธนาคาร', 'name_en' => 'Bank overdraft', 'type' => 'liability'],
        ['code' => '2120-01', 'name' => 'เจ้าหนี้การค้า', 'name_en' => 'Trade accounts payable', 'type' => 'liability', 'role' => 'ap_control'],
        ['code' => '2120-02', 'name' => 'เช็คจ่ายลงวันที่ล่วงหน้า', 'name_en' => 'Post-dated cheques issued', 'type' => 'liability'],
        ['code' => '2131-00', 'name' => 'ค่าใช้จ่ายค้างจ่าย', 'name_en' => 'Accrued expenses', 'type' => 'liability'],
        ['code' => '2132-01', 'name' => 'ภาษีหัก ณ ที่จ่ายค้างจ่าย ภงด.1', 'name_en' => 'WHT payable (PND.1, payroll)', 'type' => 'liability'],
        ['code' => '2132-03', 'name' => 'ภาษีหัก ณ ที่จ่ายค้างจ่าย ภงด.3', 'name_en' => 'WHT payable (PND.3)', 'type' => 'liability', 'role' => 'wht_payable_pnd3'],
        ['code' => '2132-04', 'name' => 'ภาษีหัก ณ ที่จ่ายค้างจ่าย ภงด.53', 'name_en' => 'WHT payable (PND.53)', 'type' => 'liability', 'role' => 'wht_payable_pnd53'],
        ['code' => '2135-00', 'name' => 'ภาษีขาย', 'name_en' => 'Output VAT', 'type' => 'liability', 'role' => 'vat_output'],
        ['code' => '2136-00', 'name' => 'ภาษีขาย-รอเรียกเก็บ', 'name_en' => 'Output VAT — deferred', 'type' => 'liability', 'role' => 'vat_output_deferred'],
        ['code' => '2137-00', 'name' => 'เจ้าหนี้กรมสรรพากร', 'name_en' => 'Payable — Revenue Department', 'type' => 'liability'],
        ['code' => '2138-00', 'name' => 'เงินกู้ยืมจากกรรมการ', 'name_en' => 'Loan from director', 'type' => 'liability', 'role' => 'director_loan'],
        ['code' => '2140-00', 'name' => 'เงินกู้ยืมระยะยาว', 'name_en' => 'Long-term loans', 'type' => 'liability'],

        // ── 3xxx Equity (normal balance: credit) ──────────────────────────
        ['code' => '3100-00', 'name' => 'ทุนจดทะเบียนและชำระแล้ว', 'name_en' => 'Share capital', 'type' => 'equity', 'role' => 'capital'],
        ['code' => '3200-00', 'name' => 'กำไรสะสม', 'name_en' => 'Retained earnings', 'type' => 'equity', 'role' => 'retained_earnings'],

        // ── 4xxx Income (normal balance: credit) ──────────────────────────
        ['code' => '4100-01', 'name' => 'รายได้จากการขาย', 'name_en' => 'Sales revenue', 'type' => 'income'],
        ['code' => '4100-02', 'name' => 'รายได้จากการให้บริการ', 'name_en' => 'Service income', 'type' => 'income', 'role' => 'service_revenue'],
        ['code' => '4200-01', 'name' => 'ดอกเบี้ยรับ', 'name_en' => 'Interest income', 'type' => 'income'],
        ['code' => '4200-02', 'name' => 'รายได้อื่น', 'name_en' => 'Other income', 'type' => 'income'],

        // ── 5xxx Cost & expenses (normal balance: debit) ──────────────────
        ['code' => '5400-01', 'name' => 'ซื้อสินค้า/วัตถุดิบ', 'name_en' => 'Purchases', 'type' => 'expense'],
        ['code' => '5400-04', 'name' => 'ค่าจ้างและค่าบริการ', 'name_en' => 'Wages & service fees', 'type' => 'expense'],
        ['code' => '5300-01', 'name' => 'เงินเดือนและค่าแรง', 'name_en' => 'Salaries & wages', 'type' => 'expense'],
        ['code' => '5300-02', 'name' => 'ค่าสวัสดิการพนักงาน', 'name_en' => 'Staff welfare', 'type' => 'expense'],
        ['code' => '5310-01', 'name' => 'ค่าเช่า', 'name_en' => 'Rent', 'type' => 'expense'],
        ['code' => '5310-02', 'name' => 'ค่าน้ำค่าไฟ', 'name_en' => 'Utilities', 'type' => 'expense'],
        ['code' => '5310-03', 'name' => 'ค่าโทรศัพท์และอินเทอร์เน็ต', 'name_en' => 'Phone & internet', 'type' => 'expense'],
        ['code' => '5320-01', 'name' => 'ค่าเครื่องเขียนแบบพิมพ์', 'name_en' => 'Stationery & printed forms', 'type' => 'expense'],
        ['code' => '5320-03', 'name' => 'วัสดุสิ้นเปลือง', 'name_en' => 'Consumable supplies', 'type' => 'expense'],
        ['code' => '5330-01', 'name' => 'ค่าใช้จ่ายเดินทาง', 'name_en' => 'Travel expense', 'type' => 'expense'],
        ['code' => '5340-01', 'name' => 'ค่าโฆษณาและการตลาด', 'name_en' => 'Advertising & marketing', 'type' => 'expense'],
        ['code' => '5350-01', 'name' => 'ค่าซ่อมแซมและบำรุงรักษา', 'name_en' => 'Repairs & maintenance', 'type' => 'expense'],
        ['code' => '5360-04', 'name' => 'ค่าธรรมเนียมธนาคาร', 'name_en' => 'Bank charges', 'type' => 'expense'],
        ['code' => '5360-07', 'name' => 'ค่าบริการทำบัญชี', 'name_en' => 'Bookkeeping fee', 'type' => 'expense'],
        ['code' => '5360-09', 'name' => 'ค่าสอบบัญชี', 'name_en' => 'Audit fee', 'type' => 'expense'],
        ['code' => '5360-12', 'name' => 'ค่าอากรแสตมป์', 'name_en' => 'Stamp duty', 'type' => 'expense'],
        ['code' => '5370-01', 'name' => 'ค่าเสื่อมราคา', 'name_en' => 'Depreciation', 'type' => 'expense'],
        ['code' => '5370-08', 'name' => 'ภาษีเงินได้นิติบุคคล', 'name_en' => 'Corporate income tax', 'type' => 'expense', 'deductible' => false],
        ['code' => '5390-04', 'name' => 'ค่าใช้จ่ายต้องห้าม', 'name_en' => 'Non-deductible expenses', 'type' => 'expense', 'deductible' => false],
    ];

    /**
     * Named templates a tenant can seed from. The key is stored so onboarding
     * can offer a picker; STANDARD_TH is the default for every new workspace.
     *
     * @return array<string, array{name: string, name_en: string, accounts: array}>
     */
    public static function templates(): array
    {
        return [
            'standard_th' => [
                'name' => 'ผังบัญชีมาตรฐาน (ธุรกิจทั่วไป)',
                'name_en' => 'Standard Thai SME',
                'accounts' => self::STANDARD_TH,
            ],
        ];
    }

    /** The default chart seeded for a new workspace. */
    public static function template(): array
    {
        return self::STANDARD_TH;
    }

    /**
     * Seed $accounts (defaults to the standard Thai SME chart) into $workspace,
     * skipping codes that already exist. Idempotent and atomic.
     *
     * @return int  number of accounts created
     */
    public static function seed(Workspace $workspace, ?array $accounts = null): int
    {
        $accounts ??= self::STANDARD_TH;

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
