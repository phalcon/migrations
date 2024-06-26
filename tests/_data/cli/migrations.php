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

use Dotenv\Dotenv;
use Phalcon\Config\Config;

Dotenv::createImmutable(realpath('tests'))->load();

/**
 * Current config file is used in CLI tests
 * For cases when it is needed to test real working cases.
 */
return new Config([
    'database' => [
        'adapter' => 'mysql',
        'host' => $_ENV['MYSQL_TEST_DB_HOST'],
        'port' => $_ENV['MYSQL_TEST_DB_PORT'],
        'username' => $_ENV['MYSQL_TEST_DB_USER'],
        'password' => $_ENV['MYSQL_TEST_DB_PASSWORD'],
        'dbname' => $_ENV['MYSQL_TEST_DB_DATABASE'],
    ],
    'application' => [
        'logInDb' => true,
        'migrationsDir' => 'tests/_output',
    ],
]);
