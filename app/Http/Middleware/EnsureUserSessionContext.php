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

            // If logged in as admin, prevent accessing user portal routes
            if (session('auth_context') === 'admin') {
                if (! $request->is('admin') &&
                    ! $request->is('admin/*') &&
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
