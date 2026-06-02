<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccountingAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! auth('accounting')->check()) {
            return redirect()->route('accounting.login')
                ->with('intended', $request->url());
        }

        $user = auth('accounting')->user();

        if (! $user->is_active) {
            auth('accounting')->logout();
            $request->session()->invalidate();

            return redirect()->route('accounting.login')
                ->withErrors(['email' => __('app.accounting.account_inactive')]);
        }

        return $next($request);
    }
}
