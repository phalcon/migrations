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

use InvalidArgumentException;
use Phalcon\Migrations\Db\Adapter\AdapterFactory;
use Phalcon\Migrations\Db\Adapter\Mysql;
use Phalcon\Migrations\Db\Connection;
use Phalcon\Migrations\Tests\AbstractMysqlTestCase;
use Phalcon\Migrations\Utils\Config;

final class ConnectionTest extends AbstractMysqlTestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = Connection::fromConfig(static::getMigrationsConfig());
        $this->connection->connect();
    }

    public function testIsConnectedReturnsTrueAfterConnect(): void
    {
        $this->assertTrue($this->connection->isConnected());
    }

    public function testIsConnectedReturnsFalseBeforeConnect(): void
    {
        $connection = Connection::fromConfig(static::getMigrationsConfig());

        $this->assertFalse($connection->isConnected());
    }

    public function testGetDriverName(): void
    {
        $this->assertSame('mysql', $this->connection->getDriverName());
    }

    public function testQuoteWrapsString(): void
    {
        $result = $this->connection->quote('hello');

        $this->assertStringContainsString('hello', $result);
    }

    public function testQuoteIdentifierForMysqlUseBackticks(): void
    {
        $result = $this->connection->quoteIdentifier('column_name');

        $this->assertSame('`column_name`', $result);
    }

    public function testFetchValueReturnsScalar(): void
    {
        $result = $this->connection->fetchValue('SELECT 1');

        $this->assertSame('1', (string) $result);
    }

    public function testFetchPairsReturnsKeyValueArray(): void
    {
        $this->connection->execute('CREATE TABLE IF NOT EXISTS `conn_pairs_test` (`k` VARCHAR(10), `v` VARCHAR(10))');
        $this->connection->execute("INSERT INTO `conn_pairs_test` VALUES ('a', '1'), ('b', '2')");

        $result = $this->connection->fetchPairs('SELECT `k`, `v` FROM `conn_pairs_test`');

        $this->connection->execute('DROP TABLE `conn_pairs_test`');

        $this->assertSame(['a' => '1', 'b' => '2'], $result);
    }

    public function testIterateYieldsRows(): void
    {
        $rows = [];
        foreach ($this->connection->iterate('SELECT 1 AS n UNION SELECT 2') as $row) {
            $rows[] = $row;
        }

        $this->assertCount(2, $rows);
    }

    public function testBeginCommitRollback(): void
    {
        $this->connection->execute('CREATE TABLE IF NOT EXISTS `conn_tx_test` (`v` INT)');

        $this->connection->begin();
        $this->connection->execute("INSERT INTO `conn_tx_test` VALUES (42)");
        $this->connection->commit();

        $result = $this->connection->fetchOne('SELECT `v` FROM `conn_tx_test`');
        $this->assertSame('42', (string) $result['v']);

        $this->connection->begin();
        $this->connection->execute("INSERT INTO `conn_tx_test` VALUES (99)");
        $this->connection->rollback();

        $rows = $this->connection->fetchAll('SELECT `v` FROM `conn_tx_test`');
        $this->assertCount(1, $rows);

        $this->connection->execute('DROP TABLE `conn_tx_test`');
    }

    public function testSetLoggerIsInvokedOnExecute(): void
    {
        $logged = [];
        $this->connection->setLogger(function (string $sql) use (&$logged): void {
            $logged[] = $sql;
        });

        $this->connection->execute('SELECT 1');

        $this->assertNotEmpty($logged);
        $this->assertStringContainsString('SELECT 1', $logged[0]);

        $this->connection->setLogger(null);
    }

    public function testAdapterFactoryCreatesMysqlAdapter(): void
    {
        $adapter = AdapterFactory::create($this->connection);

        $this->assertInstanceOf(Mysql::class, $adapter);
    }

    public function testFromConfigThrowsOnUnsupportedAdapter(): void
    {
        $this->expectException(InvalidArgumentException::class);

        Connection::fromConfig(Config::fromArray([
            'database' => ['adapter' => 'oracle', 'host' => 'localhost', 'dbname' => 'test'],
        ]));
    }

    public function testConnectIsIdempotent(): void
    {
        $this->connection->connect();
        $this->connection->connect();

        $this->assertTrue($this->connection->isConnected());
    }
}
