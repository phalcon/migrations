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

namespace Phalcon\Migrations\Tests\Unit\Mysql;

use Phalcon\Migrations\Db\Adapter\AdapterInterface;
use Phalcon\Migrations\Db\Connection;
use Phalcon\Migrations\Migrations;
use Phalcon\Migrations\Tests\AbstractMysqlTestCase;
use Phalcon\Migrations\Utils\Config;
use ReflectionProperty;

/**
 * @see https://github.com/phalcon/migrations/issues/254
 */
final class Issue254Test extends AbstractMysqlTestCase
{
    public function testConnectionUsesCharsetOfConfig(): void
    {
        $this->assertSame('latin1', $this->connectionCharset($this->config('latin1')));
    }

    public function testConnectionUsesUtf8mb4ByDefault(): void
    {
        $this->assertSame('utf8mb4', $this->connectionCharset(static::getMigrationsConfig()));
    }

    public function testLogConnectionKeepsForeignKeyChecks(): void
    {
        Migrations::addCurrentVersion(['config' => static::getMigrationsConfig(), 'migrationsInDb' => true], '0.0.1');

        /** @var AdapterInterface $storage */
        $storage = (new ReflectionProperty(Migrations::class, 'storage'))->getValue();
        $row     = $storage->fetchOne('SELECT @@foreign_key_checks AS checks');

        $this->assertSame('1', (string) $row['checks']);
    }

    private function config(string $charset): Config
    {
        return Config::fromArray([
            'database' => [
                'adapter'  => 'mysql',
                'charset'  => $charset,
                'dbname'   => $_ENV['MYSQL_TEST_DB_DATABASE'],
                'host'     => $_ENV['MYSQL_TEST_DB_HOST'],
                'password' => $_ENV['MYSQL_TEST_DB_PASSWORD'],
                'port'     => $_ENV['MYSQL_TEST_DB_PORT'],
                'username' => $_ENV['MYSQL_TEST_DB_USER'],
            ],
        ]);
    }

    private function connectionCharset(Config $config): string
    {
        $row = Connection::fromConfig($config)->fetchOne('SELECT @@character_set_connection AS charset');

        return (string) $row['charset'];
    }
}
