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

use Phalcon\Db\Adapter\Pdo\AbstractPdo;
use Phalcon\Db\Adapter\Pdo\Postgresql as PdoPostgresql;
use Phalcon\Db\Enum;
use Phalcon\Migrations\Migrations;
use Phalcon\Migrations\Utils\Config;

abstract class AbstractPostgresqlTestCase extends AbstractTestCase
{
    protected static ?AbstractPdo $phalconDb = null;

    protected static string $defaultSchema = '';

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();

        static::$defaultSchema = $_ENV['POSTGRES_TEST_DB_SCHEMA'];

        $options = static::getMigrationsConfig()->toArray();
        unset($options['adapter']);

        self::$phalconDb = new PdoPostgresql($options);
    }

    protected function setUp(): void
    {
        parent::setUp();

        $schema = static::$defaultSchema;
        self::$phalconDb->execute('DROP SCHEMA IF EXISTS "' . $schema . '" CASCADE');
        self::$phalconDb->execute('CREATE SCHEMA "' . $schema . '"');
        self::$phalconDb->execute('SET search_path TO "' . $schema . '"');
    }

    protected function tearDown(): void
    {
        self::$phalconDb->execute('DROP SCHEMA IF EXISTS "' . static::$defaultSchema . '" CASCADE');
        Migrations::resetStorage();

        parent::tearDown();
    }

    public function getPhalconDb(): AbstractPdo
    {
        return self::$phalconDb;
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

    protected function assertNumRecords(int $expected, string $table): void
    {
        $result = $this->getPhalconDb()->fetchOne(
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
        $results = $this->getPhalconDb()->fetchAll(
            sprintf('SELECT "%s" FROM %s', $column, $table),
            Enum::FETCH_ASSOC
        );

        return array_column($results, $column);
    }

}
