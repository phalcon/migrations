<?php

declare(strict_types=1);

namespace Helper;

use Codeception\Module;
use Codeception\TestInterface;
use PDO;
use Phalcon\Config;
use Phalcon\Db\Adapter\Pdo\AbstractPdo;
use Phalcon\Db\Adapter\PdoFactory;
use Phalcon\Migrations\Migrations;

class Mysql extends Module
{
    public function _before(TestInterface $test)
    {
        foreach ($this->getPhalconDb()->listTables() as $table) {
            $this->getPhalconDb()->dropTable($table);
        }
    }

    public function _after(TestInterface $test)
    {
        /**
         * Reset filename or DB connection
         */
        Migrations::resetStorage();
    }

    /**
     * @return PDO
     * @throws \Codeception\Exception\ModuleException
     */
    public function getDb(): PDO
    {
        return $this->getModule('Db')->_getDbh();
    }

    /**
     * @return AbstractPdo
     */
    public function getPhalconDb(): AbstractPdo
    {
        /** @var AbstractPdo $db */
        $db = (new PdoFactory())->newInstance('mysql', [
            'host' => getenv('MYSQL_TEST_DB_HOST'),
            'port' => getenv('MYSQL_TEST_DB_PORT'),
            'dbname' => getenv('MYSQL_TEST_DB_DATABASE'),
            'username' => getenv('MYSQL_TEST_DB_USER'),
            'password' => getenv('MYSQL_TEST_DB_PASSWORD'),
        ]);

        return $db;
    }

    /**
     * @return Config
     */
    public function getMigrationsConfig(): Config
    {
        return new Config([
            'database' => [
                'adapter' => 'mysql',
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
    }
}
