<?php

return [
    // Identity comes from the brand config (white-label via .env).
    'name' => env('APP_NAME', 'Ledgerly'),
    'domain' => env('APP_DOMAIN', 'example.com'),
    'url' => env('APP_URL', 'http://localhost'),
    'email' => env('BRAND_SUPPORT_EMAIL', 'support@example.com'),
    'phone' => env('BRAND_PHONE', ''),
    'line' => env('BRAND_LINE', ''),
    'locales' => ['th', 'en'],

    // Hero background images per marketing page.
    // Replace these URLs with your own photos by uploading to /public/images/
    // and pointing at e.g. asset('images/hero-home.jpg').
    'hero_images' => [
        'home' => 'https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=2000&q=80',
        'services' => 'https://images.unsplash.com/photo-1551434678-e076c223a692?auto=format&fit=crop&w=2000&q=80',
        'about' => 'https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&w=2000&q=80',
        'contact' => 'https://images.unsplash.com/photo-1486406146926-c627a92ad1ab?auto=format&fit=crop&w=2000&q=80',
        // Login / auth pages background. Change THIS line to swap the photo on /login
        // (the marketing site keeps using the keys above). Defaults to the home photo.
        'login' => 'https://images.unsplash.com/photo-1497366754035-f200968a6e72?auto=format&fit=crop&w=2000&q=80',
    ],
];
