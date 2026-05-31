<?php

namespace App\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

/**
 * Push notifications to the shop owner via the LINE Messaging API.
 *
 * Configured in /admin → Queue (channel access token encrypted in
 * site_settings; target user/group ID). Every send is best-effort — any
 * failure is logged and swallowed so it never breaks the payment flow.
 */
class LineNotifier
{
    private const PUSH_ENDPOINT = 'https://api.line.me/v2/bot/message/push';

    public static function enabled(): bool
    {
        return filled(self::token()) && filled(self::target());
    }

    public static function token(): ?string
    {
        $stored = setting('line.channel_token');
        if (filled($stored)) {
            try {
                return Crypt::decryptString($stored);
            } catch (\Throwable $e) {
                return $stored;
            }
        }
        return config('services.line.channel_token');
    }

    /** The user / group / room ID that receives the push. */
    public static function target(): ?string
    {
        return setting('line.target_id') ?: config('services.line.target_id');
    }

    /**
     * Push a plain-text message. Returns [ok, message] so the admin test
     * button can report the outcome. Never throws.
     */
    public static function push(string $text): array
    {
        if (! self::enabled()) {
            return [false, 'not_configured'];
        }

        try {
            $res = Http::timeout(10)
                ->withToken((string) self::token())
                ->post(self::PUSH_ENDPOINT, [
                    'to' => self::target(),
                    'messages' => [
                        ['type' => 'text', 'text' => mb_substr($text, 0, 4900)],
                    ],
                ]);
        } catch (\Throwable $e) {
            report($e);
            return [false, 'unreachable'];
        }

        if ($res->successful()) {
            return [true, 'ok'];
        }

        // 401 = bad token, 403 = wrong target / not friends, 400 = bad ID.
        $reason = match ($res->status()) {
            401 => 'bad_token',
            403 => 'bad_target',
            400 => 'bad_request',
            429 => 'rate_limited',
            default => (string) ($res->json('message') ?? ('http_'.$res->status())),
        };

        return [false, $reason];
    }

    /** Fire-and-forget: send if configured, ignore the result. */
    public static function notify(string $text): void
    {
        if (self::enabled()) {
            self::push($text);
        }
    }
}
