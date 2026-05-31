<?php

namespace App\Services;

/**
 * RFC 6238 TOTP implementation — pure PHP, no composer deps.
 *
 * Generates and validates 6-digit codes compatible with Google
 * Authenticator, Microsoft Authenticator, 1Password, etc. Secrets are
 * base32-encoded per the otpauth:// URI scheme.
 */
class Totp
{
    private const PERIOD = 30;
    private const DIGITS = 6;
    private const ALGORITHM = 'sha1';
    private const BASE32_ALPHABET = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Generate a new random base32-encoded secret (160 bits = 32 chars).
     */
    public static function generateSecret(): string
    {
        $bytes = random_bytes(20);
        return self::base32Encode($bytes);
    }

    /**
     * Verify a user-submitted code against the secret. Allows ±$window steps
     * of clock drift (default 1 step = ±30 seconds).
     */
    public static function verify(string $secret, string $code, int $window = 1): bool
    {
        $code = preg_replace('/\s+/', '', $code);
        if (! preg_match('/^\d{' . self::DIGITS . '}$/', $code)) {
            return false;
        }

        $counter = (int) floor(time() / self::PERIOD);

        for ($i = -$window; $i <= $window; $i++) {
            if (hash_equals(self::codeForCounter($secret, $counter + $i), $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * The current code right now — useful for debugging/seeding tests.
     */
    public static function currentCode(string $secret): string
    {
        return self::codeForCounter($secret, (int) floor(time() / self::PERIOD));
    }

    /**
     * otpauth:// URI to feed into the user's authenticator app
     * (rendered as a QR code in the browser).
     */
    public static function provisioningUri(string $secret, string $accountName, string $issuer): string
    {
        $label = rawurlencode($issuer . ':' . $accountName);
        $params = http_build_query([
            'secret' => $secret,
            'issuer' => $issuer,
            'algorithm' => strtoupper(self::ALGORITHM),
            'digits' => self::DIGITS,
            'period' => self::PERIOD,
        ]);

        return "otpauth://totp/{$label}?{$params}";
    }

    private static function codeForCounter(string $secret, int $counter): string
    {
        $key = self::base32Decode($secret);
        $binCounter = pack('N*', 0, $counter);
        $hash = hash_hmac(self::ALGORITHM, $binCounter, $key, true);

        // Dynamic truncation per RFC 4226.
        $offset = ord($hash[strlen($hash) - 1]) & 0x0F;
        $code = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        ) % (10 ** self::DIGITS);

        return str_pad((string) $code, self::DIGITS, '0', STR_PAD_LEFT);
    }

    private static function base32Encode(string $bytes): string
    {
        $result = '';
        $buffer = 0;
        $bitsLeft = 0;

        for ($i = 0, $len = strlen($bytes); $i < $len; $i++) {
            $buffer = ($buffer << 8) | ord($bytes[$i]);
            $bitsLeft += 8;
            while ($bitsLeft >= 5) {
                $result .= self::BASE32_ALPHABET[($buffer >> ($bitsLeft - 5)) & 0x1F];
                $bitsLeft -= 5;
            }
        }
        if ($bitsLeft > 0) {
            $result .= self::BASE32_ALPHABET[($buffer << (5 - $bitsLeft)) & 0x1F];
        }

        return $result;
    }

    private static function base32Decode(string $encoded): string
    {
        $encoded = strtoupper(preg_replace('/[^A-Z2-7]/', '', $encoded));
        $result = '';
        $buffer = 0;
        $bitsLeft = 0;

        for ($i = 0, $len = strlen($encoded); $i < $len; $i++) {
            $idx = strpos(self::BASE32_ALPHABET, $encoded[$i]);
            if ($idx === false) {
                continue;
            }
            $buffer = ($buffer << 5) | $idx;
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $result .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $result;
    }

    /**
     * Generate a fresh set of single-use recovery codes (8 codes,
     * format: xxxxx-xxxxx).
     */
    public static function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = self::randomToken(5) . '-' . self::randomToken(5);
        }
        return $codes;
    }

    private static function randomToken(int $length): string
    {
        $alphabet = 'abcdefghijkmnpqrstuvwxyz23456789';
        $token = '';
        $max = strlen($alphabet) - 1;
        for ($i = 0; $i < $length; $i++) {
            $token .= $alphabet[random_int(0, $max)];
        }
        return $token;
    }
}
