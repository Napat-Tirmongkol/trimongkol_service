<?php

namespace App\Services\Accounting;

use App\Exceptions\Accounting\LedgerException;

/**
 * Fixed-point money helpers. Every amount is handled as an integer number of
 * minor units (satang) so additions, percentages, and line totals are exact —
 * no float drift, no bcmath. See CLAUDE.md: never use float for money.
 */
class Money
{
    public const SCALE = 2;

    /** Parse a money value into integer minor units at $scale decimals (extra dp truncated). */
    public static function toMinor($value, int $scale = self::SCALE): int
    {
        $s = trim((string) $value);
        $negative = str_starts_with($s, '-');
        $s = ltrim($s, '+-');

        if ($s === '' || ! preg_match('/^\d*(\.\d*)?$/', $s)) {
            throw new LedgerException("Invalid money value: {$value}");
        }

        [$whole, $frac] = array_pad(explode('.', $s, 2), 2, '');
        $frac = substr(str_pad($frac, $scale, '0'), 0, $scale);

        return ($negative ? -1 : 1) * ((int) $whole * (10 ** $scale) + (int) $frac);
    }

    /** Render integer minor units back to a fixed-scale decimal string. */
    public static function fromMinor(int $minor, int $scale = self::SCALE): string
    {
        $sign = $minor < 0 ? '-' : '';
        $minor = abs($minor);
        $unit = 10 ** $scale;

        $whole = intdiv($minor, $unit);
        if ($scale === 0) {
            return $sign.$whole;
        }

        return $sign.$whole.'.'.str_pad((string) ($minor % $unit), $scale, '0', STR_PAD_LEFT);
    }

    /** $ratePercent of $amountMinor, rounded half-up to the minor unit. Pure integer math. */
    public static function percentage(int $amountMinor, $ratePercent): int
    {
        $rateScaled = (int) round(((float) $ratePercent) * 1000);   // 7.000 -> 7000, keeps 3dp of rate
        $numerator = $amountMinor * $rateScaled;                    // amount * rate * 1000
        $divisor = 100 * 1000;                                      // ÷100 (percent) and unscale the rate
        $half = ($numerator >= 0 ? 1 : -1) * intdiv($divisor, 2);

        return intdiv($numerator + $half, $divisor);
    }

    /** quantity (4dp) × unit price (4dp) → minor units (2dp), rounded half-up. */
    public static function lineAmount($quantity, $unitPrice): int
    {
        $product = self::toMinor($quantity, 4) * self::toMinor($unitPrice, 4); // scaled 10^8
        $divisor = 1000000;                                                    // 10^8 -> 10^2
        $half = ($product >= 0 ? 1 : -1) * intdiv($divisor, 2);

        return intdiv($product + $half, $divisor);
    }
}
