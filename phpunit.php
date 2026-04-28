<?php

/**
 * This file is part of the Phalcon Migrations.
 *
 * (c) Phalcon Team <team@phalcon.io>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

error_reporting(E_ALL);

$autoloader = __DIR__ . '/vendor/autoload.php';

if (!file_exists($autoloader)) {
    echo "Composer autoloader not found: $autoloader" . PHP_EOL;
    echo "Please issue 'composer install' and try again." . PHP_EOL;
    exit(1);
}

require_once $autoloader;

$dotenvPath = file_exists(__DIR__ . '/tests/.env') ? __DIR__ . '/tests' : __DIR__;
if (file_exists($dotenvPath . '/.env')) {
    Dotenv\Dotenv::createImmutable($dotenvPath)->load();
}
