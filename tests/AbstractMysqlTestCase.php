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

namespace Phalcon\Migrations\Tests;

use Phalcon\Migrations\Db\Adapter\AdapterFactory;
use Phalcon\Migrations\Db\Adapter\AdapterInterface;
use Phalcon\Migrations\Db\Connection;
use Phalcon\Migrations\Migrations;
use Phalcon\Migrations\Utils\Config;

abstract class AbstractMysqlTestCase extends AbstractTestCase
{
    protected static ?AdapterInterface $db = null;

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        self::$db = AdapterFactory::create(
            Connection::fromConfig(static::getMigrationsConfig())
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $schema = $_ENV['MYSQL_TEST_DB_DATABASE'];
        $this->setForeignKeys();
        foreach (self::$db->listTables($schema) as $table) {
            self::$db->dropTable($table, $schema);
        }
        $this->setForeignKeys(true);
    }

    protected function tearDown(): void
    {
        $schema = $_ENV['MYSQL_TEST_DB_DATABASE'];
        $this->setForeignKeys();
        foreach (self::$db->listTables($schema) as $table) {
            self::$db->dropTable($table, $schema);
        }
        $this->setForeignKeys(true);
        Migrations::resetStorage();

        parent::tearDown();
    }

    public function getAdapter(): AdapterInterface
    {
        return self::$db;
    }

    public function getPhalconDb(): AdapterInterface
    {
        return self::$db;
    }

    public static function getMigrationsConfig(): Config
    {
        return Config::fromArray([
            'database'    => [
                'adapter'  => 'mysql',
                'host'     => $_ENV['MYSQL_TEST_DB_HOST'],
                'port'     => $_ENV['MYSQL_TEST_DB_PORT'],
                'username' => $_ENV['MYSQL_TEST_DB_USER'],
                'password' => $_ENV['MYSQL_TEST_DB_PASSWORD'],
                'dbname'   => $_ENV['MYSQL_TEST_DB_DATABASE'],
            ],
            'application' => [
                'logInDb' => true,
            ],
        ]);
    }

    protected function describeColumns(string $table, string $schema = ''): array
    {
        $schema = $schema ?: $_ENV['MYSQL_TEST_DB_DATABASE'];

        return array_values(self::$db->listColumns($schema, $table));
    }

    protected function describeIndexes(string $table, string $schema = ''): array
    {
        $schema = $schema ?: $_ENV['MYSQL_TEST_DB_DATABASE'];

        return self::$db->listIndexes($schema, $table);
    }

    protected function describeReferences(string $table, string $schema = ''): array
    {
        $schema = $schema ?: $_ENV['MYSQL_TEST_DB_DATABASE'];

        return self::$db->listReferences($schema, $table);
    }

    protected function tableOptions(string $table, string $schema = ''): array
    {
        $schema = $schema ?: $_ENV['MYSQL_TEST_DB_DATABASE'];

        return self::$db->getTableOptions($schema, $table);
    }

    protected function assertNumRecords(int $expected, string $table): void
    {
        $result = self::$db->fetchOne(
            sprintf('SELECT COUNT(*) AS cnt FROM `%s`', $table)
        );
        $this->assertSame($expected, (int) $result['cnt']);
    }

    protected function batchInsert(string $table, array $columns, array $rows): void
    {
        $str = '';
        foreach ($rows as $values) {
            foreach ($values as &$val) {
                if (is_null($val)) {
                    $val = 'NULL';
                    continue;
                }
                if (is_string($val)) {
                    $val = self::$db->quote($val);
                }
            }
            $str .= sprintf('(%s),', implode(',', $values));
        }

        $str   = rtrim($str, ',') . ';';
        $query = sprintf(
            'INSERT INTO `%s` (%s) VALUES %s',
            $table,
            sprintf('`%s`', implode('`,`', $columns)),
            $str
        );

        self::$db->execute($query);
    }

    protected function getDataDir(string $path = ''): string
    {
        return __DIR__ . '/_data' . ($path ? '/' . ltrim($path, '/') : '');
    }

    protected function grabColumnFromDatabase(string $table, string $column): array
    {
        $results = self::$db->fetchAll(
            sprintf('SELECT `%s` FROM `%s`', $column, $table)
        );

        return array_column($results, $column);
    }

    protected function silentRun(string $directory): void
    {
        ob_start();
        try {
            Migrations::run([
                'migrationsDir'  => $this->getDataDir($directory),
                'config'         => static::getMigrationsConfig(),
                'migrationsInDb' => true,
            ]);
        } finally {
            ob_end_clean();
        }
    }

    protected function setForeignKeys(bool $enabled = false): void
    {
        self::$db->execute('SET FOREIGN_KEY_CHECKS=' . intval($enabled));
    }
}