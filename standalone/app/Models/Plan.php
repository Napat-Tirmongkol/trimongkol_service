<?php

namespace App\Models;

/**
 * Plan is config-backed — see config/plans.php. This is a thin facade
 * over that config so calling code reads like an ordinary model.
 */
class Plan
{
    public const FREE = 'free';
    public const PRO = 'pro';
    public const BUSINESS = 'business';

    public function __construct(
        public readonly string $key,
        public readonly string $name,
        public readonly int $price,
        public readonly string $tagline,
        /** @var array<string> */
        public readonly array $features,
        /** @var array<string, int|null> */
        public readonly array $limits,
    ) {}

    public static function find(string $key): ?self
    {
        $row = config("plans.{$key}");
        if (! $row) {
            return null;
        }

        $locale = app()->getLocale();
        $taglineKey = $locale === 'en' && ! empty($row['tagline_en']) ? 'tagline_en' : 'tagline';
        $featuresKey = $locale === 'en' && ! empty($row['features_en']) ? 'features_en' : 'features';

        return new self(
            key: $key,
            name: $row['name'],
            price: (int) $row['price'],
            tagline: $row[$taglineKey] ?? '',
            features: $row[$featuresKey] ?? [],
            limits: $row['limits'] ?? [],
        );
    }

    /** @return array<string, self> */
    public static function all(): array
    {
        $plans = [];
        foreach (array_keys(config('plans', [])) as $key) {
            $plans[$key] = self::find($key);
        }
        return $plans;
    }

    public function limit(string $name): ?int
    {
        return $this->limits[$name] ?? null;
    }

    public function isFree(): bool
    {
        return $this->key === self::FREE;
    }
}
