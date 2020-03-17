<?php

declare(strict_types=1);

namespace Helper;

use Codeception\Module;
use Codeception\TestInterface;
use Phalcon\Config;
use Phalcon\Db\Adapter\Pdo\AbstractPdo;
use Phalcon\Db\Adapter\PdoFactory;
use Phalcon\Migrations\Migrations;

class Postgresql extends Module
{
    /**
     * @var AbstractPdo|null
     */
    protected static $phalconDb;

    /**
     * @var string
     */
    protected static $defaultSchema;

    /**
     * Initialize Postgresql suite
     */
    public function _initialize()
    {
        $options = $this->getMigrationsConfig()->get('database')->toArray();
        unset($options['adapter']);

        self::$defaultSchema = getenv('POSTGRES_TEST_DB_SCHEMA');
        /** @var AbstractPdo $db */
        self::$phalconDb = (new PdoFactory())
            ->newInstance(
                'postgresql',
                $options
            );
    }

    public function _before(TestInterface $test)
    {
        self::$phalconDb->execute('DROP SCHEMA IF EXISTS "' . self::$defaultSchema . '" CASCADE');
        self::$phalconDb->execute('CREATE SCHEMA "' . self::$defaultSchema . '";');
        self::$phalconDb->execute('SET search_path TO "' . self::$defaultSchema . '"');
    }

    /**
     * After test HOOK
     *
     * @param TestInterface $test
     */
    public function _after(TestInterface $test)
    {
        /**
         * Cleanup Database
         */
        self::$phalconDb->execute('DROP SCHEMA IF EXISTS ' . self::$defaultSchema . ' CASCADE');

        /**
         * Reset filename or DB connection
         */
        Migrations::resetStorage();
    }

    /**
     * @return AbstractPdo
     */
    public function getPhalconDb(): AbstractPdo
    {
        return self::$phalconDb;
    }

    /**
     * @return Config
     */
    public function getMigrationsConfig(): Config
    {
        return new Config([
            'database' => [
                'adapter' => 'postgresql',
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
    }

    /**
     * @return string
     */
    public function getDefaultSchema(): string
    {
        return self::$defaultSchema;
    }
}
