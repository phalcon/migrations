<?php
declare(strict_types=1);

namespace Phalcon\Migrations\Tests\Integration;

use Exception;
use Phalcon\Config;
use Phalcon\Db\Adapter\Pdo\AbstractPdo;
use Phalcon\Db\Adapter\PdoFactory;
use Phalcon\Migrations\Migrations;
use PHPUnit\Framework\TestCase;
use function Phalcon\Migrations\Tests\remove_dir;
use function Phalcon\Migrations\Tests\root_path;

class IntegrationTestCase extends TestCase
{
    /**
     * @var AbstractPdo
     */
    protected $db;

    /**
     * @var array
     */
    protected static $generateConfig;

    public static function setUpBeforeClass(): void
    {
        self::$generateConfig = new Config([
            'database' => [
                'adapter' => getenv('TEST_DB_ADAPTER'),
                'host' => getenv('TEST_DB_HOST'),
                'port' => getenv('TEST_DB_PORT'),
                'username' => getenv('TEST_DB_USER'),
                'password' => getenv('TEST_DB_PASSWORD'),
                'dbname' => getenv('TEST_DB_DATABASE'),
            ],
            'application' => [
                'logInDb' => true,
            ],
        ]);

        ob_start();
    }

    public static function tearDownAfterClass(): void
    {
        ob_get_clean();

        /**
         * Cleanup tests output folders
         */
        remove_dir(root_path('tests/var/output/'));
    }

    /**
     * @throws Exception
     */
    public function setUp(): void
    {
        $adapter = getenv('TEST_DB_ADAPTER');
        switch ($adapter) {
            case 'mysql':
                $this->db = $this->initializeMySQL();
                break;

            case 'postgresql':
                $this->db = $this->initializePostgreSQL();
                break;

            default:
                throw new Exception(sprintf('Adapter %s not found', $adapter));
                break;
        }

    }

    /**
     * Cleanup Database and reset connection
     *
     * @throws Exception
     */
    public function tearDown(): void
    {
        $adapter = getenv('TEST_DB_ADAPTER');
        $dbName = getenv('TEST_DB_DATABASE');

        switch ($adapter) {
            case 'mysql':
                $this->db->query('DROP DATABASE IF EXISTS `' . $dbName . '`;');
                break;

            case 'postgresql':
                $this->db->query('DROP SCHEMA IF EXISTS ' . $dbName . ' CASCADE;');
                break;

            default:
                throw new Exception(sprintf('Adapter %s not found', $adapter));
                break;
        }

        /**
         * Reset filename or DB connection
         */
        Migrations::resetStorage();
    }

    protected function initializePostgreSQL(): AbstractPdo
    {
        /** @var AbstractPdo $db */
        $db = (new PdoFactory())->newInstance(getenv('TEST_DB_ADAPTER'), [
            'host' => getenv('TEST_DB_HOST'),
            'port' => getenv('TEST_DB_PORT'),
            'username' => getenv('TEST_DB_USER'),
            'password' => getenv('TEST_DB_PASSWORD'),
        ]);

        $databaseName = getenv('TEST_DB_DATABASE');

        $row = $db->fetchOne('SELECT datname FROM pg_catalog.pg_database WHERE datname = \'' . $databaseName . '\';');
        if (empty($row)) {
            $db->query('CREATE SCHEMA "' . $databaseName . '";');
            $db->query('SET search_path TO "' . $databaseName . '"');
        }

        return $db;
    }

    protected function initializeMySQL(): AbstractPdo
    {
        /** @var AbstractPdo $db */
        $db = (new PdoFactory())->newInstance(getenv('TEST_DB_ADAPTER'), [
            'host' => getenv('TEST_DB_HOST'),
            'port' => getenv('TEST_DB_PORT'),
            'username' => getenv('TEST_DB_USER'),
            'password' => getenv('TEST_DB_PASSWORD'),
        ]);

        $databaseName = getenv('TEST_DB_DATABASE');
        $db->query('DROP DATABASE IF EXISTS `' . $databaseName . '`;');
        $db->query('CREATE DATABASE `' . $databaseName . '`;');
        $db->query('USE `' . $databaseName . '`');

        return $db;
    }
}
