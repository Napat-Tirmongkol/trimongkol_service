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
}
