<?php

namespace App\Services\Notifications;

/**
 * Platform notification hub. Any product can push an owner/ops message with
 * a single call — it fans out to every configured channel (LINE, Discord).
 * Channels are configured once in /admin → Notifications.
 *
 *   Notifier::send("A new order arrived");
 *
 * Best-effort by design: a channel that's off or failing is simply skipped.
 */
class Notifier
{
    /** Fan a plain-text message out to every enabled channel. */
    public static function send(string $text): void
    {
        LineNotifier::notify($text);
        DiscordNotifier::notify($text);
    }

    /** True when at least one channel is configured. */
    public static function enabled(): bool
    {
        return LineNotifier::enabled() || DiscordNotifier::enabled();
    }
}
