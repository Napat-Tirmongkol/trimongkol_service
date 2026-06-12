<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserSessionContext
{
    public function handle(Request $request, Closure $next): Response
    {
        if (auth()->check()) {
            // Default to user context if not set or if currently impersonating a user
            if (! session()->has('auth_context') || session()->has('impersonator_id')) {
                session(['auth_context' => 'user']);
            }

            // Admin staff hitting an /admin/* URL while their session context
            // is still 'user' (typical right after a Google sign-in to the
            // portfolio) get silently promoted instead of bouncing off
            // EnsureAdmin's 403 — same person, same login, just a different
            // section of the site.
            if (session('auth_context') !== 'admin'
                && ($request->is('admin') || $request->is('admin/*'))
                && (bool) auth()->user()->is_admin
            ) {
                session(['auth_context' => 'admin']);
            }

            // If logged in as admin, prevent accessing user portal routes.
            // The portfolio area lives outside admin but is gated by its own
            // email allowlist, so admins can visit /portfolio/* without
            // losing their admin context.
            if (session('auth_context') === 'admin') {
                if (! $request->is('admin') &&
                    ! $request->is('admin/*') &&
                    ! $request->is('portfolio') &&
                    ! $request->is('portfolio/*') &&
                    ! $request->is('logout') &&
                    ! $request->is('impersonate/stop') &&
                    ! $request->is('locale/*')
                ) {
                    return redirect()->route('admin.dashboard');
                }
            }
        }

        return $next($request);
    }
}
