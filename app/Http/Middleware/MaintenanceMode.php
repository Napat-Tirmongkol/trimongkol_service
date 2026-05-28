<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Setting::get('maintenance.enabled') !== '1') {
            return $next($request);
        }

        // Always let admins, the admin login flow, and the health check through.
        if ($request->user()?->is_admin) {
            return $next($request);
        }

        if ($request->is('admin', 'admin/*', 'up', 'locale/*')) {
            return $next($request);
        }

        $message = Setting::get('maintenance.message', __('app.admin.maintenance_default'));

        return response()->view('errors.maintenance', ['message' => $message], 503);
    }
}
