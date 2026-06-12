<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gate for /portfolio/* — the personal-net-worth area. Two checks, in order:
 *  1. user is signed in (else bounce to the portfolio login page)
 *  2. user's email is in `config('portfolio.allowed_emails')` (else 403)
 *
 * The email allowlist is the real authorisation layer; Google OAuth only
 * proves the email was verified, not that this person is allowed in.
 */
class EnsurePortfolioAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('portfolio.login'));
        }

        $allowed = array_map('strtolower', (array) config('portfolio.allowed_emails', []));

        abort_unless(
            in_array(strtolower((string) $user->email), $allowed, true),
            403,
            'This account is not authorised for the portfolio.',
        );

        return $next($request);
    }
}
