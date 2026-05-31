<?php

namespace App\Services\Accounting;

use App\Models\Accounting\Account;
use App\Models\Accounting\TaxCode;
use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

/**
 * Seeds the standard Thai tax codes for a workspace, wiring each to the
 * role-tagged ledger account from ChartOfAccounts (VAT7 -> ภาษีขาย, etc.).
 */
class TaxCodes
{
    private const DEFAULTS = [
        ['code' => 'VAT7', 'name' => 'ภาษีขาย 7%', 'kind' => 'vat_output', 'rate' => '7.000', 'role' => 'vat_output'],
        ['code' => 'VAT7P', 'name' => 'ภาษีซื้อ 7%', 'kind' => 'vat_input', 'rate' => '7.000', 'role' => 'vat_input'],
        ['code' => 'WHT3', 'name' => 'หัก ณ ที่จ่าย 3% (ค่าบริการ/รับเหมา)', 'kind' => 'wht', 'rate' => '3.000', 'role' => 'wht_payable_pnd53'],
        ['code' => 'WHT5', 'name' => 'หัก ณ ที่จ่าย 5% (ค่าเช่า)', 'kind' => 'wht', 'rate' => '5.000', 'role' => 'wht_payable_pnd53'],
    ];

    /**
     * Seed the default codes into $workspace, skipping codes that already exist
     * and any whose backing account role is not present. Idempotent + atomic.
     *
     * @return int  number of tax codes created
     */
    public static function seedDefault(Workspace $workspace): int
    {
        return DB::transaction(function () use ($workspace) {
            $existing = TaxCode::query()->forWorkspace($workspace)->pluck('code')->all();
            $accountByRole = Account::query()
                ->forWorkspace($workspace)
                ->whereNotNull('system_role')
                ->pluck('id', 'system_role');

            $created = 0;
            foreach (self::DEFAULTS as $row) {
                if (in_array($row['code'], $existing, true) || ! $accountByRole->has($row['role'])) {
                    continue;
                }

                TaxCode::create([
                    'workspace_id' => $workspace->id,
                    'code' => $row['code'],
                    'name' => $row['name'],
                    'kind' => $row['kind'],
                    'rate' => $row['rate'],
                    'account_id' => $accountByRole->get($row['role']),
                ]);
                $created++;
            }

            return $created;
        });
    }
}
