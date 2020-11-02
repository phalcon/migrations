<?php

declare(strict_types=1);

namespace Helper;

use Codeception\Module;
use Codeception\TestInterface;
use PDO;
use Phalcon\Config;
use Phalcon\Db\Adapter\Pdo\AbstractPdo;
use Phalcon\Db\Adapter\PdoFactory;
use Phalcon\Db\Exception;
use Phalcon\Migrations\Migrations;

class Mysql extends Module
{
    /**
     * @var AbstractPdo|null
     */
    protected static $phalconDb;

    public function _initialize()
    {
        /** @var AbstractPdo $db */
        self::$phalconDb = (new PdoFactory())
            ->newInstance(
                'mysql',
                $this->getMigrationsConfig()->get('database')->toArray()
            );
    }

    public function _before(TestInterface $test)
    {
        $this->setForeignKeys();
        foreach ($this->getPhalconDb()->listTables() as $table) {
            $this->getPhalconDb()->dropTable($table);
        }

        $this->setForeignKeys(true);
    }

    public function _after(TestInterface $test)
    {
        $this->setForeignKeys();
        foreach ($this->getPhalconDb()->listTables() as $table) {
            $this->getPhalconDb()->dropTable($table);
        }

        $this->setForeignKeys(true);

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
        return self::$phalconDb;
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

    /**
     * @see https://gist.github.com/afischoff/9608738
     * @see https://github.com/phalcon/cphalcon/issues/14620
     *
     * @param string $table
     * @param array $columns
     * @param array $rows
     * @return void
     */
    public function batchInsert(string $table, array $columns, array $rows): void
    {
        $str = '';
        foreach ($rows as $values) {
            foreach ($values as &$val) {
                if (is_null($val)) {
                    $val = 'NULL';
                    continue;
                }

                if (is_string($val)) {
                    $val = $this->getPhalconDb()->escapeString($val);
                }
            }

            $str .= sprintf('(%s),', implode(',', $values));
        }

        $str = rtrim($str, ',') . ';';
        $query = sprintf(
            "INSERT INTO `%s` (%s) VALUES %s",
            $table,
            sprintf('`%s`', implode('`,`', $columns)),
            $str
        );

        $this->getPhalconDb()->execute($query);
    }

    /**
     * @param string $directory
     * @throws Exception
     */
    public function silentRun(string $directory): void
    {
        ob_start();
        Migrations::run([
            'migrationsDir' => codecept_data_dir($directory),
            'config' => $this->getMigrationsConfig(),
            'migrationsInDb' => true,
        ]);
        ob_clean();
    }

    /**
     * Executes 'SET FOREIGN_KEY_CHECKS' query
     *
     * @param bool $enabled
     */
    protected function setForeignKeys(bool $enabled = false): void
    {
        $this->getPhalconDb()->execute('SET FOREIGN_KEY_CHECKS=' . intval($enabled));
    }
}
