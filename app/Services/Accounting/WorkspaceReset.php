<?php

namespace App\Services\Accounting;

use App\Models\Workspace;
use Illuminate\Support\Facades\DB;

/**
 * Wipes every accounting_* row that belongs to a single workspace so the user
 * can re-onboard from scratch. The workspace itself stays — only its books.
 *
 * Tables are deleted in FK-safe order (children before parents). Use it inside
 * a transaction so a mid-flight failure rolls everything back.
 */
class WorkspaceReset
{
    /**
     * Tables that have a direct workspace_id column, listed children-first so
     * the deletes never trip an FK constraint.
     */
    private const TABLES = [
        // Documents & their line items / allocations
        'accounting_payment_allocations',
        'accounting_payments',
        'accounting_invoice_lines',
        'accounting_invoices',
        'accounting_bill_lines',
        'accounting_bills',
        'accounting_wht_certificates',
        'accounting_bank_statements',
        // Recurring journals
        'accounting_recurring_journal_lines',
        'accounting_recurring_journals',
        // Fixed assets, inventory, payroll
        'accounting_fixed_assets',
        'accounting_stock_movements',
        'accounting_products',
        'accounting_payroll_items',
        'accounting_payroll_runs',
        'accounting_employees',
        // Cross-cutting
        'accounting_attachments',
        'accounting_approvals',
        'accounting_budgets',
        'accounting_activity_log',
        // Ledger
        'accounting_journal_lines',
        'accounting_journals',
        // Chart, periods, taxonomies
        'accounting_accounts',
        'accounting_periods',
        'accounting_tax_codes',
        'accounting_partners',
        'accounting_departments',
        // Accounting-side logins (separate guard)
        'accounting_users',
    ];

    /**
     * @return array<string, int> table => rows-deleted
     */
    public static function reset(Workspace $workspace): array
    {
        return DB::transaction(function () use ($workspace) {
            $deleted = [];
            foreach (self::TABLES as $table) {
                $deleted[$table] = DB::table($table)
                    ->where('workspace_id', $workspace->id)
                    ->delete();
            }

            // Clear onboarding state on the workspace itself so the wizard
            // shows up again on next visit.
            $workspace->forceFill([
                'company_name' => null,
                'tax_id' => null,
                'branch' => null,
                'phone' => null,
                'company_address' => null,
            ])->save();

            return $deleted;
        });
    }
}
