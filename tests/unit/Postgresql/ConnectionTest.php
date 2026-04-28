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

namespace Phalcon\Migrations\Tests\Unit\Postgresql;

use Phalcon\Migrations\Db\Adapter\AdapterFactory;
use Phalcon\Migrations\Db\Adapter\Postgresql;
use Phalcon\Migrations\Db\Connection;
use Phalcon\Migrations\Tests\AbstractPostgresqlTestCase;

final class ConnectionTest extends AbstractPostgresqlTestCase
{
    private Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = Connection::fromConfig(static::getMigrationsConfig());
        $this->connection->connect();
    }

    public function testGetDriverName(): void
    {
        $this->assertSame('pgsql', $this->connection->getDriverName());
    }

    public function testQuoteIdentifierForPgsqlUsesDoubleQuotes(): void
    {
        $result = $this->connection->quoteIdentifier('column_name');

        $this->assertSame('"column_name"', $result);
    }

    public function testAdapterFactoryCreatesPostgresqlAdapter(): void
    {
        $adapter = AdapterFactory::create($this->connection);

        $this->assertInstanceOf(Postgresql::class, $adapter);
    }

    public function testFetchValueReturnsScalar(): void
    {
        $result = $this->connection->fetchValue('SELECT 1');

        $this->assertSame('1', (string) $result);
    }

    public function testFetchPairsReturnsKeyValueArray(): void
    {
        $this->connection->execute(
            'CREATE TABLE IF NOT EXISTS conn_pairs_pg (k VARCHAR(10), v VARCHAR(10))'
        );
        $this->connection->execute("INSERT INTO conn_pairs_pg VALUES ('a', '1'), ('b', '2')");

        $result = $this->connection->fetchPairs('SELECT k, v FROM conn_pairs_pg');

        $this->connection->execute('DROP TABLE conn_pairs_pg');

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
        $this->connection->execute('CREATE TABLE IF NOT EXISTS conn_tx_pg (v INT)');

        $this->connection->begin();
        $this->connection->execute('INSERT INTO conn_tx_pg VALUES (42)');
        $this->connection->commit();

        $result = $this->connection->fetchOne('SELECT v FROM conn_tx_pg');
        $this->assertSame('42', (string) $result['v']);

        $this->connection->begin();
        $this->connection->execute('INSERT INTO conn_tx_pg VALUES (99)');
        $this->connection->rollback();

        $rows = $this->connection->fetchAll('SELECT v FROM conn_tx_pg');
        $this->assertCount(1, $rows);

        $this->connection->execute('DROP TABLE conn_tx_pg');
    }

    public function testSetLoggerIsInvokedOnExecute(): void
    {
        $logged = [];
        $this->connection->setLogger(function (string $sql) use (&$logged): void {
            $logged[] = $sql;
        });

        $this->connection->execute('SELECT 1');

        $this->assertNotEmpty($logged);

        $this->connection->setLogger(null);
    }
}
