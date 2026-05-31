<?php

namespace App\Providers;

use App\Models\LoginAttempt;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceMember;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
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

        // Record failed login attempts so owners can spot brute-force probes.
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

        // Every new signup gets a personal workspace they own, so they can
        // start keeping books immediately without any extra setup.
        Event::listen(Registered::class, function (Registered $event) {
            if (! $event->user instanceof User) {
                return;
            }
            if ($event->user->workspaces()->exists()) {
                return;
            }

            $base = Str::slug($event->user->name ?: explode('@', $event->user->email)[0]) ?: 'workspace';
            $slug = $base;
            $i = 1;
            while (Workspace::where('slug', $slug)->exists()) {
                $slug = $base.'-'.(++$i);
            }

            $workspace = Workspace::create([
                'name' => ($event->user->name ?: explode('@', $event->user->email)[0])."'s workspace",
                'slug' => $slug,
            ]);

            WorkspaceMember::create([
                'workspace_id' => $workspace->id,
                'user_id' => $event->user->id,
                'role' => 'owner',
                'joined_at' => now(),
            ]);
        });
    }
}
