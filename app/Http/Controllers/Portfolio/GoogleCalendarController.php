<?php

namespace App\Http\Controllers\Portfolio;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GoogleCalendarController extends Controller
{
    private static string $tokenFile = 'google_calendar_token.json';
    private static string $shiftsFile = 'google_calendar_shifts.json';

    public function connect(): RedirectResponse
    {
        $clientId = config('services.google_calendar.client_id');
        $redirectUri = config('services.google_calendar.redirect');

        if (! $clientId) {
            return redirect()->route('portfolio.health.index')
                ->with('error', 'ยังไม่ได้ระบุ GOOGLE_CALENDAR_CLIENT_ID ในระบบ');
        }

        $params = [
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/calendar.events https://www.googleapis.com/auth/calendar.readonly',
            'access_type' => 'offline',
            'prompt' => 'consent',
            'include_granted_scopes' => 'true',
            'state' => csrf_token(),
        ];

        return redirect('https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params));
    }

    public function callback(Request $request): RedirectResponse
    {
        if ($request->has('error')) {
            return redirect()->route('portfolio.health.index')
                ->with('error', 'การเชื่อมต่อ Google Calendar ถูกยกเลิก: ' . $request->query('error'));
        }

        $code = $request->query('code');
        if (! $code) {
            return redirect()->route('portfolio.health.index')
                ->with('error', 'ไม่พบ Authorization Code จาก Google');
        }

        $clientId = config('services.google_calendar.client_id');
        $clientSecret = config('services.google_calendar.client_secret');
        $redirectUri = config('services.google_calendar.redirect');

        try {
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'code' => $code,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
            ]);

            if (! $response->successful()) {
                Log::error('Google Calendar Token Error', ['body' => $response->body()]);
                return redirect()->route('portfolio.health.index')
                    ->with('error', 'แลกเปลี่ยน Token ไม่สำเร็จ: ' . ($response->json('error_description') ?: $response->body()));
            }

            $tokenData = $response->json();
            $tokenData['created_at'] = time();

            $this->saveToken($tokenData);

            // Fetch and parse events across all relevant calendars
            $shifts = $this->fetchUpcomingEvents($tokenData['access_token']);
            $this->saveShifts($shifts);

            return redirect()->route('portfolio.health.index')
                ->with('status', 'เชื่อมต่อ Google Calendar สำเร็จ! ดึงตารางเวร SOC ' . count($shifts) . ' รายการเรียบร้อยแล้ว');
        } catch (Throwable $e) {
            Log::error('Google Calendar Callback Exception', ['message' => $e->getMessage()]);
            return redirect()->route('portfolio.health.index')
                ->with('error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ: ' . $e->getMessage());
        }
    }

    public function sync(): RedirectResponse
    {
        $token = $this->getValidToken();
        if (! $token) {
            return redirect()->route('portfolio.health.index')
                ->with('error', 'ไม่พบ Token Google Calendar กรุณากดเชื่อมต่อใหม่');
        }

        try {
            $shifts = $this->fetchUpcomingEvents($token['access_token']);
            $this->saveShifts($shifts);

            return redirect()->route('portfolio.health.index')
                ->with('status', 'อัปเดตตารางเวร SOC ล่าสุดสำเร็จ (' . count($shifts) . ' รายการ)');
        } catch (Throwable $e) {
            return redirect()->route('portfolio.health.index')
                ->with('error', 'ซิงก์ข้อมูลไม่สำเร็จ: ' . $e->getMessage());
        }
    }

    public function storeEvent(Request $request): RedirectResponse|JsonResponse
    {
        $token = $this->getValidToken();
        if (! $token) {
            return redirect()->route('portfolio.health.index')
                ->with('error', 'ไม่พบ Token Google Calendar กรุณาเชื่อมต่อใหม่');
        }

        $data = $request->validate([
            'summary'     => 'required|string|max:255',
            'start_time'  => 'required|string',
            'end_time'    => 'required|string',
            'description' => 'nullable|string',
            'location'    => 'nullable|string',
        ]);

        try {
            $res = Http::withToken($token['access_token'])->post('https://www.googleapis.com/calendar/v3/calendars/primary/events', [
                'summary'     => $data['summary'],
                'description' => $data['description'] ?? '',
                'location'    => $data['location'] ?? '',
                'start'       => ['dateTime' => Carbon::parse($data['start_time'])->toRfc3339String()],
                'end'         => ['dateTime' => Carbon::parse($data['end_time'])->toRfc3339String()],
            ]);

            if (! $res->successful()) {
                Log::warning('Google Calendar Event Insert Error: ' . $res->body());
                return redirect()->route('portfolio.health.index')
                    ->with('error', 'บันทึกกิจกรรมลง Google ไม่สำเร็จ: ' . ($res->json('error.message') ?? 'อาจต้องกดยกเลิกและเชื่อมต่อใหม่เพื่อขอสิทธิ์เขียนปฏิทิน'));
            }

            // Re-sync
            $shifts = $this->fetchUpcomingEvents($token['access_token']);
            $this->saveShifts($shifts);

            return redirect()->route('portfolio.health.index')
                ->with('status', 'บันทึกนัดหมาย "' . $data['summary'] . '" ลง Google Calendar เรียบร้อยแล้ว! 📅');
        } catch (Throwable $e) {
            return redirect()->route('portfolio.health.index')
                ->with('error', 'เกิดข้อผิดพลาดในการบันทึกกิจกรรม: ' . $e->getMessage());
        }
    }

    public function disconnect(): RedirectResponse
    {
        $this->deleteFile(self::$tokenFile);
        $this->deleteFile(self::$shiftsFile);

        return redirect()->route('portfolio.health.index')
            ->with('status', 'ยกเลิกการเชื่อมต่อ Google Calendar เรียบร้อยแล้ว');
    }

    public function getShiftsJson(): JsonResponse
    {
        $summary = self::getShiftSummary();
        return response()->json($summary);
    }

    public static function getShiftSummary(): array
    {
        $instance = new self();
        $token = $instance->readToken();
        $isConnected = ! empty($token['access_token']);

        $shifts = $instance->readShifts();
        $now = Carbon::now();
        $todayStart = $now->copy()->startOfDay();
        $todayEnd = $now->copy()->endOfDay();
        $tomorrowStart = $now->copy()->addDay()->startOfDay();
        $tomorrowEnd = $now->copy()->addDay()->endOfDay();

        $todayShifts = [];
        $tomorrowShifts = [];
        $upcoming = [];

        // Priority weighting for picking the "hero" shift
        $priority = [
            'night' => 100,
            'morning' => 90,
            'day' => 90,
            'afternoon' => 80,
            'wfh' => 70,
            'off' => 50,
            'event' => 10,
        ];

        foreach ($shifts as $s) {
            try {
                $start = Carbon::parse($s['start_time']);
                $end = Carbon::parse($s['end_time']);

                // Check overlap with Today
                if ($start->lte($todayEnd) && $end->gte($todayStart)) {
                    $todayShifts[] = $s;
                }

                // Check overlap with Tomorrow
                if ($start->lte($tomorrowEnd) && $end->gte($tomorrowStart)) {
                    $tomorrowShifts[] = $s;
                }

                // Upcoming (starts now or later, or ends in the future)
                if ($end->gte($now->copy()->subHours(4))) {
                    $upcoming[] = $s;
                }
            } catch (Throwable $e) {
                // Ignore parse errors on individual shifts
            }
        }

        // Sort shifts by priority to pick the main hero shift for display
        $sortFn = function ($a, $b) use ($priority) {
            $pA = $priority[$a['type'] ?? 'event'] ?? 0;
            $pB = $priority[$b['type'] ?? 'event'] ?? 0;
            return $pB <=> $pA;
        };

        usort($todayShifts, $sortFn);
        usort($tomorrowShifts, $sortFn);

        $todayShift = $todayShifts[0] ?? null;
        $tomorrowShift = $tomorrowShifts[0] ?? null;

        // Deduplicate and sort upcoming by start_time
        $seen = [];
        $uniqueUpcoming = [];
        foreach ($upcoming as $u) {
            $key = ($u['title'] ?? '') . '|' . ($u['start_time'] ?? '');
            if (! isset($seen[$key])) {
                $seen[$key] = true;
                $uniqueUpcoming[] = $u;
            }
        }

        usort($uniqueUpcoming, function ($a, $b) {
            return strcmp($a['start_time'] ?? '', $b['start_time'] ?? '');
        });

        // Limit upcoming to 20 items so all events throughout the month are visible
        $uniqueUpcoming = array_slice($uniqueUpcoming, 0, 20);

        return [
            'is_connected' => $isConnected,
            'last_synced' => $shifts['last_synced_at'] ?? null,
            'today_shift' => $todayShift,
            'today_shifts' => $todayShifts,
            'tomorrow_shift' => $tomorrowShift,
            'tomorrow_shifts' => $tomorrowShifts,
            'upcoming' => $uniqueUpcoming,
            'total_cached' => count($shifts['items'] ?? $shifts),
        ];
    }

    private function fetchUpcomingEvents(string $accessToken): array
    {
        try {
            // 1. Discover all relevant calendars from calendarList
            $calUrl = 'https://www.googleapis.com/calendar/v3/users/me/calendarList';
            $calRes = Http::withToken($accessToken)->get($calUrl);
            $targetCals = [];

            if ($calRes->successful()) {
                $cals = $calRes->json('items') ?: [];
                foreach ($cals as $c) {
                    $cid = $c['id'] ?? '';
                    $summary = mb_strtolower($c['summary'] ?? '');
                    $isPrimary = $c['primary'] ?? false;

                    // Match primary, shift schedules, CSOC, or work calendars
                    if ($isPrimary || str_contains($summary, 'กะ') || str_contains($summary, 'เวร') ||
                        str_contains($summary, 'csoc') || str_contains($summary, 'soc') ||
                        str_contains($summary, 'work') || str_contains($summary, 'shift') ||
                        str_contains($summary, 'ptt')) {
                        $targetCals[] = [
                            'id' => $cid,
                            'name' => $c['summary'] ?? 'Calendar',
                        ];
                    }
                }
            }

            if (empty($targetCals)) {
                $targetCals[] = ['id' => 'primary', 'name' => 'Primary Calendar'];
            }

            $timeMin = Carbon::now()->subDays(2)->startOfDay()->toRfc3339String();
            $timeMax = Carbon::now()->addDays(28)->endOfDay()->toRfc3339String();

            $allShifts = [];

            foreach ($targetCals as $cal) {
                $cid = $cal['id'];
                $cname = $cal['name'];

                $url = 'https://www.googleapis.com/calendar/v3/calendars/' . urlencode($cid) . '/events?' . http_build_query([
                    'timeMin' => $timeMin,
                    'timeMax' => $timeMax,
                    'singleEvents' => 'true',
                    'orderBy' => 'startTime',
                    'maxResults' => 100,
                ]);

                $res = Http::withToken($accessToken)->get($url);
                if (! $res->successful()) {
                    continue;
                }

                $items = $res->json('items') ?: [];
                foreach ($items as $item) {
                    $summary = trim($item['summary'] ?? 'ไม่ระบุชื่อ');
                    $start = $item['start']['dateTime'] ?? $item['start']['date'] ?? null;
                    $end = $item['end']['dateTime'] ?? $item['end']['date'] ?? null;

                    if (! $start) continue;

                    // Normalize date-only events to start at 00:00 and end at 23:59:59
                    $isAllDay = ! str_contains($start, 'T');
                    if ($isAllDay) {
                        $startDt = Carbon::parse($start)->startOfDay()->toIso8601String();
                        // Google end date for all day event is exclusive (+1 day)
                        $endDt = Carbon::parse($end)->subSecond()->toIso8601String();
                    } else {
                        $startDt = Carbon::parse($start)->toIso8601String();
                        $endDt = Carbon::parse($end)->toIso8601String();
                    }

                    $type = $this->classifyShiftType($summary, $startDt, $endDt);

                    $allShifts[] = [
                        'id' => $item['id'] ?? '',
                        'calendar_name' => $cname,
                        'title' => $summary,
                        'type' => $type['type'],
                        'label' => $type['label'],
                        'icon' => $type['icon'],
                        'badge_color' => $type['badge_color'],
                        'is_all_day' => $isAllDay,
                        'start_time' => $startDt,
                        'end_time' => $endDt,
                        'start_date' => substr($startDt, 0, 10),
                        'start_formatted' => $isAllDay ? 'ทั้งวัน' : Carbon::parse($startDt)->format('H:i'),
                        'end_formatted' => $isAllDay ? 'ทั้งวัน' : Carbon::parse($endDt)->format('H:i'),
                        'day_formatted' => Carbon::parse($startDt)->translatedFormat('D d M'),
                        'location' => $item['location'] ?? null,
                    ];
                }
            }

            return $allShifts;
        } catch (Throwable $e) {
            Log::error('Fetch Multi Calendar Events Error: ' . $e->getMessage());
            return [];
        }
    }

    private function classifyShiftType(string $summary, string $start, string $end): array
    {
        $lower = mb_strtolower($summary);
        $startHour = Carbon::parse($start)->hour;

        // WFH
        if (str_contains($lower, 'wfh') || str_contains($lower, 'work from home')) {
            return [
                'type' => 'wfh',
                'label' => 'WFH (ทำงานที่บ้าน)',
                'icon' => '💻',
                'badge_color' => 'bg-sky-500/20 text-sky-300 border-sky-500/30',
            ];
        }

        // Night Shift (กะดึก)
        if (str_contains($lower, 'ดึก') || str_contains($lower, 'night') || $startHour >= 20 || $startHour <= 2) {
            return [
                'type' => 'night',
                'label' => 'กะดึก (Night Shift)',
                'icon' => '🌙',
                'badge_color' => 'bg-purple-500/20 text-purple-300 border-purple-500/30',
            ];
        }

        // Morning Shift (กะเช้า / Day Shift)
        if (str_contains($lower, 'day') || str_contains($lower, 'เช้า') || str_contains($lower, 'morning') || ($startHour >= 6 && $startHour <= 10)) {
            return [
                'type' => 'morning',
                'label' => 'กะเช้า / Day Shift',
                'icon' => '☀️',
                'badge_color' => 'bg-amber-500/20 text-amber-300 border-amber-500/30',
            ];
        }

        // Afternoon Shift (กะบ่าย)
        if (str_contains($lower, 'บ่าย') || str_contains($lower, 'afternoon') || ($startHour >= 14 && $startHour <= 16)) {
            return [
                'type' => 'afternoon',
                'label' => 'กะบ่าย (Afternoon Shift)',
                'icon' => '⛅',
                'badge_color' => 'bg-blue-500/20 text-blue-300 border-blue-500/30',
            ];
        }

        // Off Day (วันหยุด)
        if (str_contains($lower, 'off') || str_contains($lower, 'หยุด') || str_contains($lower, 'พัก')) {
            return [
                'type' => 'off',
                'label' => 'วันหยุด (Off Day)',
                'icon' => '🏖️',
                'badge_color' => 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
            ];
        }

        return [
            'type' => 'event',
            'label' => 'ภารกิจทั่วไป',
            'icon' => '📌',
            'badge_color' => 'bg-slate-500/20 text-slate-300 border-slate-500/30',
        ];
    }

    private function getValidToken(): ?array
    {
        $token = $this->readToken();
        if (! $token || empty($token['access_token'])) {
            return null;
        }

        $expiresIn = $token['expires_in'] ?? 3600;
        $createdAt = $token['created_at'] ?? 0;

        if (time() > ($createdAt + $expiresIn - 60)) {
            if (empty($token['refresh_token'])) {
                return null;
            }

            $clientId = config('services.google_calendar.client_id');
            $clientSecret = config('services.google_calendar.client_secret');

            $res = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'refresh_token' => $token['refresh_token'],
                'grant_type' => 'refresh_token',
            ]);

            if ($res->successful()) {
                $refreshed = $res->json();
                $token['access_token'] = $refreshed['access_token'];
                $token['expires_in'] = $refreshed['expires_in'] ?? 3600;
                $token['created_at'] = time();
                $this->saveToken($token);
            } else {
                return null;
            }
        }

        return $token;
    }

    private function saveToken(array $token): void
    {
        $json = json_encode($token, JSON_PRETTY_PRINT);
        @file_put_contents(storage_path('app/' . self::$tokenFile), $json);
        @file_put_contents(storage_path('app/private/' . self::$tokenFile), $json);
    }

    private function readToken(): ?array
    {
        $paths = [
            storage_path('app/' . self::$tokenFile),
            storage_path('app/private/' . self::$tokenFile),
        ];

        foreach ($paths as $p) {
            if (file_exists($p)) {
                $content = @file_get_contents($p);
                if ($content) {
                    $decoded = json_decode($content, true);
                    if ($decoded) return $decoded;
                }
            }
        }

        return null;
    }

    private function saveShifts(array $shifts): void
    {
        $payload = [
            'last_synced_at' => Carbon::now()->toIso8601String(),
            'items' => $shifts,
        ];
        $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        @file_put_contents(storage_path('app/' . self::$shiftsFile), $json);
        @file_put_contents(storage_path('app/private/' . self::$shiftsFile), $json);
    }

    private function readShifts(): array
    {
        $paths = [
            storage_path('app/' . self::$shiftsFile),
            storage_path('app/private/' . self::$shiftsFile),
        ];

        foreach ($paths as $p) {
            if (file_exists($p)) {
                $content = @file_get_contents($p);
                if ($content) {
                    $decoded = json_decode($content, true);
                    if (is_array($decoded)) {
                        return $decoded['items'] ?? $decoded;
                    }
                }
            }
        }

        return [];
    }

    private function deleteFile(string $filename): void
    {
        @unlink(storage_path('app/' . $filename));
        @unlink(storage_path('app/private/' . $filename));
    }
}
