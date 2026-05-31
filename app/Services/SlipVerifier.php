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
     * Verify $slip is a real transfer of exactly $expectedAmount THB into the
     * configured receiving account.
     *
     * @return array{ok: bool, trans_ref: ?string, amount: ?int, receiver: ?string, receiver_acc: ?string, message: string, raw: array}
     */
    public static function verify(UploadedFile $slip, int $expectedAmount): array
    {
        $fail = fn (string $msg, array $raw = [], array $extra = []) => array_merge([
            'ok' => false, 'trans_ref' => null, 'amount' => null,
            'receiver' => null, 'receiver_acc' => null, 'message' => $msg, 'raw' => $raw,
        ], $extra);

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
        $receiverAcc = $data['receiver']['account']['value']
            ?? ($data['receiver']['proxy']['value'] ?? null);

        $base = [
            'trans_ref' => $transRef, 'amount' => $amount,
            'receiver' => $receiver, 'receiver_acc' => $receiverAcc, 'raw' => $data,
        ];

        // Exact-amount match: an over- or under-payment is rejected so a plan
        // can't be bought for the wrong price.
        if ($amount !== $expectedAmount) {
            return array_merge($base, ['ok' => false, 'message' => 'amount_mismatch']);
        }

        // The transfer must land in OUR receiving account. Compare against the
        // configured PromptPay / bank number using masked-suffix matching,
        // since slips usually expose only the last digits (e.g. "xxx-x-x1234").
        if (! self::receiverMatches($receiverAcc)) {
            return array_merge($base, ['ok' => false, 'message' => 'receiver_mismatch']);
        }

        return array_merge($base, ['ok' => true, 'message' => 'ok']);
    }

    /** Receiving account this branch should pay into (bank no. or PromptPay). */
    public static function expectedReceiver(): ?string
    {
        return setting('queue_billing.method') === 'bank_account'
            ? setting('queue_billing.account_no')
            : setting('queue_billing.promptpay');
    }

    /**
     * Loosely match the slip's receiver account against our configured number.
     * Both are reduced to digits; we accept when one is a suffix of the other
     * (banks mask all but the last 4-5 digits on slips). Empty config = skip
     * the check (don't block on a misconfiguration).
     */
    public static function receiverMatches(?string $slipAcc): bool
    {
        $expected = preg_replace('/\D/', '', (string) self::expectedReceiver());
        $got = preg_replace('/\D/', '', (string) $slipAcc);

        if ($expected === '' ) {
            return true; // not configured to check
        }
        if ($got === '') {
            return false; // we expect a match but the slip exposed nothing
        }

        $len = min(strlen($expected), strlen($got), 4);
        if ($len < 4) {
            return false;
        }

        return substr($expected, -$len) === substr($got, -$len);
    }
}
