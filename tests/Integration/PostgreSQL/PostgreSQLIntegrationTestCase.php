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

namespace Phalcon\Migrations\Tests\Integration\PostgreSQL;

use Phalcon\Config;
use Phalcon\Db\Adapter\Pdo\AbstractPdo;
use Phalcon\Db\Adapter\PdoFactory;
use Phalcon\Migrations\Migrations;
use Phalcon\Migrations\Tests\Integration\IntegrationTestCase;

class PostgreSQLIntegrationTestCase extends IntegrationTestCase
{
    /**
     * @var string
     */
    protected $defaultSchema;

    public function setUp(): void
    {
        $this->db = $this->initializeDatabase();
    }

    public function tearDown(): void
    {
        /**
         * Cleanup Database
         */
        $this->db->query('DROP SCHEMA IF EXISTS ' . $this->defaultSchema . ' CASCADE;');

        /**
         * Reset filename or DB connection
         */
        Migrations::resetStorage();
    }

    public static function setUpBeforeClass(): void
    {
        self::$generateConfig = new Config([
            'database' => [
                'adapter' => getenv('POSTGRES_TEST_DB_ADAPTER'),
                'host' => getenv('POSTGRES_TEST_DB_HOST'),
                'port' => getenv('POSTGRES_TEST_DB_PORT'),
                'username' => getenv('POSTGRES_TEST_DB_USER'),
                'password' => getenv('POSTGRES_TEST_DB_PASSWORD'),
                'dbname' => getenv('POSTGRES_TEST_DB_DATABASE'),
                'schema' => getenv('POSTGRES_TEST_DB_SCHEMA'),
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
        $db = (new PdoFactory())->newInstance(getenv('POSTGRES_TEST_DB_ADAPTER'), [
            'host' => getenv('POSTGRES_TEST_DB_HOST'),
            'port' => getenv('POSTGRES_TEST_DB_PORT'),
            'username' => getenv('POSTGRES_TEST_DB_USER'),
            'password' => getenv('POSTGRES_TEST_DB_PASSWORD'),
            'dbname' => getenv('POSTGRES_TEST_DB_DATABASE'),
        ]);

        $this->defaultSchema = getenv('POSTGRES_TEST_DB_SCHEMA');

        $db->execute('DROP SCHEMA IF EXISTS "' . $this->defaultSchema . '" CASCADE;');
        $db->execute('CREATE SCHEMA "' . $this->defaultSchema . '";');
        $db->execute('SET search_path TO "' . $this->defaultSchema . '"');

        return $db;
    }
}
