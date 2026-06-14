<?php

namespace App\Services\Portfolio;

use App\Models\Portfolio\Holding;
use App\Models\Portfolio\WatchlistItem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Pulls live unit prices for portfolio holdings from free public endpoints:
 *  - stocks / funds  → Yahoo Finance chart API (Thai tickers use the `.BK` suffix)
 *  - crypto          → CoinGecko `simple/price`
 *  - FX → THB        → Yahoo `<CCY>THB=X` (rates cached for 1h to avoid hammering)
 *
 * No API keys are required and no third-party SDKs are pulled in — we hit
 * the JSON endpoints with Laravel's HTTP client. Failures are logged and
 * swallowed: a holding that can't be priced simply keeps its previous value.
 */
class PriceFetcher
{
    private const YAHOO_BASE     = 'https://query1.finance.yahoo.com/v8/finance/chart/';
    private const COINGECKO_BASE = 'https://api.coingecko.com/api/v3/simple/price';

    private const HTTP_TIMEOUT_SEC = 8;
    private const FX_CACHE_TTL_SEC = 3600;

    /**
     * Refresh `current_price`, `current_value_thb` and `last_priced_at` for
     * every priceable holding belonging to the given user. Non-priceable
     * kinds (cash, deposit, debt, gold, real estate, other) are recomputed
     * from their stored unit price so totals stay consistent.
     *
     * @return array{updated:int, failed:int, errors:array<int,string>}
     */
    public function refreshForUser(int $userId): array
    {
        $holdings = Holding::query()->forUser($userId)->get();

        $updated = 0;
        $failed = 0;
        $errors = [];

        foreach ($holdings as $holding) {
            try {
                if ($holding->isPriceable()) {
                    $price = $this->fetchUnitPrice($holding->kind, (string) $holding->symbol);
                    if ($price !== null) {
                        $holding->current_price = $price;
                        $holding->last_priced_at = now();
                        $updated++;
                    } else {
                        $failed++;
                        $errors[] = "{$holding->symbol}: no price returned";
                    }
                }

                $holding->current_value_thb = $this->toThb(
                    (float) ($holding->current_price ?? 0),
                    (float) $holding->quantity,
                    (string) $holding->currency,
                );
                $holding->save();
            } catch (Throwable $e) {
                $failed++;
                $errors[] = "{$holding->symbol}: " . $e->getMessage();
                Log::warning('portfolio.price_refresh.failed', [
                    'holding_id' => $holding->id,
                    'symbol' => $holding->symbol,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return ['updated' => $updated, 'failed' => $failed, 'errors' => $errors];
    }

    /**
     * Refresh `current_price`, `current_price_thb` and `last_priced_at` for
     * every item in the user's watchlist.
     *
     * @return array{updated:int, failed:int, errors:array<int,string>}
     */
    public function refreshWatchlistForUser(int $userId): array
    {
        $items = WatchlistItem::query()->forUser($userId)->get();

        $updated = 0;
        $failed = 0;
        $errors = [];

        foreach ($items as $item) {
            try {
                if ($item->isPriceable()) {
                    $price = $this->fetchUnitPrice($item->kind, (string) $item->symbol);
                    if ($price !== null) {
                        $item->current_price = $price;
                        $item->last_priced_at = now();

                        // Convert to THB
                        $item->current_price_thb = $this->toThb(
                            (float) $price,
                            1.0,
                            (string) $item->currency
                        );
                        $item->save();
                        $updated++;
                    } else {
                        $failed++;
                        $errors[] = "{$item->symbol}: no price returned";
                    }
                }
            } catch (Throwable $e) {
                $failed++;
                $errors[] = "{$item->symbol}: " . $e->getMessage();
                Log::warning('portfolio.watchlist_refresh.failed', [
                    'item_id' => $item->id,
                    'symbol' => $item->symbol,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return ['updated' => $updated, 'failed' => $failed, 'errors' => $errors];
    }

    /**
     * Convert (price × quantity) in `currency` to THB using cached FX rates.
     * Returns null if the FX rate can't be resolved — caller decides what to
     * do with stale values.
     */
    public function toThb(float $unitPrice, float $quantity, string $currency): ?float
    {
        $native = $unitPrice * $quantity;
        $ccy = strtoupper(trim($currency)) ?: 'THB';

        if ($ccy === 'THB') {
            return round($native, 2);
        }

        $rate = $this->fxRateToThb($ccy);
        if ($rate === null) {
            return null;
        }

        return round($native * $rate, 2);
    }

    private function fetchUnitPrice(string $kind, string $symbol): ?float
    {
        return match ($kind) {
            Holding::KIND_STOCK, Holding::KIND_FUND => $this->yahooPrice($symbol),
            Holding::KIND_CRYPTO => $this->coinGeckoPrice($symbol),
            default => null,
        };
    }

    private function yahooPrice(string $symbol): ?float
    {
        $resp = Http::timeout(self::HTTP_TIMEOUT_SEC)
            ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Tirmongkol Portfolio)'])
            ->get(self::YAHOO_BASE . rawurlencode($symbol), [
                'interval' => '1d',
                'range' => '1d',
            ]);

        if (! $resp->ok()) {
            return null;
        }

        $price = data_get($resp->json(), 'chart.result.0.meta.regularMarketPrice');

        return is_numeric($price) ? (float) $price : null;
    }

    private function coinGeckoPrice(string $symbol): ?float
    {
        // CoinGecko ids are slugs (e.g. "bitcoin", "ethereum"); price comes
        // back already in THB so no FX hop needed.
        $resp = Http::timeout(self::HTTP_TIMEOUT_SEC)
            ->get(self::COINGECKO_BASE, [
                'ids' => $symbol,
                'vs_currencies' => 'thb',
            ]);

        if (! $resp->ok()) {
            return null;
        }

        $price = data_get($resp->json(), $symbol . '.thb');

        return is_numeric($price) ? (float) $price : null;
    }

    private function fxRateToThb(string $currency): ?float
    {
        $key = 'portfolio.fx.' . strtolower($currency) . '_thb';

        return Cache::remember($key, self::FX_CACHE_TTL_SEC, function () use ($currency) {
            try {
                $resp = Http::timeout(self::HTTP_TIMEOUT_SEC)
                    ->withHeaders(['User-Agent' => 'Mozilla/5.0 (Tirmongkol Portfolio)'])
                    ->get(self::YAHOO_BASE . rawurlencode($currency . 'THB=X'), [
                        'interval' => '1d',
                        'range' => '1d',
                    ]);

                if (! $resp->ok()) {
                    return null;
                }

                $rate = data_get($resp->json(), 'chart.result.0.meta.regularMarketPrice');

                return is_numeric($rate) ? (float) $rate : null;
            } catch (Throwable $e) {
                Log::warning('portfolio.fx.failed', ['ccy' => $currency, 'error' => $e->getMessage()]);

                return null;
            }
        });
    }
}
