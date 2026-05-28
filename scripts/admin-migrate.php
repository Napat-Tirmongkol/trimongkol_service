<?php

/**
 * Standalone runner for `artisan migrate` + cache clears, designed for
 * Plesk's "Run a PHP script" scheduled task — so you don't have to
 * locate the PHP CLI binary by hand.
 *
 * Plesk picks the correct PHP version per domain automatically when
 * you use the "Run a PHP script" task type and point it at this file.
 */

set_time_limit(300);

$root = dirname(__DIR__);

require $root . '/vendor/autoload.php';

/** @var \Illuminate\Foundation\Application $app */
$app = require $root . '/bootstrap/app.php';

$kernel = $app->make(\Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$run = function (string $command, array $params = []) use ($kernel): void {
    echo "\n>>> php artisan {$command} " . http_build_query($params, '', ' ') . "\n";
    $code = $kernel->call($command, $params);
    echo $kernel->output();
    echo "exit code: {$code}\n";
};

$run('migrate', ['--force' => true]);
$run('optimize:clear');

echo "\nDone.\n";
