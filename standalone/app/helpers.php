<?php

use App\Models\Setting;

if (! function_exists('setting')) {
    function setting(string $key, ?string $default = null): ?string
    {
        return Setting::get($key, $default);
    }
}

if (! function_exists('t')) {
    /**
     * Read marketing-site content. For string-typed keys it checks for a DB
     * override (key + current locale suffix) and falls back to the lang file
     * `site.{key}` entry. For array-typed keys (e.g. services.items) it
     * always returns the lang file value — those are edited via a separate
     * admin screen rather than the free-text form.
     */
    function t(string $key): string|array
    {
        $locale = app()->getLocale();
        $override = Setting::get("{$key}.{$locale}");

        if ($override !== null && $override !== '') {
            return $override;
        }

        return trans("site.{$key}");
    }
}
