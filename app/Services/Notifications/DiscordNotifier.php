<?php

namespace App\Services\Notifications;

use Illuminate\Support\Facades\Http;

/**
 * Post notifications to a Discord channel via an incoming webhook URL.
 * Platform-level channel — configured once in /admin → Notifications and
 * reusable by any product.
 *
 * A single webhook URL is all that's needed (no token, no recipient ID).
 * Every send is best-effort: any failure is logged and swallowed.
 */
class DiscordNotifier
{
    public static function enabled(): bool
    {
        return filled(self::webhook());
    }

    public static function webhook(): ?string
    {
        // Not encrypted: a Discord webhook URL is a capability, not a secret
        // credential, and storing it as plain text keeps the admin form able
        // to show/verify it. Still server-side only.
        return setting('discord.webhook') ?: config('services.discord.webhook');
    }

    /**
     * Post a plain-text message. Returns [ok, message] for the admin test
     * button. Never throws.
     */
    public static function send(string $text): array
    {
        $url = self::webhook();
        if (! filled($url)) {
            return [false, 'not_configured'];
        }

        if (! preg_match('#^https://(\w+\.)?discord(app)?\.com/api/webhooks/#', $url)) {
            return [false, 'bad_url'];
        }

        try {
            $res = Http::timeout(10)->post($url, [
                'content' => mb_substr($text, 0, 1900),
            ]);
        } catch (\Throwable $e) {
            report($e);
            return [false, 'unreachable'];
        }

        if ($res->successful()) {
            return [true, 'ok'];
        }

        $reason = match ($res->status()) {
            401, 403, 404 => 'bad_url',
            429 => 'rate_limited',
            default => 'http_'.$res->status(),
        };

        return [false, $reason];
    }

    /** Fire-and-forget: send if configured, ignore the result. */
    public static function notify(string $text): void
    {
        if (self::enabled()) {
            self::send($text);
        }
    }

    /**
     * Post a rich embed card. Never throws.
     * Pass $webhookUrl to use a specific channel instead of the global one.
     */
    public static function sendEmbed(array $embed, ?string $webhookUrl = null): array
    {
        $url = $webhookUrl ?? self::webhook();
        if (! filled($url)) {
            return [false, 'not_configured'];
        }

        try {
            $res = Http::timeout(10)->post($url, ['embeds' => [$embed]]);
        } catch (\Throwable $e) {
            report($e);
            return [false, 'unreachable'];
        }

        return $res->successful() ? [true, 'ok'] : [false, 'http_'.$res->status()];
    }

    /**
     * Fire-and-forget embed.
     * Pass $webhookUrl to override the global webhook (e.g. per-product channel).
     */
    public static function notifyEmbed(array $embed, ?string $webhookUrl = null): void
    {
        $url = $webhookUrl ?? self::webhook();
        if (filled($url)) {
            self::sendEmbed($embed, $url);
        }
    }
}
