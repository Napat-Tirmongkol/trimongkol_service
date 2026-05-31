<?php

namespace App\Services;

use App\Models\Queue;
use App\Models\QueueTicket;
use App\Models\Workspace;

/**
 * Queue product plan limits — see config/queue-plans.php. Each gate returns
 * null when allowed, otherwise a translated reason string for a flash toast.
 *
 * Deliberately independent of App\Services\Billing (the Scanner free-launch
 * mode): the queue tiers are enforced as soon as they're configured.
 */
class QueuePlan
{
    public const KEYS = ['free', 'starter', 'pro', 'enterprise'];

    /** Plans a customer can self-purchase via slip (free is default, enterprise is contact-sales). */
    public const PURCHASABLE = ['starter', 'pro'];

    /**
     * Effective plan key, accounting for expiry: a paid plan past its
     * queue_plan_until silently drops back to free (no scheduler needed).
     */
    public static function key(?Workspace $workspace): string
    {
        $key = $workspace?->queue_plan ?: 'free';
        if (! config("queue-plans.{$key}") || $key === 'free') {
            return 'free';
        }

        $until = $workspace?->queue_plan_until;
        if ($until !== null && $until->isPast()) {
            return 'free';
        }

        return $key;
    }

    public static function price(string $key): ?int
    {
        $p = config("queue-plans.{$key}.price");
        return $p === null ? null : (int) $p;
    }

    /** Stored plan even if expired — for showing "Starter (expired)" in admin. */
    public static function storedKey(?Workspace $workspace): string
    {
        $key = $workspace?->queue_plan ?: 'free';
        return config("queue-plans.{$key}") ? $key : 'free';
    }

    public static function isExpired(?Workspace $workspace): bool
    {
        $until = $workspace?->queue_plan_until;
        return self::storedKey($workspace) !== 'free' && $until !== null && $until->isPast();
    }

    public static function expiresAt(?Workspace $workspace): ?\Illuminate\Support\Carbon
    {
        return $workspace?->queue_plan_until;
    }

    /** @return array<string, mixed> */
    public static function config(?Workspace $workspace): array
    {
        return config('queue-plans.'.self::key($workspace)) ?? config('queue-plans.free');
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

    /** Free tiers (no branding flag) show the system watermark. */
    public static function showsWatermark(?Workspace $workspace): bool
    {
        return ! self::can($workspace, 'branding');
    }

    public static function reasonCannotCreateQueue(Workspace $workspace): ?string
    {
        $limit = self::limit($workspace, 'max_queues');
        if ($limit === null) {
            return null;
        }
        if (Queue::where('workspace_id', $workspace->id)->count() >= $limit) {
            return __('app.queue.plan_limit_queues', ['limit' => $limit]);
        }
        return null;
    }

    public static function reasonCannotAddCounter(Queue $queue): ?string
    {
        $limit = self::limit($queue->workspace, 'max_counters');
        if ($limit === null) {
            return null;
        }
        if ($queue->counters()->count() >= $limit) {
            return __('app.queue.plan_limit_counters', ['limit' => $limit]);
        }
        return null;
    }

    public static function reasonCannotIssueTicket(Queue $queue): ?string
    {
        $limit = self::limit($queue->workspace, 'max_tickets_per_day');
        if ($limit === null) {
            return null;
        }
        $today = QueueTicket::where('queue_id', $queue->id)
            ->whereDate('created_at', now()->toDateString())
            ->count();
        if ($today >= $limit) {
            return __('app.queue.plan_limit_tickets', ['limit' => $limit]);
        }
        return null;
    }
}
