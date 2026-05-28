<?php

namespace App\Providers;

use App\Models\LoginAttempt;
use App\Models\User;
use Illuminate\Auth\Events\Authenticated;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Guests trying to reach /admin/* go to the admin portal login,
        // not the regular user login.
        Authenticate::redirectUsing(function (Request $request) {
            return $request->is('admin', 'admin/*')
                ? route('admin.login')
                : route('login');
        });

        // Auto-promote users whose email is listed in ADMIN_EMAILS (comma-
        // or whitespace-separated). Lets the site owner bootstrap admin
        // access on a fresh deploy without SSH — just edit .env in Plesk.
        Event::listen(Authenticated::class, function (Authenticated $event) {
            $raw = (string) env('ADMIN_EMAILS', '');
            if ($raw === '') {
                return;
            }

            $user = $event->user;
            if (! $user instanceof User || $user->is_admin) {
                return;
            }

            $allowed = array_filter(array_map(
                fn ($e) => strtolower(trim($e)),
                preg_split('/[,\s]+/', $raw) ?: []
            ));

            if (in_array(strtolower((string) $user->email), $allowed, true)) {
                $user->is_admin = true;
                $user->save();
            }
        });

        // Stamp last_login_at + record a successful login attempt on each login.
        Event::listen(Login::class, function (Login $event) {
            if ($event->user instanceof User) {
                $event->user->forceFill(['last_login_at' => now()])->saveQuietly();
            }

            $request = request();
            LoginAttempt::create([
                'email' => $event->user?->email,
                'user_id' => $event->user?->id,
                'success' => true,
                'ip' => $request?->ip(),
                'user_agent' => substr((string) $request?->userAgent(), 0, 500),
            ]);
        });

        // Record failed login attempts so admins can see brute-force probes.
        Event::listen(Failed::class, function (Failed $event) {
            $request = request();
            LoginAttempt::create([
                'email' => $event->credentials['email'] ?? null,
                'user_id' => $event->user?->id,
                'success' => false,
                'ip' => $request?->ip(),
                'user_agent' => substr((string) $request?->userAgent(), 0, 500),
            ]);
        });
    }
}
