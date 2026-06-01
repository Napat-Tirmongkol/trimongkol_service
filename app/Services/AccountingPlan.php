<?php

namespace App\Services;

use App\Models\Accounting\Invoice;
use App\Models\Workspace;

/**
 * Accounting product plan limits — see config/accounting-plans.php. Each gate
 * returns null when allowed, otherwise a translated reason string for a flash
 * toast. Independent of the platform (Billing) and queue (QueuePlan) tiers.
 */
class AccountingPlan
{
    public const KEYS = ['free', 'starter', 'pro'];

    /** Plans a customer can self-purchase (free is default). */
    public const PURCHASABLE = ['starter', 'pro'];

    /**
     * Effective plan key, accounting for expiry: a paid plan past its
     * accounting_plan_until silently drops back to free (no scheduler needed).
     */
    public static function key(?Workspace $workspace): string
    {
        $key = $workspace?->accounting_plan ?: 'free';
        if (! config("accounting-plans.{$key}") || $key === 'free') {
            return 'free';
        }

        $until = $workspace?->accounting_plan_until;
        if ($until !== null && $until->isPast()) {
            return 'free';
        }

        return $key;
    }

    public static function price(string $key): ?int
    {
        $p = config("accounting-plans.{$key}.price");

        return $p === null ? null : (int) $p;
    }

    /** Stored plan even if expired — for showing "Starter (expired)" in admin. */
    public static function storedKey(?Workspace $workspace): string
    {
        $key = $workspace?->accounting_plan ?: 'free';

        return config("accounting-plans.{$key}") ? $key : 'free';
    }

    public static function isExpired(?Workspace $workspace): bool
    {
        $until = $workspace?->accounting_plan_until;

        return self::storedKey($workspace) !== 'free' && $until !== null && $until->isPast();
    }

    public static function expiresAt(?Workspace $workspace): ?\Illuminate\Support\Carbon
    {
        return $workspace?->accounting_plan_until;
    }

    /** @return array<string, mixed> */
    public static function config(?Workspace $workspace): array
    {
        return config('accounting-plans.'.self::key($workspace)) ?? config('accounting-plans.free');
    }

    public static function name(?Workspace $workspace): string
    {
        return self::config($workspace)['name'] ?? 'Free';
    }

    public static function limit(?Workspace $workspace, string $name): ?int
    {
        $value = self::config($workspace)['limits'][$name] ?? null;

        return $value === null ? null : (int) $value;
    }

    public static function can(?Workspace $workspace, string $flag): bool
    {
        return (bool) (self::config($workspace)['flags'][$flag] ?? false);
    }

    /** Block creating a new sales document once this month's allowance is used up. */
    public static function reasonCannotCreateInvoice(Workspace $workspace): ?string
    {
        $limit = self::limit($workspace, 'max_invoices_per_month');
        if ($limit === null) {
            return null;
        }

        $used = Invoice::forWorkspace($workspace)
            ->whereYear('created_at', now()->year)
            ->whereMonth('created_at', now()->month)
            ->count();

        if ($used >= $limit) {
            return __('app.accounting.plan_limit_invoices', ['limit' => $limit]);
        }

        return null;
    }
}
