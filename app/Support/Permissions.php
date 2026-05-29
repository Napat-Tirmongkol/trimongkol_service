<?php

namespace App\Support;

/**
 * Thin reader over config/permissions.php — the back-office permission
 * catalog. Used to register gates and render the role editor.
 */
class Permissions
{
    /** @return array<string, array> group key => group definition */
    public static function groups(): array
    {
        return config('permissions.groups', []);
    }

    /** @return array<int, string> flat list of every permission key */
    public static function keys(): array
    {
        $keys = [];
        foreach (static::groups() as $group) {
            foreach (array_keys($group['permissions'] ?? []) as $key) {
                $keys[] = $key;
            }
        }
        return $keys;
    }

    /** Locale-aware label for a group or permission row. */
    public static function label(array $row): string
    {
        return app()->getLocale() === 'en' && ! empty($row['label_en'])
            ? $row['label_en']
            : ($row['label'] ?? '');
    }
}
