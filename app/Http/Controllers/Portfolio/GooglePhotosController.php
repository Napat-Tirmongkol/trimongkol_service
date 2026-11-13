<?php

namespace App\Http\Controllers\Portfolio;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

class GooglePhotosController extends Controller
{
    private string $tokenFile = 'google_photos_token.json';
    private string $latestFile = 'google_photos_latest.json';

    public function connect(): RedirectResponse
    {
        $clientId = config('services.google_photos.client_id');
        $redirectUri = config('services.google_photos.redirect');

        if (! $clientId) {
            return redirect()->route('portfolio.journey.index')
                ->with('error', 'ยังไม่ได้ระบุ GOOGLE_PHOTOS_CLIENT_ID ในระบบ');
        }

        $params = [
            'client_id' => $clientId,
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => 'https://www.googleapis.com/auth/photoslibrary.readonly',
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
            return redirect()->route('portfolio.journey.index')
                ->with('error', 'การเชื่อมต่อ Google Photos ถูกยกเลิก: ' . $request->query('error'));
        }

        $code = $request->query('code');
        if (! $code) {
            return redirect()->route('portfolio.journey.index')
                ->with('error', 'ไม่พบ Authorization Code จาก Google');
        }

        $clientId = config('services.google_photos.client_id');
        $clientSecret = config('services.google_photos.client_secret');
        $redirectUri = config('services.google_photos.redirect');

        try {
            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'code' => $code,
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'redirect_uri' => $redirectUri,
                'grant_type' => 'authorization_code',
            ]);

            if (! $response->successful()) {
                Log::error('Google Photos Token Error', ['body' => $response->body()]);
                return redirect()->route('portfolio.journey.index')
                    ->with('error', 'แลกเปลี่ยน Token ไม่สำเร็จ: ' . ($response->json('error_description') ?: $response->body()));
            }

            $tokenData = $response->json();
            $tokenData['created_at'] = time();

            $this->saveToken($tokenData);

            // Immediately test pull 2 latest photos
            $photos = $this->fetchMediaItems($tokenData['access_token'], 2);
            if (! empty($photos)) {
                $this->saveLatestPhotos($photos);
            }

            return redirect()->route('portfolio.journey.index')
                ->with('status', 'เชื่อมต่อ Google Photos สำเร็จ! ดึงรูปภาพล่าสุด ' . count($photos) . ' ภาพเรียบร้อยแล้ว');
        } catch (Throwable $e) {
            Log::error('Google Photos Callback Exception', ['message' => $e->getMessage()]);
            return redirect()->route('portfolio.journey.index')
                ->with('error', 'เกิดข้อผิดพลาดในการเชื่อมต่อ: ' . $e->getMessage());
        }
    }

    public function getLatestJson(): JsonResponse
    {
        $token = $this->getValidToken();
        if (! $token) {
            return response()->json([
                'connected' => false,
                'photos' => $this->getStoredLatestPhotos(),
            ]);
        }

        $photos = $this->fetchMediaItems($token['access_token'], 2);
        if (! empty($photos)) {
            $this->saveLatestPhotos($photos);
        } else {
            $photos = $this->getStoredLatestPhotos();
        }

        return response()->json([
            'connected' => true,
            'photos' => $photos,
        ]);
    }

    public function disconnect(): RedirectResponse
    {
        $this->deleteFile($this->tokenFile);
        $this->deleteFile($this->latestFile);

        return redirect()->route('portfolio.journey.index')
            ->with('status', 'ยกเลิกการเชื่อมต่อ Google Photos เรียบร้อยแล้ว');
    }

    private function fetchMediaItems(string $accessToken, int $pageSize = 2): array
    {
        try {
            $res = Http::withToken($accessToken)
                ->get('https://photoslibrary.googleapis.com/v1/mediaItems', [
                    'pageSize' => $pageSize,
                ]);

            if (! $res->successful()) {
                Log::warning('Google Photos List Error: ' . $res->body());
                return [];
            }

            $items = $res->json('mediaItems') ?: [];
            $photos = [];

            foreach ($items as $item) {
                $meta = $item['mediaMetadata'] ?? [];
                $photoMeta = $meta['photo'] ?? [];

                $photos[] = [
                    'id' => $item['id'] ?? '',
                    'filename' => $item['filename'] ?? 'photo.jpg',
                    'baseUrl' => $item['baseUrl'] ?? '',
                    'thumbnailUrl' => ($item['baseUrl'] ?? '') . '=w600-h600',
                    'productUrl' => $item['productUrl'] ?? '',
                    'mimeType' => $item['mimeType'] ?? 'image/jpeg',
                    'creationTime' => $meta['creationTime'] ?? null,
                    'cameraMake' => $photoMeta['cameraMake'] ?? null,
                    'cameraModel' => $photoMeta['cameraModel'] ?? null,
                ];
            }

            return $photos;
        } catch (Throwable $e) {
            Log::error('Fetch Media Items Error: ' . $e->getMessage());
            return [];
        }
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
            // Need refresh
            if (empty($token['refresh_token'])) {
                return null;
            }

            $clientId = config('services.google_photos.client_id');
            $clientSecret = config('services.google_photos.client_secret');

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
        @file_put_contents(storage_path('app/' . $this->tokenFile), $json);
        @file_put_contents(storage_path('app/private/' . $this->tokenFile), $json);
    }

    private function readToken(): ?array
    {
        $paths = [
            storage_path('app/' . $this->tokenFile),
            storage_path('app/private/' . $this->tokenFile),
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

    private function saveLatestPhotos(array $photos): void
    {
        $json = json_encode($photos, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        @file_put_contents(storage_path('app/' . $this->latestFile), $json);
        @file_put_contents(storage_path('app/private/' . $this->latestFile), $json);
    }

    private function getStoredLatestPhotos(): array
    {
        $paths = [
            storage_path('app/' . $this->latestFile),
            storage_path('app/private/' . $this->latestFile),
        ];

        foreach ($paths as $p) {
            if (file_exists($p)) {
                $content = @file_get_contents($p);
                if ($content) {
                    $decoded = json_decode($content, true);
                    if (is_array($decoded)) return $decoded;
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
