<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    protected $table = 'site_settings';

    protected $fillable = ['key', 'value'];

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('site_settings.all'));
        static::deleted(fn () => Cache::forget('site_settings.all'));
    }

    /**
     * All settings as a plain associative array (key => value).
     * Cached forever; busted on save/delete.
     */
    public static function map(): array
    {
        return Cache::rememberForever('site_settings.all', function () {
            return self::query()->pluck('value', 'key')->all();
        });
    }

    public static function get(string $key, ?string $default = null): ?string
    {
        $map = self::map();
        $v = $map[$key] ?? null;
        return ($v === null || $v === '') ? $default : $v;
    }
}
