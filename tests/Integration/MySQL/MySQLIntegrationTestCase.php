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

namespace Phalcon\Migrations\Tests\Integration\MySQL;

use Phalcon\Config;
use Phalcon\Db\Adapter\Pdo\AbstractPdo;
use Phalcon\Db\Adapter\PdoFactory;
use Phalcon\Migrations\Migrations;
use Phalcon\Migrations\Tests\Integration\IntegrationTestCase;

class MySQLIntegrationTestCase extends IntegrationTestCase
{
    public function setUp(): void
    {
        $this->db = $this->initializeDatabase();
    }

    public function tearDown(): void
    {
        /**
         * Cleanup Database
         */
        $this->db->query('DROP DATABASE IF EXISTS `' . getenv('MYSQL_TEST_DB_DATABASE') . '`;');

        /**
         * Reset filename or DB connection
         */
        Migrations::resetStorage();
    }

    public static function setUpBeforeClass(): void
    {
        self::$generateConfig = new Config([
            'database' => [
                'adapter' => getenv('MYSQL_TEST_DB_ADAPTER'),
                'host' => getenv('MYSQL_TEST_DB_HOST'),
                'port' => getenv('MYSQL_TEST_DB_PORT'),
                'username' => getenv('MYSQL_TEST_DB_USER'),
                'password' => getenv('MYSQL_TEST_DB_PASSWORD'),
                'dbname' => getenv('MYSQL_TEST_DB_DATABASE'),
            ],
            'application' => [
                'logInDb' => true,
            ],
        ]);

        ob_start();
    }

    protected function initializeDatabase(): AbstractPdo
    {
        /** @var AbstractPdo $db */
        $db = (new PdoFactory())->newInstance(getenv('MYSQL_TEST_DB_ADAPTER'), [
            'host' => getenv('MYSQL_TEST_DB_HOST'),
            'port' => getenv('MYSQL_TEST_DB_PORT'),
            'username' => getenv('MYSQL_TEST_DB_USER'),
            'password' => getenv('MYSQL_TEST_DB_PASSWORD'),
        ]);

        $databaseName = getenv('MYSQL_TEST_DB_DATABASE');
        $db->query('DROP DATABASE IF EXISTS `' . $databaseName . '`;');
        $db->query('CREATE DATABASE `' . $databaseName . '`;');
        $db->query('USE `' . $databaseName . '`');

        return $db;
    }
}
