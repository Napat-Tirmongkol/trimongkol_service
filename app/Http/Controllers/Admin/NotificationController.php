<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use App\Services\AuditLog;
use App\Services\Notifications\DiscordNotifier;
use App\Services\Notifications\LineNotifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

/**
 * Platform-wide notification channels (LINE, Discord). Configured once here
 * and reusable by any product via App\Services\Notifications\Notifier.
 */
class NotificationController extends Controller
{
    public function edit()
    {
        return view('admin.notifications', [
            'lineEnabled' => setting('line.enabled') === '1',
            'lineTarget' => setting('line.target_id'),
            'lineTokenSet' => filled(setting('line.channel_token')) || filled(config('services.line.channel_token')),
            'discordWebhook' => setting('discord.webhook') ?: config('services.discord.webhook'),
        ]);
    }

    public function updateLine(Request $request)
    {
        $data = $request->validate([
            'line_enabled' => 'nullable|boolean',
            'line_target' => 'nullable|string|max:120',
            'line_token' => 'nullable|string|max:400',
        ]);

        Setting::updateOrCreate(['key' => 'line.enabled'], ['value' => $request->boolean('line_enabled') ? '1' : '0']);
        Setting::updateOrCreate(['key' => 'line.target_id'], ['value' => trim($data['line_target'] ?? '')]);

        if (filled($data['line_token'] ?? null)) {
            Setting::updateOrCreate(['key' => 'line.channel_token'], ['value' => Crypt::encryptString(trim($data['line_token']))]);
        }

        AuditLog::record('notifications.line.settings', null, 'LINE notify settings updated');

        return back()->with('status', __('app.admin.notifications.line_saved'));
    }

    public function testLine()
    {
        [$ok, $message] = LineNotifier::push(__('app.admin.notifications.line_test_message', [
            'app' => config('app.name'),
        ]));

        return $ok
            ? back()->with('status', __('app.admin.notifications.line_test_ok'))
            : back()->with('error', __('app.admin.notifications.line_test_fail', [
                'error' => self::translateError('line', $message),
            ]));
    }

    public function updateDiscord(Request $request)
    {
        $data = $request->validate([
            'discord_webhook' => 'nullable|string|max:300',
        ]);

        Setting::updateOrCreate(['key' => 'discord.webhook'], ['value' => trim($data['discord_webhook'] ?? '')]);

        AuditLog::record('notifications.discord.settings', null, 'Discord notify settings updated');

        return back()->with('status', __('app.admin.notifications.discord_saved'));
    }

    public function testDiscord()
    {
        [$ok, $message] = DiscordNotifier::send(__('app.admin.notifications.discord_test_message', [
            'app' => config('app.name'),
        ]));

        return $ok
            ? back()->with('status', __('app.admin.notifications.discord_test_ok'))
            : back()->with('error', __('app.admin.notifications.discord_test_fail', [
                'error' => self::translateError('discord', $message),
            ]));
    }

    /** Map a channel error token to its translated hint, falling back to raw. */
    private static function translateError(string $channel, string $token): string
    {
        $key = "app.admin.notifications.{$channel}_err_{$token}";
        $translated = __($key);

        return $translated !== $key ? $translated : $token;
    }

    /** Receive and process webhook events from LINE to capture User IDs. */
    public function handleLineWebhook(Request $request)
    {
        $events = $request->json('events', []);
        
        if (empty($events)) {
            return response()->json(['status' => 'no_events']);
        }

        $token = LineNotifier::token();
        $existing = json_decode(Setting::get('line.captured_users') ?: '[]', true);
        $maxItems = 20;

        foreach ($events as $event) {
            $source = $event['source'] ?? null;
            if (! $source) continue;

            $type = $source['type'] ?? 'user';
            $id = match ($type) {
                'group' => $source['groupId'] ?? null,
                'room' => $source['roomId'] ?? null,
                default => $source['userId'] ?? null,
            };

            if (! $id) continue;

            // Fetch name from LINE if token is configured and type is user
            $name = 'Unknown Name';
            if ($type === 'user' && $token) {
                try {
                    $profileRes = \Illuminate\Support\Facades\Http::timeout(5)
                        ->withToken($token)
                        ->get("https://api.line.me/v2/bot/profile/{$id}");
                    if ($profileRes->successful()) {
                        $name = $profileRes->json('displayName') ?? 'Unknown Name';
                    }
                } catch (\Throwable $e) {
                    \Illuminate\Support\Facades\Log::warning("Failed to fetch LINE profile: " . $e->getMessage());
                }
            } elseif ($type !== 'user') {
                $name = ucfirst($type) . ' Chat';
            }

            // Capture snippet of message text if available to help identify
            $msgText = '';
            if (($event['type'] ?? '') === 'message' && ($event['message']['type'] ?? '') === 'text') {
                $msgText = $event['message']['text'] ?? '';
            }

            // Check if this ID is already in the list
            $foundIndex = -1;
            foreach ($existing as $index => $item) {
                if ($item['id'] === $id) {
                    $foundIndex = $index;
                    break;
                }
            }

            $newItem = [
                'id' => $id,
                'type' => $type,
                'name' => $name,
                'snippet' => $msgText ? mb_substr($msgText, 0, 50) : null,
                'timestamp' => now()->toDateTimeString(),
            ];

            if ($foundIndex >= 0) {
                unset($existing[$foundIndex]);
            }
            array_unshift($existing, $newItem);
        }

        $existing = array_slice($existing, 0, $maxItems);
        Setting::updateOrCreate(['key' => 'line.captured_users'], ['value' => json_encode($existing)]);

        return response()->json(['status' => 'ok']);
    }

    /** Clear the history of captured User IDs. */
    public function clearLineCaptured()
    {
        Setting::updateOrCreate(['key' => 'line.captured_users'], ['value' => '[]']);
        return back()->with('status', 'ล้างประวัติการตรวจจับเรียบร้อยแล้ว');
    }
}
