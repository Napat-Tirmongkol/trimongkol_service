<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;

// Defensive load of helper functions: composer autoload-files can be
// stale in OPcache after deploys, so require directly here.
require_once __DIR__ . '/../app/helpers.php';

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            \App\Http\Middleware\SetLocale::class,
            \App\Http\Middleware\BlockSuspendedUser::class,
            \App\Http\Middleware\RequireTwoFactor::class,
            \App\Http\Middleware\MaintenanceMode::class,
            \App\Http\Middleware\EnsureUserSessionContext::class,
        ]);
        $middleware->alias([
            'admin' => \App\Http\Middleware\EnsureAdmin::class,
            'product' => \App\Http\Middleware\EnsureProductEnabled::class,
            'auth.accounting' => \App\Http\Middleware\EnsureAccountingAuth::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
