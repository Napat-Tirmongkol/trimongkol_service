<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

/**
 * Post notifications to a Discord channel via an incoming webhook URL.
 *
 * Simpler than the LINE channel — a single webhook URL (created in a Discord
 * channel's Integrations settings) is all that's needed; no token, no
 * recipient ID. Configured in /admin → Queue. Every send is best-effort: any
 * failure is logged and swallowed so it never breaks the payment flow.
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
        // credential, and matching site_settings as plain text keeps the admin
        // form able to show/verify it. Still server-side only.
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

        // A Discord webhook URL must look like .../api/webhooks/<id>/<token>.
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

        // Discord returns 204 No Content on success.
        if ($res->successful()) {
            return [true, 'ok'];
        }

        $reason = match ($res->status()) {
            401, 403, 404 => 'bad_url',   // deleted/invalid webhook
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
}
