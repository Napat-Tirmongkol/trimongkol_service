<?php

namespace App\Http\Controllers\Portfolio;

use App\Http\Controllers\Controller;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use ZipArchive;

class JourneyController extends Controller
{
    private string $storageFile = 'portfolio_journey.json';
    private string $regionsFile = 'portfolio_journey_regions.json';
    private string $autoRegionsCacheFile = 'portfolio_journey_auto_regions.json';

    public function index(): View
    {
        $journeyData = $this->loadJourneyData();

        // Check Google Photos connection status & latest test photos
        $tokenPath = storage_path('app/google_photos_token.json');
        $privateTokenPath = storage_path('app/private/google_photos_token.json');
        $hasToken = file_exists($tokenPath) || file_exists($privateTokenPath);

        $latestPhotosPath = storage_path('app/google_photos_latest.json');
        $privateLatestPhotosPath = storage_path('app/private/google_photos_latest.json');
        $photos = [];
        if (file_exists($latestPhotosPath)) {
            $photos = json_decode(@file_get_contents($latestPhotosPath), true) ?: [];
        } elseif (file_exists($privateLatestPhotosPath)) {
            $photos = json_decode(@file_get_contents($privateLatestPhotosPath), true) ?: [];
        }

        $journeyData['google_photos'] = [
            'connected' => $hasToken,
            'photos' => $photos,
        ];

        $journeyData['thailand_regions'] = $this->buildThailandRegionsPayload($journeyData);

        return view('portfolio.journey', compact('journeyData'));
    }

    public function toggleRegion(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'type'         => 'required|in:province,amphoe,color',
            'code'         => 'nullable|string|max:16',
            'is_auto'      => 'nullable|boolean',
            'color_scheme' => 'nullable|in:emerald,amber,sky,rose,violet',
        ]);

        $manual = $this->loadJsonStorage($this->regionsFile, [
            'manual_provinces' => [],
            'manual_amphoes'   => [],
            'hidden_provinces' => [],
            'hidden_amphoes'   => [],
            'color_scheme'     => 'emerald',
        ]);

        if ($validated['type'] === 'color') {
            $manual['color_scheme'] = $validated['color_scheme'] ?? 'emerald';
            $this->saveJsonStorage($this->regionsFile, $manual);
            return response()->json(['ok' => true, 'regions' => $manual]);
        }

        $code = trim((string) ($validated['code'] ?? ''));
        if ($code === '') {
            return response()->json(['ok' => false, 'message' => 'Missing region code'], 422);
        }

        $mKey = $validated['type'] === 'province' ? 'manual_provinces' : 'manual_amphoes';
        $hKey = $validated['type'] === 'province' ? 'hidden_provinces' : 'hidden_amphoes';
        $isAuto = (bool) ($validated['is_auto'] ?? false);

        $manualList = array_values(array_map('strval', $manual[$mKey] ?? []));
        $hiddenList = array_values(array_map('strval', $manual[$hKey] ?? []));

        if ($isAuto) {
            if (in_array($code, $hiddenList, true)) {
                $hiddenList = array_values(array_diff($hiddenList, [$code]));
            } else {
                $hiddenList[] = $code;
                $manualList = array_values(array_diff($manualList, [$code]));
            }
        } else {
            if (in_array($code, $manualList, true)) {
                $manualList = array_values(array_diff($manualList, [$code]));
            } else {
                $manualList[] = $code;
                $hiddenList = array_values(array_diff($hiddenList, [$code]));
            }
        }

        $manual[$mKey] = array_values(array_unique($manualList));
        $manual[$hKey] = array_values(array_unique($hiddenList));
        $this->saveJsonStorage($this->regionsFile, $manual);

        return response()->json(['ok' => true, 'regions' => $manual]);
    }

    public function upload(Request $request): RedirectResponse
    {
        $request->validate([
            'takeout_file' => 'required|file|max:51200', // max 50MB
        ]);

        $file = $request->file('takeout_file');
        $ext = strtolower($file->getClientOriginalExtension());
        $jsonContent = null;

        if ($ext === 'json') {
            $jsonContent = file_get_contents($file->getRealPath());
        } elseif ($ext === 'zip') {
            $zip = new ZipArchive();
            if ($zip->open($file->getRealPath()) === true) {
                // Find Timeline Edits.json or Timeline.json or Records.json
                for ($i = 0; $i < $zip->numFiles; $i++) {
                    $entryName = $zip->getNameIndex($i);
                    if (str_contains($entryName, 'Timeline Edits.json') ||
                        str_contains($entryName, 'Timeline.json') ||
                        str_contains($entryName, 'Records.json') ||
                        str_contains($entryName, 'location-history.json')) {
                        $jsonContent = $zip->getFromIndex($i);
                        break;
                    }
                }
                $zip->close();
            }
        }

        if (!$jsonContent) {
            return back()->with('error', 'ไม่พบไฟล์ประวัติพิกัดไทม์ไลน์ (.json) ภายในไฟล์ที่อัปโหลด');
        }

        $decoded = json_decode($jsonContent, true);
        if (!$decoded) {
            return back()->with('error', 'โครงสร้างไฟล์ JSON ไม่ถูกต้อง');
        }

        $processed = $this->processRawTakeoutData($decoded);
        $encoded = json_encode($processed, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        Storage::put($this->storageFile, $encoded);
        @file_put_contents(storage_path('app/' . $this->storageFile), $encoded);
        @file_put_contents(storage_path('app/private/' . $this->storageFile), $encoded);

        // Invalidate cached Thailand region auto-detection so next view recalculates
        Storage::delete($this->autoRegionsCacheFile);
        @unlink(storage_path('app/' . $this->autoRegionsCacheFile));
        @unlink(storage_path('app/private/' . $this->autoRegionsCacheFile));

        return redirect()->route('portfolio.journey.index')
            ->with('status', 'นำเข้าและประมวลผลพิกัดไทม์ไลน์ ' . number_format($processed['summary']['total_points']) . ' จุดเรียบร้อยแล้ว!');
    }

    private function loadJourneyData(): array
    {
        $possiblePaths = [
            storage_path('app/' . $this->storageFile),
            storage_path('app/private/' . $this->storageFile),
            base_path('storage/app/' . $this->storageFile),
            base_path('storage/app/private/' . $this->storageFile),
        ];

        foreach ($possiblePaths as $filePath) {
            if (file_exists($filePath)) {
                $json = @file_get_contents($filePath);
                if ($json) {
                    $data = json_decode($json, true);
                    if ($data && !empty($data['trips'])) {
                        return $data;
                    }
                }
            }
        }

        if (Storage::exists($this->storageFile)) {
            $json = Storage::get($this->storageFile);
            $data = json_decode($json, true);
            if ($data && !empty($data['trips'])) return $data;
        }

        return [
            'summary' => [
                'total_points'      => 0,
                'total_trips'       => 0,
                'total_distance_km' => 0.0,
                'date_range'        => ['start' => null, 'end' => null],
                'places_count'      => 0,
            ],
            'places'            => [],
            'semantic_segments' => [],
            'trips'             => [],
        ];
    }

    private function processRawTakeoutData(array $data): array
    {
        $points = [];
        $semanticSegments = [];
        $places = [];

        // Scenario 1: Modern Timeline Edits (from Google Takeout post-2024)
        if (isset($data['timelineEdits']) && is_array($data['timelineEdits'])) {
            foreach ($data['timelineEdits'] as $e) {
                $raw = $e['rawSignal'] ?? [];
                $sig = $raw['signal'] ?? [];
                $pos = $sig['position'] ?? [];
                if (!empty($pos['timestamp']) && !empty($pos['point'])) {
                    $lat = ($pos['point']['latE7'] ?? 0) / 1e7;
                    $lng = ($pos['point']['lngE7'] ?? 0) / 1e7;
                    if ($lat > 0 && $lng > 0) {
                        $points[] = [
                            'time'       => $pos['timestamp'],
                            'lat'        => round($lat, 6),
                            'lng'        => round($lng, 6),
                            'speed_kmh'  => round(($pos['speedMetersPerSecond'] ?? 0) * 3.6, 1),
                            'accuracy_m' => round(($pos['accuracyMm'] ?? 0) / 1000.0, 1),
                        ];
                    }
                }

                $seg = $e['inferredSemanticSegment'] ?? [];
                if (!empty($seg['segment'])) {
                    $sTime = $seg['startTime'] ?? null;
                    $eTime = $seg['endTime'] ?? null;
                    $sub = $seg['segment'];
                    if (isset($sub['visit'])) {
                        $cand = $sub['visit']['topCandidate'] ?? [];
                        $pId = $cand['placeId'] ?? 'unknown';
                        $sType = $cand['semanticType'] ?? 'UNKNOWN';
                        $loc = $cand['placeLocation'] ?? [];
                        $lat = ($loc['latE7'] ?? 0) / 1e7;
                        $lng = ($loc['lngE7'] ?? 0) / 1e7;
                        if ($lat > 0 && $lng > 0) {
                            $semanticSegments[] = [
                                'type'          => 'visit',
                                'semantic_type' => $sType,
                                'start_time'    => $sTime,
                                'end_time'      => $eTime,
                                'lat'           => round($lat, 6),
                                'lng'           => round($lng, 6),
                                'place_id'      => $pId,
                            ];
                            if (!isset($places[$pId])) {
                                $places[$pId] = [
                                    'semantic_type' => $sType,
                                    'lat'           => round($lat, 6),
                                    'lng'           => round($lng, 6),
                                    'visits'        => 1,
                                ];
                            } else {
                                $places[$pId]['visits']++;
                            }
                        }
                    } elseif (isset($sub['activity'])) {
                        $act = $sub['activity'];
                        $cand = $act['topCandidate'] ?? [];
                        $actType = $cand['type'] ?? 'TRAVEL';
                        $dist = $act['distanceMeters'] ?? 0;
                        $semanticSegments[] = [
                            'type'          => 'activity',
                            'activity_type' => $actType,
                            'distance_km'   => round($dist / 1000.0, 2),
                            'start_time'    => $sTime,
                            'end_time'      => $eTime,
                        ];
                    }
                }
            }
        }
        // Scenario 2: Legacy Records / Location History
        elseif (isset($data['locations']) && is_array($data['locations'])) {
            foreach ($data['locations'] as $loc) {
                $lat = ($loc['latitudeE7'] ?? 0) / 1e7;
                $lng = ($loc['longitudeE7'] ?? 0) / 1e7;
                if ($lat > 0 && $lng > 0) {
                    $points[] = [
                        'time'       => $loc['timestamp'] ?? Carbon::now()->toIso8601String(),
                        'lat'        => round($lat, 6),
                        'lng'        => round($lng, 6),
                        'speed_kmh'  => round(($loc['velocity'] ?? 0) * 3.6, 1),
                        'accuracy_m' => round($loc['accuracy'] ?? 0, 1),
                    ];
                }
            }
        }

        usort($points, fn ($a, $b) => strcmp($a['time'], $b['time']));

        // Cluster into trips (break > 25 mins)
        $trips = [];
        $currentTrip = [];
        foreach ($points as $p) {
            $t = Carbon::parse($p['time'])->timestamp;
            if (empty($currentTrip)) {
                $currentTrip[] = [$p, $t];
            } else {
                $last = end($currentTrip);
                $dt = $t - $last[1];
                if ($dt > 1500) {
                    $trips[] = $currentTrip;
                    $currentTrip = [[$p, $t]];
                } else {
                    $currentTrip[] = [$p, $t];
                }
            }
        }
        if (!empty($currentTrip)) {
            $trips[] = $currentTrip;
        }

        $processedTrips = [];
        $totalDistance = 0.0;
        $tripId = 1;

        foreach ($trips as $tr) {
            $pts = array_column($tr, 0);
            $dist = 0.0;
            $count = count($pts);
            for ($i = 1; $i < $count; $i++) {
                $d = $this->haversine($pts[$i - 1]['lat'], $pts[$i - 1]['lng'], $pts[$i]['lat'], $pts[$i]['lng']);
                if ($d < 50) {
                    $dist += $d;
                }
            }
            $totalDistance += $dist;

            $speeds = array_filter(array_column($pts, 'speed_kmh'), fn ($s) => $s > 0);
            $maxSpeed = !empty($speeds) ? max($speeds) : 0.0;
            $avgSpeed = !empty($speeds) ? round(array_sum($speeds) / count($speeds), 1) : 0.0;

            if ($maxSpeed > 35) {
                $mode = $maxSpeed < 90 ? 'motorcycle' : 'vehicle';
            } elseif ($maxSpeed > 15) {
                $mode = 'motorcycle';
            } else {
                $mode = 'walking';
            }

            $step = $count > 600 ? 2 : 1;
            $renderedPts = [];
            for ($i = 0; $i < $count; $i += $step) {
                $renderedPts[] = [$pts[$i]['lat'], $pts[$i]['lng'], $pts[$i]['speed_kmh'], $pts[$i]['time']];
            }
            if ($renderedPts[count($renderedPts) - 1] !== [$pts[$count - 1]['lat'], $pts[$count - 1]['lng'], $pts[$count - 1]['speed_kmh'], $pts[$count - 1]['time']]) {
                $renderedPts[] = [$pts[$count - 1]['lat'], $pts[$count - 1]['lng'], $pts[$count - 1]['speed_kmh'], $pts[$count - 1]['time']];
            }

            $startTime = $pts[0]['time'];
            $endTime = $pts[$count - 1]['time'];

            $processedTrips[] = [
                'id'            => $tripId,
                'title'         => "ทริปที่ {$tripId}: " . substr($startTime, 0, 10),
                'date'          => substr($startTime, 0, 10),
                'start_time'    => $startTime,
                'end_time'      => $endTime,
                'distance_km'   => round($dist, 2),
                'max_speed_kmh' => $maxSpeed,
                'avg_speed_kmh' => $avgSpeed,
                'mode'          => $mode,
                'points_count'  => $count,
                'path'          => $renderedPts,
            ];
            $tripId++;
        }

        return [
            'summary' => [
                'total_points'      => count($points),
                'total_trips'       => count($processedTrips),
                'total_distance_km' => round($totalDistance, 2),
                'date_range'        => [
                    'start' => !empty($points) ? substr($points[0]['time'], 0, 10) : null,
                    'end'   => !empty($points) ? substr($points[count($points) - 1]['time'], 0, 10) : null,
                ],
                'places_count'      => count($places),
            ],
            'places'            => array_values($places),
            'semantic_segments' => $semanticSegments,
            'trips'             => $processedTrips,
        ];
    }

    private function haversine(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat / 2) * sin($dLat / 2) +
            cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
            sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
        return $earthRadius * $c;
    }

    private function buildThailandRegionsPayload(array $journeyData): array
    {
        $summary = $journeyData['summary'] ?? [];
        $signature = md5(json_encode([
            $summary['total_points'] ?? 0,
            $summary['total_trips'] ?? 0,
            $summary['total_lifetime_trips'] ?? 0,
            $summary['date_range'] ?? [],
        ]));

        $cached = $this->loadJsonStorage($this->autoRegionsCacheFile, []);
        if (($cached['signature'] ?? null) !== $signature || empty($cached['province_catalog'])) {
            $cached = $this->detectAutoVisitedRegions($journeyData, $signature);
            if (!empty($cached['province_catalog'])) {
                $this->saveJsonStorage($this->autoRegionsCacheFile, $cached);
            }
        }

        $manual = $this->loadJsonStorage($this->regionsFile, [
            'manual_provinces' => [],
            'manual_amphoes'   => [],
            'hidden_provinces' => [],
            'hidden_amphoes'   => [],
            'color_scheme'     => 'emerald',
        ]);

        return [
            'auto_provinces'   => $cached['auto_provinces'] ?? [],
            'auto_amphoes'     => $cached['auto_amphoes'] ?? [],
            'total_provinces'  => count($cached['province_catalog'] ?? []) ?: 77,
            'total_amphoes'    => (int) ($cached['total_amphoes'] ?? 928),
            'province_catalog' => $cached['province_catalog'] ?? [],
            'manual_provinces' => array_values(array_map('strval', $manual['manual_provinces'] ?? [])),
            'manual_amphoes'   => array_values(array_map('strval', $manual['manual_amphoes'] ?? [])),
            'hidden_provinces' => array_values(array_map('strval', $manual['hidden_provinces'] ?? [])),
            'hidden_amphoes'   => array_values(array_map('strval', $manual['hidden_amphoes'] ?? [])),
            'color_scheme'     => $manual['color_scheme'] ?? 'emerald',
        ];
    }

    private function detectAutoVisitedRegions(array $journeyData, string $signature): array
    {
        $geoPath = public_path('thailand_amphoes.geojson');
        if (!file_exists($geoPath)) {
            return [
                'signature'        => $signature,
                'auto_provinces'   => [],
                'auto_amphoes'     => [],
                'total_amphoes'    => 928,
                'province_catalog' => [],
            ];
        }

        $geo = json_decode(@file_get_contents($geoPath), true);
        $features = $geo['features'] ?? [];
        if (empty($features)) {
            return [
                'signature'        => $signature,
                'auto_provinces'   => [],
                'auto_amphoes'     => [],
                'total_amphoes'    => 928,
                'province_catalog' => [],
            ];
        }

        // Deduplicate points to a ~110m grid to keep PHP PIP sub-200ms
        $uniq = [];
        $pushPt = static function ($lat, $lng, ?string $date) use (&$uniq): void {
            $lat = (float) $lat;
            $lng = (float) $lng;
            // Rough Thailand bounds filter
            if ($lat < 5.0 || $lat > 21.0 || $lng < 97.0 || $lng > 106.5) {
                return;
            }
            $key = round($lat, 3) . ',' . round($lng, 3);
            if (!isset($uniq[$key])) {
                $uniq[$key] = [$lng, $lat, 1, $date];
            } else {
                $uniq[$key][2]++;
                if ($date && (!$uniq[$key][3] || $date > $uniq[$key][3])) {
                    $uniq[$key][3] = $date;
                }
            }
        };

        foreach ($journeyData['places'] ?? [] as $p) {
            if (isset($p['lat'], $p['lng'])) {
                $pushPt($p['lat'], $p['lng'], null);
            }
        }
        foreach ($journeyData['lifetime_trips'] ?? [] as $lt) {
            $d = $lt['t'] ?? null;
            if (isset($lt['s'][0], $lt['s'][1])) {
                $pushPt($lt['s'][0], $lt['s'][1], $d);
            }
            if (isset($lt['e'][0], $lt['e'][1])) {
                $pushPt($lt['e'][0], $lt['e'][1], $d);
            }
        }
        foreach ($journeyData['trips'] ?? [] as $tr) {
            $d = $tr['date'] ?? null;
            foreach ($tr['path'] ?? [] as $pt) {
                if (isset($pt[0], $pt[1])) {
                    $pushPt($pt[0], $pt[1], $d);
                }
            }
        }

        $boxes = [];
        $ampCountByPro = [];
        foreach ($features as $f) {
            $props = $f['properties'] ?? [];
            $pc = (string) ($props['pro_code'] ?? '');
            $ampCountByPro[$pc] = ($ampCountByPro[$pc] ?? 0) + 1;

            $g = $f['geometry'] ?? [];
            $polys = ($g['type'] ?? '') === 'Polygon' ? [$g['coordinates'] ?? []] : ($g['coordinates'] ?? []);
            $minx = 999.0;
            $miny = 999.0;
            $maxx = -999.0;
            $maxy = -999.0;
            foreach ($polys as $poly) {
                foreach ($poly[0] ?? [] as $c) {
                    if ($c[0] < $minx) $minx = $c[0];
                    if ($c[0] > $maxx) $maxx = $c[0];
                    if ($c[1] < $miny) $miny = $c[1];
                    if ($c[1] > $maxy) $maxy = $c[1];
                }
            }
            $boxes[] = [$minx, $miny, $maxx, $maxy, $props, $polys];
        }

        $autoPro = [];
        $autoAmp = [];

        foreach ($uniq as [$x, $y, $cnt, $date]) {
            foreach ($boxes as $b) {
                if ($x >= $b[0] && $x <= $b[2] && $y >= $b[1] && $y <= $b[3]) {
                    $hit = false;
                    foreach ($b[5] as $poly) {
                        if (!empty($poly[0]) && $this->pointInRing($x, $y, $poly[0])) {
                            $inHole = false;
                            $polyCount = count($poly);
                            for ($h = 1; $h < $polyCount; $h++) {
                                if ($this->pointInRing($x, $y, $poly[$h])) {
                                    $inHole = true;
                                    break;
                                }
                            }
                            if (!$inHole) {
                                $hit = true;
                                break;
                            }
                        }
                    }
                    if ($hit) {
                        $ac = (string) ($b[4]['amp_code'] ?? '');
                        $pc = (string) ($b[4]['pro_code'] ?? '');
                        if ($ac !== '') {
                            if (!isset($autoAmp[$ac])) {
                                $autoAmp[$ac] = ['count' => 0, 'last_date' => $date];
                            }
                            $autoAmp[$ac]['count'] += $cnt;
                            if ($date && (!$autoAmp[$ac]['last_date'] || $date > $autoAmp[$ac]['last_date'])) {
                                $autoAmp[$ac]['last_date'] = $date;
                            }
                        }
                        if ($pc !== '') {
                            if (!isset($autoPro[$pc])) {
                                $autoPro[$pc] = ['count' => 0, 'last_date' => $date];
                            }
                            $autoPro[$pc]['count'] += $cnt;
                            if ($date && (!$autoPro[$pc]['last_date'] || $date > $autoPro[$pc]['last_date'])) {
                                $autoPro[$pc]['last_date'] = $date;
                            }
                        }
                        break;
                    }
                }
            }
        }

        // Build compact 77-province catalog from thailand_provinces.geojson
        $provinceCatalog = [];
        $proGeoPath = public_path('thailand_provinces.geojson');
        if (file_exists($proGeoPath)) {
            $proGeo = json_decode(@file_get_contents($proGeoPath), true);
            foreach ($proGeo['features'] ?? [] as $pf) {
                $pr = $pf['properties'] ?? [];
                $code = (string) ($pr['pro_code'] ?? '');
                if ($code === '') {
                    continue;
                }
                $provinceCatalog[] = [
                    'code'      => $code,
                    'th'        => (string) ($pr['pro_th'] ?? ''),
                    'en'        => (string) ($pr['pro_en'] ?? ''),
                    'reg'       => (string) ($pr['reg_royin'] ?? ''),
                    'lat'       => (float) ($pr['lat'] ?? 13.75),
                    'lon'       => (float) ($pr['lon'] ?? 100.5),
                    'amp_total' => (int) ($ampCountByPro[$code] ?? 0),
                ];
            }
            usort($provinceCatalog, fn ($a, $b) => strcmp($a['th'], $b['th']));
        }

        return [
            'signature'        => $signature,
            'auto_provinces'   => $autoPro,
            'auto_amphoes'     => $autoAmp,
            'total_amphoes'    => count($features),
            'province_catalog' => $provinceCatalog,
        ];
    }

    private function pointInRing(float $x, float $y, array $ring): bool
    {
        $inside = false;
        $n = count($ring);
        $j = $n - 1;
        for ($i = 0; $i < $n; $i++) {
            $xi = $ring[$i][0];
            $yi = $ring[$i][1];
            $xj = $ring[$j][0];
            $yj = $ring[$j][1];
            $den = ($yj - $yi);
            if ($den == 0.0) {
                $den = 1e-12;
            }
            if ((($yi > $y) !== ($yj > $y)) && ($x < ($xj - $xi) * ($y - $yi) / $den + $xi)) {
                $inside = !$inside;
            }
            $j = $i;
        }
        return $inside;
    }

    private function loadJsonStorage(string $filename, array $default): array
    {
        $possiblePaths = [
            storage_path('app/' . $filename),
            storage_path('app/private/' . $filename),
            base_path('storage/app/' . $filename),
            base_path('storage/app/private/' . $filename),
        ];

        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $decoded = json_decode(@file_get_contents($path), true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }

        if (Storage::exists($filename)) {
            $decoded = json_decode(Storage::get($filename), true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return $default;
    }

    private function saveJsonStorage(string $filename, array $payload): void
    {
        $encoded = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        Storage::put($filename, $encoded);

        foreach ([storage_path('app/' . $filename), storage_path('app/private/' . $filename)] as $path) {
            $dir = dirname($path);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            @file_put_contents($path, $encoded, LOCK_EX);
        }
    }
}
