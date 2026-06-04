<?php

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit;
}

chdir(__DIR__);
passthru(PHP_BINARY . ' artisan schedule:run --no-interaction 2>&1');
