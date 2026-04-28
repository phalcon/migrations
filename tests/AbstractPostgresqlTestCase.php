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

abstract class AbstractPostgresqlTestCase extends AbstractTestCase
{
    protected static ?AdapterInterface $db = null;

    protected static string $defaultSchema = '';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        static::$defaultSchema = $_ENV['POSTGRES_TEST_DB_SCHEMA'];

        self::$db = AdapterFactory::create(
            Connection::fromConfig(static::getMigrationsConfig())
        );
    }

    protected function setUp(): void
    {
        parent::setUp();

        $schema = static::$defaultSchema;
        self::$db->execute('DROP SCHEMA IF EXISTS "' . $schema . '" CASCADE');
        self::$db->execute('CREATE SCHEMA "' . $schema . '"');
        self::$db->execute('SET search_path TO "' . $schema . '"');
    }

    protected function tearDown(): void
    {
        self::$db->execute('DROP SCHEMA IF EXISTS "' . static::$defaultSchema . '" CASCADE');
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

    public function getDefaultSchema(): string
    {
        return static::$defaultSchema;
    }

    public static function getMigrationsConfig(): Config
    {
        return Config::fromArray([
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

    protected function describeColumns(string $table, string $schema = ''): array
    {
        $schema = $schema ?: static::$defaultSchema;

        return array_values(self::$db->listColumns($schema, $table));
    }

    protected function describeIndexes(string $table, string $schema = ''): array
    {
        $schema = $schema ?: static::$defaultSchema;

        return self::$db->listIndexes($schema, $table);
    }

    protected function insertRow(string $table, array $values, array $columns): void
    {
        $schema = static::$defaultSchema;
        $cols   = implode(', ', array_map(fn($c) => '"' . $c . '"', $columns));
        $vals   = implode(', ', array_map(fn($v) => self::$db->quote((string) $v), $values));
        self::$db->execute(sprintf('INSERT INTO "%s"."%s" (%s) VALUES (%s)', $schema, $table, $cols, $vals));
    }

    protected function assertNumRecords(int $expected, string $table): void
    {
        $result = self::$db->fetchOne(
            sprintf('SELECT COUNT(*) AS cnt FROM %s', $table)
        );
        $this->assertSame($expected, (int) $result['cnt']);
    }

    protected function getDataDir(string $path = ''): string
    {
        return __DIR__ . '/_data' . ($path ? '/' . ltrim($path, '/') : '');
    }

    protected function grabColumnFromDatabase(string $table, string $column): array
    {
        $results = self::$db->fetchAll(
            sprintf('SELECT "%s" FROM %s', $column, $table)
        );

        return array_column($results, $column);
    }
}