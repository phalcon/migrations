<?php

declare(strict_types=1);

namespace Helper;

use Codeception\Module;
use Codeception\TestInterface;
use Phalcon\Config\Config;
use Phalcon\Db\Adapter\Pdo\AbstractPdo;
use Phalcon\Migrations\Db\Adapter\Pdo\PdoPostgresql;
use Phalcon\Migrations\Db\Dialect\DialectPostgresql;
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
        $options = $this->getMigrationsConfig()
                        ->get('database')
                        ->toArray()
        ;
        unset($options['adapter']);

        self::$defaultSchema = $_ENV['POSTGRES_TEST_DB_SCHEMA'];
        /** @var AbstractPdo $db */
        self::$phalconDb = new PdoPostgresql($options);
        self::$phalconDb->setDialect(new DialectPostgresql());
    }

    public function _before(TestInterface $test): void
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
    public function _after(TestInterface $test): void
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
            'database'    => [
                'adapter'  => 'postgresql',
                'host'     => $_ENV['POSTGRES_TEST_DB_HOST'],
                'port'     => $_ENV['POSTGRES_TEST_DB_PORT'],
                'username' => $_ENV['POSTGRES_TEST_DB_USER'],
                'password' => $_ENV['POSTGRES_TEST_DB_PASSWORD'],
                'dbname'   => $_ENV['POSTGRES_TEST_DB_DATABASE'],
                'schema'   => $_ENV['POSTGRES_TEST_DB_SCHEMA'],
            ],
            'application' => [
                'logInDb' => true,
            ],
        ]);
    }

    public function getDefaultSchema(): string
    {
        return self::$defaultSchema;
    }
}
