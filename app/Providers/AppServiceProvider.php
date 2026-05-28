<?php

namespace App\Providers;

use Illuminate\Auth\Middleware\Authenticate;
use Illuminate\Http\Request;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Guests trying to reach /admin/* go to the admin portal login,
        // not the regular user login.
        Authenticate::redirectUsing(function (Request $request) {
            return $request->is('admin', 'admin/*')
                ? route('admin.login')
                : route('login');
        });
    }
}
