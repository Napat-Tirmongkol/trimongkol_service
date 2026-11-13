<?php

namespace App\Http\Controllers\Portfolio;

use App\Http\Controllers\Controller;
use App\Models\Portfolio\HealthMetric;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class HealthDashboardController extends Controller
{
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

    public function index(): View
    {
        $userId = $this->resolvePortfolioUserId();

        $metrics = collect();
        if (Schema::hasTable('portfolio_health_metrics')) {
            $metrics = HealthMetric::query()
                ->where('user_id', $userId)
                ->orderBy('date', 'asc')
                ->get();
        }

        $latest = $metrics->last();
        $recordsDesc = $metrics->sortByDesc('date');

        // Aggregated 30-Day Metrics
        $totalDays = $metrics->count();
        $totalSteps = $metrics->sum('steps');
        $avgSteps = $totalDays > 0 ? round($totalSteps / $totalDays) : 0;
        $maxStepDay = $metrics->sortByDesc('steps')->first();

        // Sleep calculations (ignore <= 15 min sync artifacts)
        $validSleep = $metrics->whereNotNull('sleep_minutes')->where('sleep_minutes', '>', 15);
        $avgSleepMin = $validSleep->count() > 0 ? round($validSleep->avg('sleep_minutes')) : 0;
        $avgDeepMin = $validSleep->count() > 0 ? round($validSleep->avg('deep_sleep_minutes')) : 0;
        $avgRemMin = $validSleep->count() > 0 ? round($validSleep->avg('rem_sleep_minutes')) : 0;

        // Heart Rate calculations
        $validHr = $metrics->whereNotNull('heart_rate_avg')->where('heart_rate_avg', '>', 0);
        $avgHr = $validHr->count() > 0 ? round($validHr->avg('heart_rate_avg')) : 0;
        $validResting = $metrics->whereNotNull('resting_heart_rate')->where('resting_heart_rate', '>', 0);
        $avgRestingHr = $validResting->count() > 0 ? round($validResting->avg('resting_heart_rate')) : 0;

        // Calories & Distance
        $totalCalories = $metrics->sum('active_calories');
        $avgCalories = $totalDays > 0 ? round($totalCalories / $totalDays) : 0;
        $totalDistanceKm = round($metrics->sum('distance_meters') / 1000, 1);

        // Chart Data formatting for 30-day graphs
        $chartLabels = [];
        $chartSteps = [];
        $chartSleep = [];
        $chartHr = [];

        foreach ($metrics->take(-30) as $m) {
            $chartLabels[] = Carbon::parse($m->date)->format('d/m');
            $chartSteps[] = (int) ($m->steps ?? 0);
            $chartSleep[] = ($m->sleep_minutes && $m->sleep_minutes > 15) ? round($m->sleep_minutes / 60, 1) : 0;
            $chartHr[] = (int) ($m->resting_heart_rate ?: $m->heart_rate_avg ?: 0);
        }

        // Google Calendar SOC Shift Roster Integration
        $calendarSummary = GoogleCalendarController::getShiftSummary();
        $todayShift = $calendarSummary['today_shift'] ?? null;
        $tomorrowShift = $calendarSummary['tomorrow_shift'] ?? null;
        $shiftFatigueAlert = null;

        // Samsung Energy Score-style Readiness Calculation (100 pts across 4 pillars)
        $latestSleepMetric = $metrics->filter(fn ($m) => ($m->sleep_minutes ?? 0) > 15)->last();
        $recentSleep = $latestSleepMetric ? (int) $latestSleepMetric->sleep_minutes : (int) $avgSleepMin;

        // 1. Sleep Recovery (40 pts = 25 duration + 15 restorative stages)
        if ($recentSleep > 0) {
            $sleepDurationPts = (int) min(25, round(($recentSleep / 450) * 25));
            if ($recentSleep > 570) {
                $sleepDurationPts = max(18, $sleepDurationPts - (int) floor(($recentSleep - 570) / 30));
            }
            $deepMin = (int) ($latestSleepMetric?->deep_sleep_minutes ?? $avgDeepMin);
            $remMin = (int) ($latestSleepMetric?->rem_sleep_minutes ?? $avgRemMin);
            if ($deepMin > 0 || $remMin > 0) {
                $restorativeRatio = ($deepMin + $remMin) / max(1, $recentSleep);
                $sleepStagePts = (int) min(15, round(($restorativeRatio / 0.35) * 15));
            } else {
                $sleepStagePts = (int) round(($sleepDurationPts / 25) * 15);
            }
            $sleepScore = $sleepDurationPts + $sleepStagePts;
        } else {
            $sleepScore = 24;
        }

        // 2. Heart & Oxygen Recovery (30 pts = 20 HR baseline delta + 10 SpO2)
        $latestHr = (int) ($latest?->resting_heart_rate ?: $latest?->heart_rate_avg ?: 0);
        $baselineHr = (int) ($avgRestingHr ?: $avgHr ?: 72);
        if ($latestHr > 0 && $baselineHr > 0) {
            $hrDelta = $baselineHr - $latestHr; // positive = calmer / better recovered
            $hrPts = (int) max(4, min(20, round(14 + ($hrDelta / 2))));
        } else {
            $hrPts = 14;
        }

        $latestSpo2 = (float) ($latest?->spo2_avg ?? 0);
        if ($latestSpo2 >= 96) {
            $spo2Pts = 10;
        } elseif ($latestSpo2 >= 94) {
            $spo2Pts = 8;
        } elseif ($latestSpo2 >= 91) {
            $spo2Pts = 5;
        } elseif ($latestSpo2 > 0) {
            $spo2Pts = 2;
        } else {
            $spo2Pts = 8;
        }
        $recoveryScore = $hrPts + $spo2Pts;

        // 3. Activity Balance (20 pts = 12 steps + 8 active calories)
        $latestSteps = (int) ($latest?->steps ?: $avgSteps);
        $latestCal = (float) ($latest?->active_calories ?: $avgCalories);
        $stepPts = (int) min(12, round(($latestSteps / 6000) * 12));
        if ($latestSteps > 18000) {
            $stepPts = max(8, $stepPts - 2);
        }
        $calPts = (int) min(8, round(($latestCal / 300) * 8));
        $activityScore = $stepPts + $calPts;

        // 4. Shift Load & Circadian Alignment (10 pts)
        $shiftScore = 8;
        if ($todayShift) {
            $shiftType = $todayShift['type'] ?? 'event';

            if ($shiftType === 'night') {
                if ($recentSleep < 360) {
                    $shiftFatigueAlert = [
                        'level' => 'warning',
                        'title' => '⚠️ Night Shift Fatigue Risk',
                        'message' => 'คืนนี้มีคิวเวรดึก (23:00–07:00) แต่นอนพักต่ำกว่า 6 ชม. แนะนำงีบ Power Nap 20–30 นาทีก่อนเข้ากะ เพื่อรักษาความเฉียบคมในการวิเคราะห์ Incident Alert!',
                    ];
                    $shiftScore = 2;
                } else {
                    $shiftFatigueAlert = [
                        'level' => 'success',
                        'title' => '⚡ Night Shift Ready',
                        'message' => 'คืนนี้มีเวรดึก ระดับการพักผ่อนดีเยี่ยม ร่างกายพร้อมสำหรับการเฝ้าระวังภัยคุกคามแบบ Real-Time ตลอดทั้งคืน',
                    ];
                    $shiftScore = 8;
                }
            } elseif ($shiftType === 'off') {
                $shiftFatigueAlert = [
                    'level' => 'info',
                    'title' => '🏖️ Recovery & Rest Day',
                    'message' => 'วันนี้เป็นวันพักผ่อน ฟื้นฟูสมดุลของร่างกาย (Circadian Rhythm) และชาร์จพลังงานให้เต็มหลอด!',
                ];
                $shiftScore = 10;
            }
        }

        $readinessScore = (int) max(25, min(99, $sleepScore + $recoveryScore + $activityScore + $shiftScore));

        $readinessMeta = match (true) {
            $readinessScore >= 85 => [
                'label'      => 'พลังงานเต็มหลอด พร้อมลุยหนัก',
                'badgeClass' => 'bg-emerald-500/20 text-emerald-400',
                'textClass'  => 'text-emerald-300',
            ],
            $readinessScore >= 70 => [
                'label'      => 'สมดุลดี พร้อมทำงานปกติ',
                'badgeClass' => 'bg-cyan-500/20 text-cyan-400',
                'textClass'  => 'text-cyan-300',
            ],
            $readinessScore >= 55 => [
                'label'      => 'เริ่มล้า ควรพักผ่อนเพิ่ม',
                'badgeClass' => 'bg-amber-500/20 text-amber-400',
                'textClass'  => 'text-amber-300',
            ],
            default => [
                'label'      => 'พลังงานต่ำ ระวังอาการล้า',
                'badgeClass' => 'bg-rose-500/20 text-rose-400',
                'textClass'  => 'text-rose-300',
            ],
        };
        $readinessMeta['breakdown'] = [
            'sleep'    => ['score' => $sleepScore, 'max' => 40, 'label' => 'นอน'],
            'recovery' => ['score' => $recoveryScore, 'max' => 30, 'label' => 'หัวใจ/SpO2'],
            'activity' => ['score' => $activityScore, 'max' => 20, 'label' => 'กิจกรรม'],
            'shift'    => ['score' => $shiftScore, 'max' => 10, 'label' => 'กะงาน'],
        ];

        return view('portfolio.health', compact(
            'latest',
            'recordsDesc',
            'totalDays',
            'totalSteps',
            'avgSteps',
            'maxStepDay',
            'avgSleepMin',
            'avgDeepMin',
            'avgRemMin',
            'avgHr',
            'avgRestingHr',
            'totalCalories',
            'avgCalories',
            'totalDistanceKm',
            'chartLabels',
            'chartSteps',
            'chartSleep',
            'chartHr',
            'readinessScore',
            'readinessMeta',
            'calendarSummary',
            'todayShift',
            'tomorrowShift',
            'shiftFatigueAlert'
        ));
    }
}
