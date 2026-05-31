<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

/**
 * Verify a PromptPay transfer slip via SlipOK (https://slipok.com). The slip
 * image is sent to SlipOK, which reads the QR and checks it against the real
 * bank transaction tied to our SlipOK branch — so a slip can only validate if
 * it was actually sent to our receiving account.
 *
 * Configured in /admin → Queue (encrypted in site_settings). When not
 * configured, self::enabled() is false and the caller falls back to manual
 * admin approval.
 */
class SlipVerifier
{
    public static function enabled(): bool
    {
        return filled(self::key()) && filled(self::branchId());
    }

    public static function key(): ?string
    {
        $stored = setting('queue_billing.slipok_key');
        if (filled($stored)) {
            try {
                return Crypt::decryptString($stored);
            } catch (\Throwable $e) {
                return $stored;
            }
        }
        return config('services.slipok.key');
    }

    public static function branchId(): ?string
    {
        return setting('queue_billing.slipok_branch') ?: config('services.slipok.branch');
    }

    /**
     * Verify $slip is a real transfer of at least $expectedAmount THB.
     *
     * @return array{ok: bool, trans_ref: ?string, amount: ?int, receiver: ?string, message: string, raw: array}
     */
    public static function verify(UploadedFile $slip, int $expectedAmount): array
    {
        $fail = fn (string $msg, array $raw = []) => [
            'ok' => false, 'trans_ref' => null, 'amount' => null,
            'receiver' => null, 'message' => $msg, 'raw' => $raw,
        ];

        if (! self::enabled()) {
            return $fail('slipok_not_configured');
        }

        try {
            $res = Http::timeout(15)
                ->withHeaders(['x-authorization' => (string) self::key()])
                ->attach('files', file_get_contents($slip->getRealPath()), $slip->getClientOriginalName() ?: 'slip.jpg')
                ->post('https://api.slipok.com/api/line/apikey/'.self::branchId(), [
                    'amount' => $expectedAmount,
                    'log' => 'true',
                ]);
        } catch (\Throwable $e) {
            report($e);
            return $fail('slipok_unreachable');
        }

        $json = $res->json() ?? [];

        if (! $res->successful() || ! ($json['success'] ?? false)) {
            // 1012 = SlipOK's own "duplicate slip" guard.
            $code = $json['code'] ?? null;
            $msg = (int) $code === 1012 ? 'duplicate' : ($json['message'] ?? 'verify_failed');
            return $fail($msg, $json);
        }

        $data = $json['data'] ?? [];
        $amount = (int) round((float) ($data['amount'] ?? 0));
        $transRef = $data['transRef'] ?? null;
        $receiver = $data['receiver']['displayName']
            ?? ($data['receiver']['account']['value'] ?? null);

        if ($amount < $expectedAmount) {
            return [
                'ok' => false, 'trans_ref' => $transRef, 'amount' => $amount,
                'receiver' => $receiver, 'message' => 'amount_mismatch', 'raw' => $data,
            ];
        }

        return [
            'ok' => true, 'trans_ref' => $transRef, 'amount' => $amount,
            'receiver' => $receiver, 'message' => 'ok', 'raw' => $data,
        ];
    }
}
