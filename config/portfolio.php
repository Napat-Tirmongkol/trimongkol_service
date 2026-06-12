<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Personal Portfolio access control
    |--------------------------------------------------------------------------
    |
    | Only Google accounts whose verified email is in this allowlist may sign
    | in to /portfolio. Anyone else who completes the OAuth flow is rejected
    | with a 403 before a session is created.
    |
    | Override at deploy time via the PORTFOLIO_ALLOWED_EMAILS env var
    | (comma- or whitespace-separated). The hard-coded default keeps the
    | owner locked in even if the env var is missing.
    |
    */

    'allowed_emails' => array_values(array_filter(array_map(
        'trim',
        preg_split('/[\s,]+/', (string) env('PORTFOLIO_ALLOWED_EMAILS', 'napatnom@gmail.com')),
    ))),

];
