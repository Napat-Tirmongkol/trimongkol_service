<?php

namespace App\Services\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\AccountingUser;
use App\Models\Accounting\Budget;
use App\Models\Accounting\Department;
use App\Models\Accounting\Partner;
use App\Models\Accounting\Period;
use App\Models\Accounting\TaxCode;
use App\Models\Workspace;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Builds a fully populated demo accounting workspace from scratch in one
 * transaction. All transactional data goes through the real services
 * (SalesInvoicing, Purchasing, Receipts, SupplierPayments, LedgerPosting,
 * Inventory, RecurringJournals, FixedAssets) so it tie-outs exactly the way
 * production data would — no raw inserts that bypass balance checks or
 * period guards.
 *
 * Returns the new workspace plus generated accounting-user credentials so
 * the admin trigger can hand them to the user.
 */
class DemoSeeder
{
    /**
     * @return array{workspace: Workspace, login_email: string, login_password: string}
     */
    public static function seed(): array
    {
        return DB::transaction(function () {
            $workspace = self::createWorkspace();
            self::bootstrap($workspace);

            $rawPassword = Str::random(10);
            $user = self::createAccountingUser($workspace, $rawPassword);

            $departments = self::createDepartments($workspace);
            $customers = self::createCustomers($workspace);
            $vendors = self::createVendors($workspace);
            self::createProducts($workspace);

            self::createSalesData($workspace, $customers, $departments);
            self::createPurchaseData($workspace, $vendors, $departments);
            self::createManualJournals($workspace, $departments);
            self::createRecurringJournals($workspace);
            self::createBudgets($workspace, $departments);

            return [
                'workspace' => $workspace,
                'login_email' => $user->email,
                'login_password' => $rawPassword,
            ];
        });
    }

    // ── Workspace + bootstrap ──────────────────────────────────────────────

    private static function createWorkspace(): Workspace
    {
        // Sequential suffix so re-running yields demo-1, demo-2, …
        $next = Workspace::where('slug', 'like', 'demo-%')->count() + 1;
        $slug = 'demo-'.$next;

        $workspace = Workspace::create([
            'name' => "บริษัทตัวอย่าง #{$next} จำกัด",
            'slug' => $slug,
            'company_name' => "บริษัทตัวอย่าง #{$next} จำกัด",
            'tax_id' => '0105566'.str_pad((string) $next, 6, '0', STR_PAD_LEFT),
            'branch' => 'สำนักงานใหญ่',
            'phone' => '02-000-0000',
            'company_address' => '123 ถนนสุขุมวิท แขวงคลองตัน เขตคลองเตย กรุงเทพฯ 10110',
            'accounting_plan' => 'pro',  // unlock everything for the demo
            'accounting_plan_until' => now()->addYear(),
            'chart_template' => 'standard_th',
            'onboarded_at' => now(),
        ]);

        return $workspace;
    }

    private static function bootstrap(Workspace $workspace): void
    {
        ChartOfAccounts::seed($workspace, ChartOfAccounts::template());
        TaxCodes::seedDefault($workspace);

        // Open periods for last year and this year — covers our 100-day window
        // regardless of when the seeder runs.
        foreach ([now()->year - 1, now()->year] as $year) {
            Period::firstOrCreate(
                ['workspace_id' => $workspace->id, 'starts_on' => "{$year}-01-01"],
                ['name' => (string) ($year + 543), 'ends_on' => "{$year}-12-31", 'status' => Period::STATUS_OPEN],
            );
        }
    }

    private static function createAccountingUser(Workspace $workspace, string $rawPassword): AccountingUser
    {
        return AccountingUser::create([
            'workspace_id' => $workspace->id,
            'name' => 'เจ้าของบริษัทตัวอย่าง',
            'email' => 'demo+'.$workspace->id.'@trimongkol.com',
            'password' => Hash::make($rawPassword),
            'role' => 'owner',
            'is_active' => true,
            'is_demo' => true,
        ]);
    }

    // ── Master data ────────────────────────────────────────────────────────

    /** @return array<int, Department> */
    private static function createDepartments(Workspace $workspace): array
    {
        return collect([
            ['code' => 'HQ', 'name' => 'สำนักงานใหญ่'],
            ['code' => 'BR1', 'name' => 'สาขา 1 - สีลม'],
            ['code' => 'BR2', 'name' => 'สาขา 2 - ลาดพร้าว'],
        ])->map(fn ($d) => Department::create([
            'workspace_id' => $workspace->id,
            'code' => $d['code'],
            'name' => $d['name'],
            'is_active' => true,
        ]))->all();
    }

    /** @return array<int, Partner> */
    private static function createCustomers(Workspace $workspace): array
    {
        $rows = [
            ['code' => 'C001', 'name' => 'บริษัท ลูกค้าใหญ่ จำกัด', 'tax_id' => '0105560000111'],
            ['code' => 'C002', 'name' => 'ห้างหุ้นส่วน เอบีซี เทรดดิ้ง', 'tax_id' => '0105560000222'],
            ['code' => 'C003', 'name' => 'บริษัท สมาร์ทช็อป จำกัด', 'tax_id' => '0105560000333'],
            ['code' => 'C004', 'name' => 'บริษัท สีลม คอร์ปอเรชั่น จำกัด', 'tax_id' => '0105560000444'],
            ['code' => 'C005', 'name' => 'ร้านค้าปลีก ก.รุ่งเรือง', 'tax_id' => '1102000111222'],
        ];
        return collect($rows)->map(fn ($r) => Partner::create([
            'workspace_id' => $workspace->id,
            'code' => $r['code'],
            'name' => $r['name'],
            'tax_id' => $r['tax_id'],
            'branch_code' => '00000',
            'is_customer' => true,
            'is_vendor' => false,
            'credit_days' => 30,
        ]))->all();
    }

    /** @return array<int, Partner> */
    private static function createVendors(Workspace $workspace): array
    {
        $rows = [
            ['code' => 'V001', 'name' => 'บริษัท ผู้ขายหลัก จำกัด', 'tax_id' => '0105560100111'],
            ['code' => 'V002', 'name' => 'บริษัท ออฟฟิศ ซัพพลาย จำกัด', 'tax_id' => '0105560100222'],
            ['code' => 'V003', 'name' => 'ห้างหุ้นส่วน ขนส่งด่วน', 'tax_id' => '0105560100333'],
            ['code' => 'V004', 'name' => 'บริษัท ค่าเช่าอสังหา จำกัด', 'tax_id' => '0105560100444'],
            ['code' => 'V005', 'name' => 'การไฟฟ้านครหลวง', 'tax_id' => '0994000165498'],
        ];
        return collect($rows)->map(fn ($r) => Partner::create([
            'workspace_id' => $workspace->id,
            'code' => $r['code'],
            'name' => $r['name'],
            'tax_id' => $r['tax_id'],
            'branch_code' => '00000',
            'is_customer' => false,
            'is_vendor' => true,
            'credit_days' => 30,
        ]))->all();
    }

    private static function createProducts(Workspace $workspace): void
    {
        $inventoryAccountId = self::accountByCode($workspace, '1140-00')->id;
        $cogsAccountId = self::accountByCode($workspace, '5410-00')->id;
        $clearingAccountId = self::accountByCode($workspace, '1113-02')->id;

        $rows = [
            ['sku' => 'SKU-001', 'name' => 'สินค้าตัวอย่าง A', 'unit_cost' => 100, 'initial_qty' => 100],
            ['sku' => 'SKU-002', 'name' => 'สินค้าตัวอย่าง B', 'unit_cost' => 250, 'initial_qty' => 50],
            ['sku' => 'SKU-003', 'name' => 'สินค้าตัวอย่าง C', 'unit_cost' => 500, 'initial_qty' => 30],
            ['sku' => 'SKU-004', 'name' => 'สินค้าตัวอย่าง D', 'unit_cost' => 75, 'initial_qty' => 200],
            ['sku' => 'SKU-005', 'name' => 'สินค้าตัวอย่าง E', 'unit_cost' => 1200, 'initial_qty' => 15],
        ];

        foreach ($rows as $r) {
            $product = Inventory::register($workspace, [
                'sku' => $r['sku'],
                'name' => $r['name'],
                'unit' => 'ชิ้น',
                'inventory_account_id' => $inventoryAccountId,
                'cogs_account_id' => $cogsAccountId,
            ]);
            // Seed opening stock via a real movement so the inventory account is funded.
            Inventory::receive($product, [
                'movement_date' => now()->subDays(120)->toDateString(),
                'quantity' => $r['initial_qty'],
                'unit_cost' => $r['unit_cost'],
                'reference' => 'OPENING',
                'note' => 'ยกมา',
            ], $clearingAccountId);
        }
    }

    // ── Transactions ───────────────────────────────────────────────────────

    /** 25 invoices spread across 100 days with a mix of statuses. */
    private static function createSalesData(Workspace $workspace, array $customers, array $departments): void
    {
        $salesAccount = self::accountByCode($workspace, '4100-01');
        $vat = TaxCode::query()->forWorkspace($workspace)->where('code', 'VAT7')->first();
        $bankAccount = self::accountByCode($workspace, '1113-02');

        // (daysAgo, customer index, dept index, unitPrice, quantity, status)
        // status: paid|partial|issued|draft|void
        $plan = [
            // 90+ days bucket
            [105, 0, 0, 18000, 2, 'paid'],
            [100, 1, 1, 12500, 1, 'paid'],
            [98, 2, 0, 8500, 3, 'partial'],
            [95, 3, 2, 45000, 1, 'issued'],
            [92, 4, 0, 6800, 5, 'issued'],
            // 61-90 days bucket
            [85, 0, 1, 22000, 1, 'paid'],
            [78, 1, 0, 15500, 2, 'paid'],
            [72, 2, 1, 9800, 4, 'partial'],
            [68, 3, 0, 38000, 1, 'issued'],
            // 31-60 days bucket
            [55, 0, 2, 16500, 3, 'paid'],
            [50, 1, 1, 11200, 2, 'paid'],
            [45, 2, 0, 7300, 5, 'partial'],
            [40, 3, 1, 28000, 1, 'paid'],
            [35, 4, 2, 5400, 6, 'paid'],
            // 1-30 days bucket
            [25, 0, 0, 19500, 2, 'paid'],
            [20, 1, 2, 13800, 3, 'partial'],
            [15, 2, 1, 9200, 4, 'issued'],
            [10, 3, 0, 24500, 1, 'paid'],
            // current bucket
            [7, 0, 1, 17500, 2, 'paid'],
            [5, 1, 0, 8900, 5, 'issued'],
            [3, 2, 2, 12700, 3, 'paid'],
            [1, 3, 1, 6500, 4, 'partial'],
            // edge cases
            [60, 4, 0, 14000, 2, 'void'],
            [50, 0, 1, 8800, 3, 'draft'],
            [2, 4, 2, 21000, 1, 'draft'],
        ];

        foreach ($plan as $row) {
            [$daysAgo, $custIdx, $deptIdx, $unitPrice, $qty, $status] = $row;
            $issueDate = now()->subDays($daysAgo);
            $customer = $customers[$custIdx];
            $dept = $departments[$deptIdx] ?? null;

            $invoice = SalesInvoicing::create($workspace, [
                'partner_id' => $customer->id,
                'issue_date' => $issueDate->toDateString(),
                'due_date' => $issueDate->copy()->addDays(30)->toDateString(),
                'memo' => 'ขายสินค้าตัวอย่าง',
            ], [[
                'account_id' => $salesAccount->id,
                'description' => 'สินค้าและบริการ',
                'quantity' => $qty,
                'unit_price' => $unitPrice,
                'tax_code_id' => $vat?->id,
                'department_id' => $dept?->id,
            ]]);

            if ($status === 'draft') {
                continue;
            }

            SalesInvoicing::issue($invoice);

            if ($status === 'void') {
                SalesInvoicing::void($invoice);
                continue;
            }

            if (in_array($status, ['paid', 'partial'], true)) {
                $total = (float) $invoice->total;
                $cash = $status === 'paid' ? $total : round($total * 0.5, 2);
                $paymentDate = $issueDate->copy()->addDays(min(20, max(1, $daysAgo - 5)));
                if ($paymentDate->isFuture()) {
                    $paymentDate = now();
                }

                Receipts::record($workspace, [
                    'partner_id' => $customer->id,
                    'account_id' => $bankAccount->id,
                    'payment_date' => $paymentDate->toDateString(),
                    'amount' => $cash,
                    'wht_amount' => 0,
                    'slip_ref' => 'RCV-'.Str::upper(Str::random(6)),
                ], [['invoice_id' => $invoice->id, 'amount' => $cash]]);
            }
        }
    }

    /** 20 bills spread across 100 days with mix of statuses. */
    private static function createPurchaseData(Workspace $workspace, array $vendors, array $departments): void
    {
        $purchaseAccount = self::accountByCode($workspace, '5400-01');
        $rentAccount = self::accountByCode($workspace, '5310-01');
        $utilAccount = self::accountByCode($workspace, '5310-02');
        $vatInput = TaxCode::query()->forWorkspace($workspace)->where('code', 'VAT7P')->first();
        $bankAccount = self::accountByCode($workspace, '1113-02');

        $expenseAccounts = [$purchaseAccount, $rentAccount, $utilAccount];

        $plan = [
            // (daysAgo, vendor idx, dept idx, amount, status)
            [102, 0, 0, 35000, 'paid'],
            [95, 1, 1, 8500, 'paid'],
            [88, 2, 0, 4200, 'partial'],
            [82, 3, 2, 25000, 'issued'],
            [75, 0, 0, 18500, 'paid'],
            [68, 4, 1, 6800, 'paid'],
            [60, 1, 2, 5500, 'partial'],
            [55, 2, 0, 7200, 'paid'],
            [48, 3, 1, 28000, 'issued'],
            [42, 0, 2, 22000, 'paid'],
            [35, 4, 0, 4500, 'paid'],
            [28, 1, 1, 9200, 'partial'],
            [22, 2, 2, 6300, 'paid'],
            [15, 3, 0, 31000, 'paid'],
            [10, 0, 1, 15500, 'issued'],
            [7, 4, 2, 5800, 'paid'],
            [3, 1, 0, 11200, 'issued'],
            [70, 2, 1, 7800, 'void'],
            [40, 0, 0, 9800, 'draft'],
            [2, 3, 2, 12500, 'draft'],
        ];

        foreach ($plan as $i => $row) {
            [$daysAgo, $venIdx, $deptIdx, $amount, $status] = $row;
            $issueDate = now()->subDays($daysAgo);
            $vendor = $vendors[$venIdx];
            $dept = $departments[$deptIdx] ?? null;
            $expense = $expenseAccounts[$i % 3];

            $bill = Purchasing::create($workspace, [
                'partner_id' => $vendor->id,
                'issue_date' => $issueDate->toDateString(),
                'due_date' => $issueDate->copy()->addDays(30)->toDateString(),
                'bill_ref' => 'INV-V'.str_pad((string) ($i + 1), 4, '0', STR_PAD_LEFT),
                'memo' => 'ค่าใช้จ่ายตัวอย่าง',
            ], [[
                'account_id' => $expense->id,
                'description' => $expense->name,
                'quantity' => 1,
                'unit_price' => $amount,
                'tax_code_id' => $vatInput?->id,
                'department_id' => $dept?->id,
            ]]);

            if ($status === 'draft') {
                continue;
            }

            Purchasing::post($bill);

            if ($status === 'void') {
                Purchasing::void($bill);
                continue;
            }

            if (in_array($status, ['paid', 'partial'], true)) {
                $total = (float) $bill->total;
                $payAmount = $status === 'paid' ? $total : round($total * 0.5, 2);
                $paymentDate = $issueDate->copy()->addDays(min(25, max(1, $daysAgo - 5)));
                if ($paymentDate->isFuture()) {
                    $paymentDate = now();
                }

                SupplierPayments::record($workspace, [
                    'partner_id' => $vendor->id,
                    'account_id' => $bankAccount->id,
                    'payment_date' => $paymentDate->toDateString(),
                ], [['bill_id' => $bill->id, 'amount' => $payAmount]]);
            }
        }
    }

    private static function createManualJournals(Workspace $workspace, array $departments): void
    {
        $cash = self::accountByCode($workspace, '1111-00');
        $bank = self::accountByCode($workspace, '1113-02');
        $accrued = self::accountByCode($workspace, '2131-00');
        $rent = self::accountByCode($workspace, '5310-01');
        $bankFee = self::accountByCode($workspace, '5360-04');
        $otherIncome = self::accountByCode($workspace, '4200-02');

        $entries = [
            [60, 'ตั้งค่าใช้จ่ายค้างจ่ายเดือน', [
                ['account_id' => $rent->id, 'debit' => 15000, 'credit' => 0, 'department_id' => $departments[0]->id],
                ['account_id' => $accrued->id, 'debit' => 0, 'credit' => 15000],
            ]],
            [45, 'รับเงินสดย่อยจากธนาคาร', [
                ['account_id' => $cash->id, 'debit' => 5000, 'credit' => 0],
                ['account_id' => $bank->id, 'debit' => 0, 'credit' => 5000],
            ]],
            [30, 'ค่าธรรมเนียมโอนเงินธนาคาร', [
                ['account_id' => $bankFee->id, 'debit' => 350, 'credit' => 0, 'department_id' => $departments[1]->id],
                ['account_id' => $bank->id, 'debit' => 0, 'credit' => 350],
            ]],
            [20, 'รายได้อื่น - ดอกเบี้ยรับ', [
                ['account_id' => $bank->id, 'debit' => 1250, 'credit' => 0],
                ['account_id' => $otherIncome->id, 'debit' => 0, 'credit' => 1250],
            ]],
            [10, 'ปรับปรุงค่าใช้จ่ายค้างจ่าย', [
                ['account_id' => $accrued->id, 'debit' => 15000, 'credit' => 0],
                ['account_id' => $bank->id, 'debit' => 0, 'credit' => 15000],
            ]],
        ];

        foreach ($entries as [$daysAgo, $memo, $lines]) {
            LedgerPosting::post($workspace, [
                'date' => now()->subDays($daysAgo)->toDateString(),
                'type' => 'manual',
                'memo' => $memo,
            ], $lines);
        }
    }

    private static function createRecurringJournals(Workspace $workspace): void
    {
        $rentAccount = self::accountByCode($workspace, '5310-01');
        $bankAccount = self::accountByCode($workspace, '1113-02');
        $utilAccount = self::accountByCode($workspace, '5310-02');

        RecurringJournals::create($workspace, [
            'name' => 'ค่าเช่าสำนักงาน — รายเดือน',
            'frequency' => 'monthly',
            'next_run_date' => now()->startOfMonth()->addMonth()->toDateString(),
        ], [
            ['account_id' => $rentAccount->id, 'debit' => 15000, 'credit' => 0],
            ['account_id' => $bankAccount->id, 'debit' => 0, 'credit' => 15000],
        ]);

        RecurringJournals::create($workspace, [
            'name' => 'ค่าน้ำค่าไฟ — รายเดือน',
            'frequency' => 'monthly',
            'next_run_date' => now()->startOfMonth()->addMonth()->toDateString(),
        ], [
            ['account_id' => $utilAccount->id, 'debit' => 4500, 'credit' => 0],
            ['account_id' => $bankAccount->id, 'debit' => 0, 'credit' => 4500],
        ]);
    }

    /** Budget for income and major expense accounts, one row per month for current year. */
    private static function createBudgets(Workspace $workspace, array $departments): void
    {
        $year = now()->year;
        $config = [
            // [code, monthly base amount, type label for variation]
            ['4100-01', 80000, 'income'],
            ['5400-01', 20000, 'expense'],
            ['5310-01', 15000, 'expense'],
            ['5310-02', 5000, 'expense'],
            ['5300-01', 35000, 'expense'],
        ];

        foreach ($config as [$code, $baseMonthly]) {
            $account = self::accountByCode($workspace, $code);
            for ($month = 1; $month <= 12; $month++) {
                // Add a little month-to-month variation so charts look realistic.
                $amount = $baseMonthly + (($month % 3) * 1500);
                Budget::create([
                    'workspace_id' => $workspace->id,
                    'account_id' => $account->id,
                    'department_id' => null,
                    'fiscal_year' => $year,
                    'fiscal_month' => $month,
                    'amount' => $amount,
                ]);
            }
            // Plus an annual umbrella entry on the HQ department, just so the
            // "annual + monthly" split is exercised by the report.
            Budget::create([
                'workspace_id' => $workspace->id,
                'account_id' => $account->id,
                'department_id' => $departments[0]->id,
                'fiscal_year' => $year,
                'fiscal_month' => null,
                'amount' => $baseMonthly * 12,
            ]);
        }
    }

    // ── Helpers ────────────────────────────────────────────────────────────

    private static function accountByCode(Workspace $workspace, string $code): Account
    {
        $account = Account::query()->forWorkspace($workspace)->where('code', $code)->first();
        if (! $account) {
            throw new \RuntimeException("Demo seeder needs account {$code} but it wasn't in the chart.");
        }
        return $account;
    }
}
