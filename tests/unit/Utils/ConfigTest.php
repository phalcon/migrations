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

namespace Phalcon\Migrations\Tests\Unit\Utils;

use Phalcon\Migrations\Tests\AbstractTestCase;
use Phalcon\Migrations\Utils\Config;

final class ConfigTest extends AbstractTestCase
{
    public function testFromArrayWithEmptyData(): void
    {
        $config = Config::fromArray([]);

        $this->assertNull($config->adapter);
        $this->assertNull($config->dbname);
        $this->assertNull($config->descr);
        $this->assertSame([], $config->exportDataFromTables);
        $this->assertNull($config->host);
        $this->assertFalse($config->logInDb);
        $this->assertNull($config->migrationsDir);
        $this->assertFalse($config->migrationsTsBased);
        $this->assertFalse($config->noAutoIncrement);
        $this->assertNull($config->password);
        $this->assertNull($config->port);
        $this->assertNull($config->schema);
        $this->assertFalse($config->skipForeignChecks);
        $this->assertFalse($config->skipRefSchema);
        $this->assertNull($config->username);
    }

    public function testFromArrayWithDatabaseFields(): void
    {
        $config = Config::fromArray([
            'database' => [
                'adapter'  => 'mysql',
                'dbname'   => 'test_db',
                'host'     => 'localhost',
                'password' => 'secret',
                'port'     => '3306',
                'schema'   => 'public',
                'username' => 'root',
            ],
        ]);

        $this->assertSame('mysql', $config->adapter);
        $this->assertSame('test_db', $config->dbname);
        $this->assertSame('localhost', $config->host);
        $this->assertSame('secret', $config->password);
        $this->assertSame(3306, $config->port);
        $this->assertSame('public', $config->schema);
        $this->assertSame('root', $config->username);
    }

    public function testFromArrayWithApplicationFields(): void
    {
        $config = Config::fromArray([
            'application' => [
                'descr'                => 'test description',
                'exportDataFromTables' => ['table1', 'table2'],
                'logInDb'              => true,
                'migrationsDir'        => '/migrations',
                'migrationsTsBased'    => true,
                'no-auto-increment'    => true,
                'skip-foreign-checks'  => true,
                'skip-ref-schema'      => true,
            ],
        ]);

        $this->assertSame('test description', $config->descr);
        $this->assertSame(['table1', 'table2'], $config->exportDataFromTables);
        $this->assertTrue($config->logInDb);
        $this->assertSame('/migrations', $config->migrationsDir);
        $this->assertTrue($config->migrationsTsBased);
        $this->assertTrue($config->noAutoIncrement);
        $this->assertTrue($config->skipForeignChecks);
        $this->assertTrue($config->skipRefSchema);
    }

    public function testFromArrayWithExportDataFromTablesAsString(): void
    {
        $config = Config::fromArray([
            'application' => [
                'exportDataFromTables' => 'table1,table2,table3',
            ],
        ]);

        $this->assertSame(['table1', 'table2', 'table3'], $config->exportDataFromTables);
    }

    public function testToArrayExcludesNullValues(): void
    {
        $config = Config::fromArray([]);

        $this->assertSame([], $config->toArray());
    }

    public function testToArrayIncludesOnlyDatabaseFields(): void
    {
        $config = Config::fromArray([
            'database' => [
                'adapter'  => 'mysql',
                'dbname'   => 'test_db',
                'host'     => 'localhost',
                'password' => 'secret',
                'port'     => 3306,
                'schema'   => 'public',
                'username' => 'root',
            ],
        ]);

        $result = $config->toArray();

        $this->assertSame('mysql', $result['adapter']);
        $this->assertSame('test_db', $result['dbname']);
        $this->assertSame('localhost', $result['host']);
        $this->assertSame('secret', $result['password']);
        $this->assertSame(3306, $result['port']);
        $this->assertSame('public', $result['schema']);
        $this->assertSame('root', $result['username']);
    }

    public function testToArrayOmitsSchemaWhenNull(): void
    {
        $config = Config::fromArray([
            'database' => [
                'adapter' => 'mysql',
                'dbname'  => 'test_db',
            ],
        ]);

        $result = $config->toArray();

        $this->assertArrayNotHasKey('schema', $result);
    }
}
