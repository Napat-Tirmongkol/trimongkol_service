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

    public static function key(?Workspace $workspace): string
    {
        $key = $workspace?->queue_plan ?: 'free';
        return config("queue-plans.{$key}") ? $key : 'free';
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
