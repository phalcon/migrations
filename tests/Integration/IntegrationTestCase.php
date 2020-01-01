<?php
declare(strict_types=1);

namespace Phalcon\Migrations\Tests\Integration;

use Phalcon\Config;
use Phalcon\Db\Adapter\Pdo\AbstractPdo;
use Phalcon\Db\Adapter\PdoFactory;
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

    public function setUp(): void
    {
        $this->db = $this->initializeDatabase();
    }

    public function tearDown(): void
    {
        /**
         * Cleanup Database
         */
        $this->db->query('DROP DATABASE IF EXISTS `' . getenv('TEST_DB_DATABASE') . '`;');
    }

    protected function initializeDatabase(): AbstractPdo
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
