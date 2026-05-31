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

        // log=true sends the amount + records the slip, so SlipOK authoritatively
        // enforces amount (1013), receiver-account (1014) and duplicate (1012)
        // against the bank account linked to this branch in its dashboard.
        [$ok, $data, $msg] = self::call($slip, $expectedAmount, true);
        if (! $ok) {
            return $fail($msg, $data);
        }

        $parsed = self::parse($data);
        $base = [
            'trans_ref' => $parsed['trans_ref'], 'amount' => $parsed['amount'],
            'receiver' => $parsed['receiver'], 'receiver_acc' => $parsed['receiver_acc'], 'raw' => $data,
        ];

        // Exact-amount match: an over- or under-payment is rejected so a plan
        // can't be bought for the wrong price.
        if ($parsed['amount'] !== $expectedAmount) {
            return array_merge($base, ['ok' => false, 'message' => 'amount_mismatch']);
        }

        // The transfer must land in OUR receiving account. Compare against the
        // configured PromptPay / bank number using masked-suffix matching,
        // since slips usually expose only the last digits (e.g. "xxx-x-x1234").
        if (! self::receiverMatches($parsed['receiver_acc'])) {
            return array_merge($base, ['ok' => false, 'message' => 'receiver_mismatch']);
        }

        return array_merge($base, ['ok' => true, 'message' => 'ok']);
    }

    /**
     * Dry-run a slip for the admin "test transfer" tool: reads the slip via
     * SlipOK without consuming it (log=false, so it can be re-tested) and
     * reports what was read plus whether the receiver matches. Does NOT enforce
     * an amount, create a payment, or activate anything.
     *
     * @return array{ok: bool, message: string, amount: ?int, receiver: ?string, receiver_acc: ?string, receiver_match: bool, trans_ref: ?string}
     */
    public static function inspect(UploadedFile $slip): array
    {
        $out = [
            'ok' => false, 'message' => 'verify_failed', 'amount' => null,
            'receiver' => null, 'receiver_acc' => null, 'receiver_match' => false, 'trans_ref' => null,
        ];

        if (! self::enabled()) {
            $out['message'] = 'slipok_not_configured';
            return $out;
        }

        [$ok, $data, $msg] = self::call($slip, null, false);
        if (! $ok) {
            $out['message'] = $msg;
            return $out;
        }

        $parsed = self::parse($data);

        return [
            'ok' => true,
            'message' => 'ok',
            'amount' => $parsed['amount'],
            'receiver' => $parsed['receiver'],
            'receiver_acc' => $parsed['receiver_acc'],
            'receiver_match' => self::receiverMatches($parsed['receiver_acc']),
            'trans_ref' => $parsed['trans_ref'],
        ];
    }

    /**
     * Low-level SlipOK call. Returns [ok, data|errorJson, message].
     * $log=false tells SlipOK not to store the slip (so a test can repeat).
     */
    private static function call(UploadedFile $slip, ?int $amount, bool $log): array
    {
        $payload = ['log' => $log ? 'true' : 'false'];
        if ($amount !== null) {
            $payload['amount'] = $amount;
        }

        try {
            $res = Http::timeout(15)
                ->withHeaders(['x-authorization' => (string) self::key()])
                ->attach('files', file_get_contents($slip->getRealPath()), $slip->getClientOriginalName() ?: 'slip.jpg')
                ->post('https://api.slipok.com/api/line/apikey/'.self::branchId(), $payload);
        } catch (\Throwable $e) {
            report($e);
            return [false, [], 'slipok_unreachable'];
        }

        $json = $res->json() ?? [];

        if (! $res->successful() || ! ($json['success'] ?? false)) {
            // Map SlipOK's documented error codes to our own short messages so
            // the caller can react (and translate) without parsing Thai text.
            // The error body may also carry slip `data` (e.g. 1012/1013/1014).
            $code = (int) ($json['code'] ?? 0);

            // No app-level code → a transport-level error. A 404 here means the
            // /apikey/<BRANCH_ID> path didn't resolve (wrong/empty Branch ID);
            // 401/403 means a bad API key. Surface those as config problems
            // instead of a raw "Not Found".
            if ($code === 0) {
                $msg = match ($res->status()) {
                    404 => 'slipok_branch',
                    401, 403 => 'slipok_auth',
                    default => $json['message'] ?? 'verify_failed',
                };
                return [false, $json['data'] ?? $json, $msg];
            }

            $msg = match ($code) {
                1012 => 'duplicate',           // slip already used
                1013 => 'amount_mismatch',     // amount != slip
                1014 => 'receiver_mismatch',   // wrong destination account
                1010 => 'bank_delay',          // BBL/SCB — retry shortly
                1001 => 'slipok_branch',       // branch not found
                1003, 1004, 1015 => 'slipok_quota',   // package expired / over quota
                1002 => 'slipok_auth',         // bad API key
                1005, 1006, 1007, 1008, 1011 => 'bad_slip', // not a valid payment slip
                default => $json['message'] ?? 'verify_failed',
            };
            return [false, $json['data'] ?? $json, $msg];
        }

        return [true, $json['data'] ?? [], 'ok'];
    }

    /** Pull the fields we care about out of a SlipOK `data` payload. */
    private static function parse(array $data): array
    {
        return [
            'amount' => (int) round((float) ($data['amount'] ?? 0)),
            'trans_ref' => $data['transRef'] ?? null,
            'receiver' => $data['receiver']['displayName']
                ?? ($data['receiver']['account']['value'] ?? null),
            'receiver_acc' => $data['receiver']['account']['value']
                ?? ($data['receiver']['proxy']['value'] ?? null),
        ];
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
