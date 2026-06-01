<?php

namespace App\Services;

/**
 * Platform-wide on/off switch for optional products (queue, accounting, …),
 * toggled from /admin/site. A product is ON unless explicitly turned off
 * ("0"); platform admins can still reach a switched-off product so they can
 * preview/test it before launching it to regular users.
 */
class ProductGate
{
    /** Raw switch state — is the product turned on for everyone? */
    public static function isOn(string $key): bool
    {
        return setting("products.{$key}.enabled", '1') !== '0';
    }

    /**
     * Can the current request use this product? True when it's on for everyone,
     * or when the signed-in user is a platform admin (preview access).
     */
    public static function enabled(string $key): bool
    {
        return self::isOn($key) || (bool) (auth()->user()?->is_admin ?? false);
    }
}
