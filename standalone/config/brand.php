<?php

/*
|--------------------------------------------------------------------------
| Brand / white-label
|--------------------------------------------------------------------------
|
| Single source of truth for the product's identity. To re-brand a deployment
| edit these via .env — no code changes needed:
|
|   APP_NAME="Your Brand"
|   BRAND_TAGLINE="Your tagline"
|   BRAND_COLOR="#1f47e6"          # primary, used for theme-color + dialogs
|   BRAND_LOGO=                    # optional: /images/logo.svg (falls back to
|                                  # a generated monogram of the first letter)
|
| Tailwind's brand-* palette is defined in resources/css/app.css (@theme) and
| is build-time; BRAND_COLOR here covers the runtime spots (browser theme
| color, SweetAlert buttons) so a quick re-brand needs no asset rebuild.
|
*/

return [
    'name' => env('APP_NAME', 'Ledgerly'),
    'tagline' => env('BRAND_TAGLINE', 'ระบบบัญชีสำหรับธุรกิจไทย'),
    'color' => env('BRAND_COLOR', '#1f47e6'),
    'logo' => env('BRAND_LOGO'),                 // null → generated monogram
    'support_email' => env('BRAND_SUPPORT_EMAIL', 'support@example.com'),
];
