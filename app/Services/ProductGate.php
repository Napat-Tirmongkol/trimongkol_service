<?php

namespace App\Services;

/**
 * Platform-wide on/off switch for optional products (queue, accounting, …),
 * toggled from /admin/site. A product is ON unless it has been explicitly
 * turned off ("0") — so existing installs keep working until an admin opts out.
 */
class ProductGate
{
    public static function enabled(string $key): bool
    {
        return setting("products.{$key}.enabled", '1') !== '0';
    }
}
