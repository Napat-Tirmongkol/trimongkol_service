<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Google Cloud Text-to-Speech for queue voice announcements. The API key
    // is normally set in /admin (encrypted in site_settings); these env vars
    // are an optional fallback for ops that prefer .env.
    'google_tts' => [
        'key' => env('GOOGLE_TTS_KEY'),
        'voice' => env('GOOGLE_TTS_VOICE'),
    ],

    // SlipOK PromptPay slip verification for queue plan self-checkout. Keys are
    // normally set in /admin (encrypted in site_settings); env is a fallback.
    'slipok' => [
        'key' => env('SLIPOK_API_KEY'),
        'branch' => env('SLIPOK_BRANCH_ID'),
    ],

    // LINE Messaging API — push notifications to the shop owner. Channel token
    // is normally set in /admin (encrypted); env is an optional fallback.
    'line' => [
        'channel_token' => env('LINE_CHANNEL_TOKEN'),
        'target_id' => env('LINE_TARGET_ID'),
    ],

    // Discord incoming webhook — owner notifications. Normally set in /admin;
    // env is an optional fallback.
    'discord' => [
        'webhook' => env('DISCORD_WEBHOOK_URL'),
    ],

    // Google Gemini API — used by the social media auto-post feature.
    // Key is normally set in /admin (encrypted in site_settings); env is a fallback.
    'gemini' => [
        'key' => env('GEMINI_API_KEY'),
    ],

    // Google OAuth (Socialite) — sign-in for the personal portfolio at
    // /portfolio/login. Credentials come from a Google Cloud OAuth client;
    // the redirect URI must exactly match what's registered in the console.
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'redirect' => env('GOOGLE_REDIRECT_URI'),
    ],

];
