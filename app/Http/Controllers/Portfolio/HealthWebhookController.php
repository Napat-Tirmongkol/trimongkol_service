<?php

namespace App\Http\Controllers\Portfolio;

use App\Http\Controllers\Controller;
use App\Models\Portfolio\HealthMetric;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class HealthWebhookController extends Controller
{
    private function ensureTableExists(): void
    {
        if (!\Illuminate\Support\Facades\Schema::hasTable('portfolio_health_metrics')) {
            \Illuminate\Support\Facades\Schema::create('portfolio_health_metrics', function (\Illuminate\Database\Schema\Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id');
                $table->date('date');
                $table->unsignedInteger('steps')->nullable();
                $table->decimal('distance_meters', 10, 2)->nullable();
                $table->decimal('active_calories', 8, 2)->nullable();
                $table->decimal('total_calories', 8, 2)->nullable();
                $table->unsignedSmallInteger('heart_rate_avg')->nullable();
                $table->unsignedSmallInteger('heart_rate_min')->nullable();
                $table->unsignedSmallInteger('heart_rate_max')->nullable();
                $table->unsignedSmallInteger('resting_heart_rate')->nullable();
                $table->unsignedSmallInteger('sleep_minutes')->nullable();
                $table->unsignedSmallInteger('deep_sleep_minutes')->nullable();
                $table->unsignedSmallInteger('rem_sleep_minutes')->nullable();
                $table->unsignedSmallInteger('light_sleep_minutes')->nullable();
                $table->decimal('spo2_avg', 5, 2)->nullable();
                $table->string('source', 50)->default('health_connect');
                $table->longText('raw_payload')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'date'], 'user_health_date_unique');
                $table->index('user_id');
                $table->index('date');
            });
        }
    }

    private function resolvePortfolioUserId(): int
    {
        $allowed = (array) config('portfolio.allowed_emails', []);
        $primaryEmail = !empty($allowed) ? strtolower($allowed[0]) : null;
        if ($primaryEmail) {
            $owner = User::where('email', $primaryEmail)->first();
            if ($owner) {
                return $owner->id;
            }
        }
        return (int) (auth()->id() ?? 1);
    }

    private function getExpectedSecret(): string
    {
        return (string) config('services.health_webhook.secret', '');
    }

    private function verifyAuth(Request $request): bool
    {
        $expected = $this->getExpectedSecret();
        if (empty($expected)) {
            return false;
        }

        $token = $request->bearerToken()
            ?: $request->header('X-Health-Token')
            ?: $request->header('X-API-KEY')
            ?: $request->query('token');

        return hash_equals($expected, (string) $token);
    }

    private function toIntOrNull(mixed $value): ?int
    {
        return is_numeric($value) ? (int) round((float) $value) : null;
    }

    private function toFloatOrNull(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }

    private function resolveRecordDate(?string $rawTimestamp, string $timezone): string
    {
        try {
            $dt = $rawTimestamp ? Carbon::parse($rawTimestamp) : Carbon::now();
            return $dt->timezone($timezone)->toDateString();
        } catch (\Throwable $e) {
            return Carbon::now($timezone)->toDateString();
        }
    }

    private function isPartialDayWindow(?string $startTime, string $timezone): bool
    {
        if (!$startTime) {
            return false;
        }
        try {
            $dt = Carbon::parse($startTime)->timezone($timezone);
            return $dt->hour > 0 || $dt->minute > 5;
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function isBucketedHealthConnectPayload(array $payload): bool
    {
        $bucketKeys = ['steps', 'distance', 'active_calories', 'total_calories', 'heart_rate', 'resting_heart_rate', 'sleep', 'oxygen_saturation'];
        foreach ($bucketKeys as $key) {
            if (isset($payload[$key]) && is_array($payload[$key])) {
                return true;
            }
        }
        return false;
    }

    /**
     * Aggregate Health Connect Gateway array-based telemetry into per-date records.
     *
     * @return array<int, array<string, mixed>>
     */
    private function normalizeBucketedPayload(array $payload): array
    {
        $timezone = is_string($payload['timezone'] ?? null) && $payload['timezone'] !== ''
            ? $payload['timezone']
            : 'Asia/Bangkok';
        $source = is_string($payload['source'] ?? null) ? $payload['source'] : 'health_connect';
        $days = [];

        $initDay = static function (array &$days, string $date) use ($source): void {
            if (!isset($days[$date])) {
                $days[$date] = [
                    'date'                => $date,
                    'source'              => $source,
                    'is_partial_activity' => false,
                    '_hr_sum'             => 0.0,
                    '_hr_count'           => 0,
                    '_hr_min'             => null,
                    '_hr_max'             => null,
                    '_rhr_sum'            => 0.0,
                    '_rhr_count'          => 0,
                    '_spo2_sum'           => 0.0,
                    '_spo2_count'         => 0,
                    '_sleep_sec'          => 0,
                    '_deep_sec'           => 0,
                    '_rem_sec'            => 0,
                    '_light_sec'          => 0,
                ];
            }
        };

        foreach ((array) ($payload['steps'] ?? []) as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $count = $this->toIntOrNull($entry['count'] ?? $entry['steps'] ?? null);
            if ($count === null) {
                continue;
            }
            $startTime = $entry['start_time'] ?? $entry['end_time'] ?? null;
            $date = $this->resolveRecordDate($startTime, $timezone);
            $initDay($days, $date);
            $days[$date]['steps'] = ($days[$date]['steps'] ?? 0) + $count;
            if ($this->isPartialDayWindow($entry['start_time'] ?? null, $timezone)) {
                $days[$date]['is_partial_activity'] = true;
            }
        }

        foreach ((array) ($payload['distance'] ?? []) as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $meters = $this->toFloatOrNull($entry['meters'] ?? $entry['distance_meters'] ?? null);
            if ($meters === null) {
                continue;
            }
            $date = $this->resolveRecordDate($entry['start_time'] ?? $entry['end_time'] ?? null, $timezone);
            $initDay($days, $date);
            $days[$date]['distance_meters'] = round(($days[$date]['distance_meters'] ?? 0.0) + $meters, 2);
        }

        foreach ((array) ($payload['active_calories'] ?? []) as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $cal = $this->toFloatOrNull($entry['calories'] ?? $entry['active_calories'] ?? null);
            if ($cal === null) {
                continue;
            }
            $date = $this->resolveRecordDate($entry['start_time'] ?? $entry['end_time'] ?? null, $timezone);
            $initDay($days, $date);
            $days[$date]['active_calories'] = round(($days[$date]['active_calories'] ?? 0.0) + $cal, 2);
        }

        foreach ((array) ($payload['total_calories'] ?? []) as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $cal = $this->toFloatOrNull($entry['calories'] ?? $entry['total_calories'] ?? null);
            if ($cal === null) {
                continue;
            }
            $date = $this->resolveRecordDate($entry['start_time'] ?? $entry['end_time'] ?? null, $timezone);
            $initDay($days, $date);
            $days[$date]['total_calories'] = round(($days[$date]['total_calories'] ?? 0.0) + $cal, 2);
        }

        foreach ((array) ($payload['heart_rate'] ?? []) as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $bpm = $this->toFloatOrNull($entry['bpm'] ?? $entry['avg'] ?? null);
            if ($bpm === null || $bpm <= 0) {
                continue;
            }
            $date = $this->resolveRecordDate($entry['time'] ?? $entry['start_time'] ?? null, $timezone);
            $initDay($days, $date);
            $days[$date]['_hr_sum'] += $bpm;
            $days[$date]['_hr_count']++;
            $minVal = $this->toIntOrNull($entry['min'] ?? $bpm);
            $maxVal = $this->toIntOrNull($entry['max'] ?? $bpm);
            if ($minVal !== null) {
                $days[$date]['_hr_min'] = $days[$date]['_hr_min'] === null ? $minVal : min($days[$date]['_hr_min'], $minVal);
            }
            if ($maxVal !== null) {
                $days[$date]['_hr_max'] = $days[$date]['_hr_max'] === null ? $maxVal : max($days[$date]['_hr_max'], $maxVal);
            }
        }

        foreach ((array) ($payload['resting_heart_rate'] ?? []) as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $rhr = $this->toFloatOrNull($entry['bpm'] ?? $entry['resting_heart_rate'] ?? null);
            if ($rhr === null || $rhr <= 0) {
                continue;
            }
            $date = $this->resolveRecordDate($entry['time'] ?? $entry['start_time'] ?? null, $timezone);
            $initDay($days, $date);
            $days[$date]['_rhr_sum'] += $rhr;
            $days[$date]['_rhr_count']++;
        }

        foreach ((array) ($payload['sleep'] ?? []) as $session) {
            if (!is_array($session)) {
                continue;
            }
            $durationSec = $this->toIntOrNull($session['duration_seconds'] ?? null) ?? 0;
            $date = $this->resolveRecordDate($session['session_end_time'] ?? $session['end_time'] ?? $session['start_time'] ?? null, $timezone);
            $initDay($days, $date);
            $days[$date]['_sleep_sec'] += $durationSec;

            foreach ((array) ($session['stages'] ?? []) as $stage) {
                if (!is_array($stage)) {
                    continue;
                }
                $stageSec = $this->toIntOrNull($stage['duration_seconds'] ?? null) ?? 0;
                $code = (string) ($stage['stage'] ?? '');
                // Android Health Connect SleepSessionRecord constants: 4=LIGHT, 5=DEEP, 6=REM
                if ($code === '4') {
                    $days[$date]['_light_sec'] += $stageSec;
                } elseif ($code === '5') {
                    $days[$date]['_deep_sec'] += $stageSec;
                } elseif ($code === '6') {
                    $days[$date]['_rem_sec'] += $stageSec;
                }
            }
        }

        foreach ((array) ($payload['oxygen_saturation'] ?? []) as $entry) {
            if (!is_array($entry)) {
                continue;
            }
            $pct = $this->toFloatOrNull($entry['percentage'] ?? $entry['avg'] ?? null);
            if ($pct === null || $pct <= 0) {
                continue;
            }
            $date = $this->resolveRecordDate($entry['time'] ?? $entry['start_time'] ?? null, $timezone);
            $initDay($days, $date);
            $days[$date]['_spo2_sum'] += $pct;
            $days[$date]['_spo2_count']++;
        }

        $normalized = [];
        ksort($days);
        foreach ($days as $day) {
            if ($day['_hr_count'] > 0) {
                $day['heart_rate_avg'] = (int) round($day['_hr_sum'] / $day['_hr_count']);
                $day['heart_rate_min'] = $day['_hr_min'];
                $day['heart_rate_max'] = $day['_hr_max'];
            }
            if ($day['_rhr_count'] > 0) {
                $day['resting_heart_rate'] = (int) round($day['_rhr_sum'] / $day['_rhr_count']);
            }
            if ($day['_spo2_count'] > 0) {
                $day['spo2_avg'] = round($day['_spo2_sum'] / $day['_spo2_count'], 2);
            }
            if ($day['_sleep_sec'] > 0) {
                $day['sleep_minutes'] = (int) round($day['_sleep_sec'] / 60);
                if ($day['_deep_sec'] > 0) {
                    $day['deep_sleep_minutes'] = (int) round($day['_deep_sec'] / 60);
                }
                if ($day['_rem_sec'] > 0) {
                    $day['rem_sleep_minutes'] = (int) round($day['_rem_sec'] / 60);
                }
                if ($day['_light_sec'] > 0) {
                    $day['light_sleep_minutes'] = (int) round($day['_light_sec'] / 60);
                }
            }
            unset(
                $day['_hr_sum'],
                $day['_hr_count'],
                $day['_hr_min'],
                $day['_hr_max'],
                $day['_rhr_sum'],
                $day['_rhr_count'],
                $day['_spo2_sum'],
                $day['_spo2_count'],
                $day['_sleep_sec'],
                $day['_deep_sec'],
                $day['_rem_sec'],
                $day['_light_sec']
            );
            $normalized[] = $day;
        }

        return $normalized;
    }

    public function handle(Request $request): JsonResponse
    {
        $this->ensureTableExists();

        if (!$this->verifyAuth($request)) {
            return response()->json([
                'success' => false,
                'error'   => 'Unauthorized: Invalid health webhook secret token',
            ], 401);
        }

        $userId = $this->resolvePortfolioUserId();
        $payload = $request->all();

        // Allow lightweight webhook ping/test requests without overwriting real metrics
        $hasTestPing = !empty($payload['test']) && !$this->isBucketedHealthConnectPayload($payload) && !isset($payload['steps']);
        if (empty($payload) || $hasTestPing) {
            return response()->json([
                'success'        => true,
                'message'        => 'Health webhook connection verified',
                'records_synced' => 0,
                'latest_date'    => null,
                'timestamp'      => Carbon::now()->toIso8601String(),
            ]);
        }

        if ($this->isBucketedHealthConnectPayload($payload)) {
            $records = $this->normalizeBucketedPayload($payload);
        } else {
            // Support array batch payload or single flat payload
            $records = isset($payload[0]) && is_array($payload[0]) ? $payload : [$payload];
        }

        $processedCount = 0;
        $latestRecordDate = null;

        foreach ($records as $item) {
            if (!is_array($item)) {
                continue;
            }
            $rawDate = $item['date'] ?? $item['recorded_date'] ?? $item['start_time'] ?? null;
            $targetDate = $this->resolveRecordDate(is_string($rawDate) ? $rawDate : null, 'Asia/Bangkok');

            $steps = $this->toIntOrNull($item['steps'] ?? data_get($item, 'data.steps.count') ?? data_get($item, 'metrics.steps') ?? null);
            $distance = $this->toFloatOrNull($item['distance_meters'] ?? $item['distance'] ?? data_get($item, 'data.distance.meters') ?? null);
            $activeCalories = $this->toFloatOrNull($item['active_calories'] ?? $item['calories'] ?? data_get($item, 'data.active_calories.calories') ?? null);
            $totalCalories = $this->toFloatOrNull($item['total_calories'] ?? data_get($item, 'data.total_calories.calories') ?? null);

            $hrAvg = $this->toIntOrNull($item['heart_rate_avg'] ?? $item['heart_rate'] ?? data_get($item, 'data.heart_rate.avg') ?? data_get($item, 'data.heart_rate.bpm') ?? null);
            $hrMin = $this->toIntOrNull($item['heart_rate_min'] ?? data_get($item, 'data.heart_rate.min') ?? null);
            $hrMax = $this->toIntOrNull($item['heart_rate_max'] ?? data_get($item, 'data.heart_rate.max') ?? null);
            $restingHr = $this->toIntOrNull($item['resting_heart_rate'] ?? data_get($item, 'data.resting_heart_rate.bpm') ?? null);

            $sleepMinutes = $this->toIntOrNull($item['sleep_minutes'] ?? $item['sleep'] ?? data_get($item, 'data.sleep.total_minutes') ?? null);
            if ($sleepMinutes === null && is_numeric(data_get($item, 'data.sleep.duration_seconds'))) {
                $sleepMinutes = (int) round(((float) data_get($item, 'data.sleep.duration_seconds')) / 60);
            }
            $deepSleep = $this->toIntOrNull($item['deep_sleep_minutes'] ?? data_get($item, 'data.sleep.deep_minutes') ?? null);
            $remSleep = $this->toIntOrNull($item['rem_sleep_minutes'] ?? data_get($item, 'data.sleep.rem_minutes') ?? null);
            $lightSleep = $this->toIntOrNull($item['light_sleep_minutes'] ?? data_get($item, 'data.sleep.light_minutes') ?? null);
            $spo2 = $this->toFloatOrNull($item['spo2_avg'] ?? $item['spo2'] ?? data_get($item, 'data.spo2.percentage') ?? null);

            $source = is_string($item['source'] ?? null) ? $item['source'] : 'health_connect';
            $isPartialActivity = !empty($item['is_partial_activity']);
            unset($item['is_partial_activity']);

            try {
                $existing = HealthMetric::where('user_id', $userId)
                    ->where('date', $targetDate)
                    ->first();

                if ($existing) {
                    $updateData = ['source' => $source];
                    // Prevent a trailing 24h rolling tail from overwriting a fuller earlier-day activity total
                    $allowActivityUpdate = !$isPartialActivity || ($existing->steps ?? 0) <= ($steps ?? 0);
                    if ($allowActivityUpdate) {
                        if ($steps !== null) $updateData['steps'] = $steps;
                        if ($distance !== null) $updateData['distance_meters'] = $distance;
                        if ($activeCalories !== null) $updateData['active_calories'] = $activeCalories;
                        if ($totalCalories !== null) $updateData['total_calories'] = $totalCalories;
                    }
                    if ($hrAvg !== null) $updateData['heart_rate_avg'] = $hrAvg;
                    if ($hrMin !== null) $updateData['heart_rate_min'] = $hrMin;
                    if ($hrMax !== null) $updateData['heart_rate_max'] = $hrMax;
                    if ($restingHr !== null) $updateData['resting_heart_rate'] = $restingHr;
                    if ($sleepMinutes !== null && (!$isPartialActivity || ($existing->sleep_minutes ?? 0) <= $sleepMinutes)) {
                        $updateData['sleep_minutes'] = $sleepMinutes;
                        if ($deepSleep !== null) $updateData['deep_sleep_minutes'] = $deepSleep;
                        if ($remSleep !== null) $updateData['rem_sleep_minutes'] = $remSleep;
                        if ($lightSleep !== null) $updateData['light_sleep_minutes'] = $lightSleep;
                    }
                    if ($spo2 !== null) $updateData['spo2_avg'] = $spo2;
                    $updateData['raw_payload'] = json_encode($item, JSON_UNESCAPED_UNICODE);

                    $existing->update($updateData);
                } else {
                    HealthMetric::create([
                        'user_id'             => $userId,
                        'date'                => $targetDate,
                        'steps'               => $steps,
                        'distance_meters'     => $distance,
                        'active_calories'     => $activeCalories,
                        'total_calories'      => $totalCalories,
                        'heart_rate_avg'      => $hrAvg,
                        'heart_rate_min'      => $hrMin,
                        'heart_rate_max'      => $hrMax,
                        'resting_heart_rate'  => $restingHr,
                        'sleep_minutes'       => $sleepMinutes,
                        'deep_sleep_minutes'  => $deepSleep,
                        'rem_sleep_minutes'   => $remSleep,
                        'light_sleep_minutes' => $lightSleep,
                        'spo2_avg'            => $spo2,
                        'source'              => $source,
                        'raw_payload'         => json_encode($item, JSON_UNESCAPED_UNICODE),
                    ]);
                }

                $processedCount++;
                $latestRecordDate = $targetDate;
            } catch (\Throwable $e) {
                Log::error('Health webhook sync error: ' . $e->getMessage());
            }
        }

        return response()->json([
            'success'          => true,
            'message'          => 'Health metrics synced successfully',
            'records_synced'   => $processedCount,
            'latest_date'      => $latestRecordDate,
            'timestamp'        => Carbon::now()->toIso8601String(),
        ]);
    }

    public function test(Request $request): JsonResponse
    {
        if (! $this->verifyAuth($request)) {
            return response()->json([
                'ok'    => false,
                'error' => 'Unauthorized: Invalid health webhook secret token',
            ], 401);
        }

        $this->ensureTableExists();

        $userId = $this->resolvePortfolioUserId();
        $latest = HealthMetric::where('user_id', $userId)->orderByDesc('date')->first();

        return response()->json([
            'status'         => 'ready',
            'endpoint'       => route('portfolio.health.webhook'),
            'method'         => 'POST',
            'headers_needed' => [
                'Authorization' => 'Bearer <HEALTH_WEBHOOK_SECRET>',
                'Content-Type'  => 'application/json',
            ],
            'alternative_header' => 'X-Health-Token: <HEALTH_WEBHOOK_SECRET>',
            'latest_synced'  => $latest ? [
                'date'           => $latest->date->toDateString(),
                'steps'          => $latest->steps,
                'heart_rate_avg' => $latest->heart_rate_avg,
                'sleep_minutes'  => $latest->sleep_minutes,
                'calories'       => $latest->active_calories,
                'updated_at'     => $latest->updated_at->toIso8601String(),
            ] : null,
        ]);
    }
}
