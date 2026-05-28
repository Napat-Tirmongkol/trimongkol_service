<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RequireTwoFactor
{
    /**
     * Routes that an unauthenticated-for-2FA user is still allowed to hit
     * (so they can finish the challenge or sign out).
     */
    private const PASSTHROUGH_ROUTES = [
        'two-factor.challenge',
        'two-factor.verify',
        'logout',
        'admin.logout',
        'locale.switch',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->hasTwoFactorEnabled()) {
            return $next($request);
        }

        if ($request->session()->get('auth.two_factor_passed')) {
            return $next($request);
        }

        // The admin who initiated impersonation already passed 2FA;
        // don't ask them for the impersonated user's code.
        if ($request->session()->has('impersonator_id')) {
            return $next($request);
        }

        if ($request->routeIs(self::PASSTHROUGH_ROUTES)) {
            return $next($request);
        }

        return redirect()->route('two-factor.challenge');
    }
}
