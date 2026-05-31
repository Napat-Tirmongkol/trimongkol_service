<?php

namespace App\Http\Middleware;

use App\Services\ProductGate;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Blocks a product's routes when the admin has switched it off, so a disabled
 * product can't be reached by typing the URL directly. Usage: middleware
 * 'product:accounting'. Returns 404 — the product simply "isn't there".
 */
class EnsureProductEnabled
{
    public function handle(Request $request, Closure $next, string $product): Response
    {
        abort_unless(ProductGate::enabled($product), 404);

        return $next($request);
    }
}
