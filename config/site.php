<?php

return [
    'name' => 'Tirmongkol Service',
    'domain' => 'tirmongkol.com',
    'url' => 'https://tirmongkol.com',
    'email' => 'contact@tirmongkol.com',
    'phone' => '+66 00 000 0000',
    'line' => '@tirmongkol',
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
