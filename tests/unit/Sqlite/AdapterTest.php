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

namespace Phalcon\Migrations\Tests\Unit\Sqlite;

use Phalcon\Migrations\Db\Adapter\Sqlite;
use Phalcon\Migrations\Db\Column;
use Phalcon\Migrations\Db\Connection;
use Phalcon\Migrations\Db\Index;
use Phalcon\Migrations\Tests\AbstractTestCase;
use Phalcon\Migrations\Utils\Config;

final class AdapterTest extends AbstractTestCase
{
    private Sqlite $adapter;
    private Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = Connection::fromConfig(
            Config::fromArray([
                'database' => [
                    'adapter' => 'sqlite',
                    'dbname'  => ':memory:',
                ],
            ])
        );
        $this->connection->connect();
        $this->adapter = new Sqlite($this->connection);
    }

    public function testAddPrimaryKeyIsNoOp(): void
    {
        $this->adapter->createTable('lite_addpk_test', '', [
            'columns' => [new Column('id', ['type' => Column::TYPE_INTEGER, 'notNull' => true])],
        ]);

        $this->adapter->addPrimaryKey('lite_addpk_test', '', new Index('PRIMARY', ['id'], 'PRIMARY'));

        $this->assertTrue($this->adapter->tableExists('lite_addpk_test'));
    }

    public function testCreateTableWithAutoIncrementPrimaryColumn(): void
    {
        $this->adapter->createTable('lite_autopk_test', '', [
            'columns' => [
                new Column('id', [
                    'type'          => Column::TYPE_INTEGER,
                    'notNull'       => true,
                    'primary'       => true,
                    'autoIncrement' => true,
                ]),
            ],
            'indexes' => [new Index('PRIMARY', ['id'], 'PRIMARY')],
        ]);

        $sql = $this->getTableSql('lite_autopk_test');

        $this->assertStringContainsString('PRIMARY KEY AUTOINCREMENT', $sql);
    }

    public function testCreateTableWithCurrentTimestampDefault(): void
    {
        $this->adapter->createTable('lite_ts_test', '', [
            'columns' => [
                new Column('id', ['type' => Column::TYPE_INTEGER, 'notNull' => true]),
                new Column('created_at', [
                    'type'    => Column::TYPE_TIMESTAMP,
                    'notNull' => true,
                    'default' => 'current_timestamp()',
                ]),
            ],
        ]);

        $this->connection->execute('INSERT INTO lite_ts_test (id) VALUES (1)');
        $value = (string) $this->connection->fetchValue('SELECT created_at FROM lite_ts_test');

        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $value);
    }

    public function testCreateTableWithPrimaryIndex(): void
    {
        $this->adapter->createTable('lite_pk_test', '', [
            'columns' => [
                new Column('id', [
                    'type'          => Column::TYPE_INTEGER,
                    'notNull'       => true,
                    'autoIncrement' => true,
                ]),
            ],
            'indexes' => [new Index('PRIMARY', ['id'], 'PRIMARY')],
        ]);

        $this->assertStringContainsString('PRIMARY KEY', $this->getTableSql('lite_pk_test'));
    }

    public function testListIndexesExcludesInternalIndexes(): void
    {
        $this->adapter->createTable('lite_idx_test', '', [
            'columns' => [
                new Column('code', ['type' => Column::TYPE_VARCHAR, 'size' => 10, 'notNull' => true]),
            ],
            'indexes' => [new Index('PRIMARY', ['code'], 'PRIMARY')],
        ]);
        $this->adapter->addIndex('lite_idx_test', '', new Index('lite_idx_code', ['code'], ''));

        $indexes = $this->adapter->listIndexes('', 'lite_idx_test');

        $this->assertArrayHasKey('lite_idx_code', $indexes);
        $this->assertCount(1, $indexes);
    }

    public function testTableExists(): void
    {
        $this->adapter->createTable('lite_exists_test', '', [
            'columns' => [new Column('id', ['type' => Column::TYPE_INTEGER, 'notNull' => true])],
        ]);

        $this->assertTrue($this->adapter->tableExists('lite_exists_test'));
        $this->assertTrue($this->adapter->tableExists('lite_exists_test', 'main'));
        $this->assertFalse($this->adapter->tableExists('lite_missing'));
    }

    private function getTableSql(string $table): string
    {
        return (string) $this->connection->fetchValue(
            "SELECT sql FROM sqlite_master WHERE type = 'table' AND name = :table",
            ['table' => $table]
        );
    }
}
