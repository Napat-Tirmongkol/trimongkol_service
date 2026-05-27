<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->session()->get('locale', config('app.locale', 'th'));

        if (! in_array($locale, config('site.locales'), true)) {
            $locale = 'th';
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
