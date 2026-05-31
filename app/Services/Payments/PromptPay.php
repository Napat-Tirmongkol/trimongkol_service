<?php

namespace App\Services\Payments;

/**
 * Build an EMVCo PromptPay QR payload string (the text encoded into the QR
 * the customer scans). Render it to an actual QR client-side with the
 * existing `qrcode` library — no server-side image generation needed.
 *
 * Target may be a mobile number (e.g. 0812345678) or a 13-digit national /
 * tax ID. Amount is optional; when given the banking app pre-fills it.
 */
class PromptPay
{
    public static function payload(string $target, ?float $amount = null): string
    {
        $target = preg_replace('/[^0-9]/', '', $target) ?? '';

        // Phones become 0066 + number without the leading 0 (13 digits, tag 01);
        // 13-digit IDs use tag 02.
        if (strlen($target) >= 13) {
            $proxyTag = '02';
            $proxyValue = substr($target, 0, 13);
        } else {
            $proxyTag = '01';
            $proxyValue = '0066' . substr(ltrim($target, '0'), -9);
        }

        $merchant = self::field('00', 'A000000677010111')
            . self::field($proxyTag, $proxyValue);

        $payload = self::field('00', '01')                 // payload format indicator
            . self::field('01', '11')                       // static QR (reusable)
            . self::field('29', $merchant)                  // merchant account info (PromptPay)
            . self::field('53', '764')                      // currency THB
            . ($amount !== null ? self::field('54', number_format($amount, 2, '.', '')) : '')
            . self::field('58', 'TH')                        // country
            . '6304';                                       // CRC tag + length, value appended below

        return $payload . self::crc16($payload);
    }

    private static function field(string $id, string $value): string
    {
        return $id . str_pad((string) strlen($value), 2, '0', STR_PAD_LEFT) . $value;
    }

    private static function crc16(string $data): string
    {
        $crc = 0xFFFF;
        for ($i = 0, $len = strlen($data); $i < $len; $i++) {
            $crc ^= ord($data[$i]) << 8;
            for ($j = 0; $j < 8; $j++) {
                $crc = ($crc & 0x8000) ? (($crc << 1) ^ 0x1021) : ($crc << 1);
                $crc &= 0xFFFF;
            }
        }
        return strtoupper(str_pad(dechex($crc), 4, '0', STR_PAD_LEFT));
    }
}
