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

use Phalcon\Migrations\Db\Adapter\Mysql;
use Phalcon\Migrations\Db\Column;
use Phalcon\Migrations\Db\Connection;
use Phalcon\Migrations\Db\Index;
use Phalcon\Migrations\Db\Reference;
use Phalcon\Migrations\Tests\AbstractMysqlTestCase;

final class AdapterTest extends AbstractMysqlTestCase
{
    private Connection $connection;
    private Mysql $adapter;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = Connection::fromConfig(static::getMigrationsConfig());
        $this->connection->connect();
        $this->adapter    = new Mysql($this->connection);
    }

    public function testGetConnection(): void
    {
        $this->assertSame($this->connection, $this->adapter->getConnection());
    }

    public function testGetCurrentSchema(): void
    {
        $schema = $this->adapter->getCurrentSchema();

        $this->assertSame($_ENV['MYSQL_TEST_DB_DATABASE'], $schema);
    }

    public function testListTables(): void
    {
        $schema = $_ENV['MYSQL_TEST_DB_DATABASE'];

        $this->adapter->createTable('adapter_list_test', $schema, [
            'columns' => [new Column('id', ['type' => Column::TYPE_INTEGER, 'notNull' => true])],
        ]);

        $tables = $this->adapter->listTables($schema);

        $this->assertContains('adapter_list_test', $tables);
    }

    public function testTableExistsReturnsTrueForExistingTable(): void
    {
        $schema = $_ENV['MYSQL_TEST_DB_DATABASE'];

        $this->adapter->createTable('adapter_exists_test', $schema, [
            'columns' => [new Column('id', ['type' => Column::TYPE_INTEGER, 'notNull' => true])],
        ]);

        $this->assertTrue($this->adapter->tableExists('adapter_exists_test', $schema));
        $this->assertFalse($this->adapter->tableExists('adapter_missing_table', $schema));
    }

    public function testListColumns(): void
    {
        $schema = $_ENV['MYSQL_TEST_DB_DATABASE'];

        $this->adapter->createTable('adapter_cols_test', $schema, [
            'columns' => [
                new Column('id', ['type' => Column::TYPE_INTEGER, 'size' => 11, 'notNull' => true, 'first' => true]),
                new Column('name', ['type' => Column::TYPE_VARCHAR, 'size' => 100, 'notNull' => false]),
            ],
            'indexes' => [
                new Index('PRIMARY', ['id'], Index::TYPE_PRIMARY),
            ],
        ]);

        $columns = $this->adapter->listColumns($schema, 'adapter_cols_test');

        $this->assertArrayHasKey('id', $columns);
        $this->assertArrayHasKey('name', $columns);
    }

    public function testListIndexes(): void
    {
        $schema = $_ENV['MYSQL_TEST_DB_DATABASE'];

        $this->adapter->createTable('adapter_idx_test', $schema, [
            'columns' => [
                new Column('id', ['type' => Column::TYPE_INTEGER, 'size' => 11, 'notNull' => true, 'first' => true]),
            ],
            'indexes' => [
                new Index('PRIMARY', ['id'], Index::TYPE_PRIMARY),
            ],
        ]);

        $indexes = $this->adapter->listIndexes($schema, 'adapter_idx_test');

        $this->assertArrayHasKey('PRIMARY', $indexes);
    }

    public function testListReferences(): void
    {
        $schema = $_ENV['MYSQL_TEST_DB_DATABASE'];

        $this->adapter->createTable('adapter_ref_parent', $schema, [
            'columns' => [
                new Column('id', ['type' => Column::TYPE_INTEGER, 'size' => 11, 'notNull' => true, 'first' => true]),
            ],
            'indexes' => [
                new Index('PRIMARY', ['id'], Index::TYPE_PRIMARY),
            ],
        ]);

        $this->adapter->createTable('adapter_ref_child', $schema, [
            'columns' => [
                new Column('id', ['type' => Column::TYPE_INTEGER, 'size' => 11, 'notNull' => true, 'first' => true]),
                new Column('parent_id', ['type' => Column::TYPE_INTEGER, 'size' => 11, 'notNull' => true]),
            ],
            'references' => [
                new Reference('fk_adapter_ref', [
                    'referencedSchema'  => $schema,
                    'referencedTable'   => 'adapter_ref_parent',
                    'columns'           => ['parent_id'],
                    'referencedColumns' => ['id'],
                    'onUpdate'          => 'NO ACTION',
                    'onDelete'          => 'NO ACTION',
                ]),
            ],
        ]);

        $refs = $this->adapter->listReferences($schema, 'adapter_ref_child');

        $this->assertArrayHasKey('fk_adapter_ref', $refs);
    }

    public function testGetTableOptions(): void
    {
        $schema = $_ENV['MYSQL_TEST_DB_DATABASE'];

        $this->adapter->createTable('adapter_opts_test', $schema, [
            'columns' => [new Column('id', ['type' => Column::TYPE_INTEGER, 'notNull' => true])],
        ]);

        $options = $this->adapter->getTableOptions($schema, 'adapter_opts_test');

        $this->assertIsArray($options);
    }

    public function testModifyColumn(): void
    {
        $schema = $_ENV['MYSQL_TEST_DB_DATABASE'];
        $table  = 'adapter_modify_test';

        $this->adapter->createTable($table, $schema, [
            'columns' => [
                new Column('id', ['type' => Column::TYPE_INTEGER, 'size' => 11, 'notNull' => true, 'first' => true]),
            ],
        ]);

        $old = new Column('id', ['type' => Column::TYPE_INTEGER, 'size' => 11, 'notNull' => true, 'first' => true]);
        $new = new Column('id', ['type' => Column::TYPE_INTEGER, 'size' => 20, 'notNull' => true, 'first' => true]);

        $this->adapter->modifyColumn($table, $schema, $new, $old);

        $columns = $this->adapter->listColumns($schema, $table);
        $this->assertArrayHasKey('id', $columns);
    }

    public function testDropIndex(): void
    {
        $schema = $_ENV['MYSQL_TEST_DB_DATABASE'];
        $table  = 'adapter_drop_idx_test';

        $this->adapter->createTable($table, $schema, [
            'columns' => [
                new Column('id', ['type' => Column::TYPE_INTEGER, 'size' => 11, 'notNull' => true, 'first' => true]),
                new Column('name', ['type' => Column::TYPE_VARCHAR, 'size' => 100, 'notNull' => false]),
            ],
        ]);

        $this->adapter->addIndex($table, $schema, new Index('idx_name', ['name'], ''));
        $this->adapter->dropIndex($table, $schema, 'idx_name');

        $indexes = $this->adapter->listIndexes($schema, $table);
        $this->assertArrayNotHasKey('idx_name', $indexes);
    }

    public function testDropPrimaryKey(): void
    {
        $schema = $_ENV['MYSQL_TEST_DB_DATABASE'];
        $table  = 'adapter_drop_pk_test';

        $this->adapter->createTable($table, $schema, [
            'columns' => [
                new Column('id', ['type' => Column::TYPE_INTEGER, 'size' => 11, 'notNull' => true, 'first' => true]),
            ],
        ]);

        $this->adapter->addPrimaryKey($table, $schema, new Index('PRIMARY', ['id'], Index::TYPE_PRIMARY));
        $this->adapter->dropPrimaryKey($table, $schema);

        $indexes = $this->adapter->listIndexes($schema, $table);
        $this->assertArrayNotHasKey('PRIMARY', $indexes);
    }

    public function testAddAndDropColumn(): void
    {
        $schema = $_ENV['MYSQL_TEST_DB_DATABASE'];
        $table  = 'adapter_col_ops_test';

        $this->adapter->createTable($table, $schema, [
            'columns' => [
                new Column('id', ['type' => Column::TYPE_INTEGER, 'size' => 11, 'notNull' => true, 'first' => true]),
            ],
        ]);

        $this->adapter->addColumn($table, $schema, new Column('extra', ['type' => Column::TYPE_VARCHAR, 'size' => 50]));
        $cols = $this->adapter->listColumns($schema, $table);
        $this->assertArrayHasKey('extra', $cols);

        $this->adapter->dropColumn($table, $schema, 'extra');
        $cols = $this->adapter->listColumns($schema, $table);
        $this->assertArrayNotHasKey('extra', $cols);
    }

    public function testAddAndDropForeignKey(): void
    {
        $schema = $_ENV['MYSQL_TEST_DB_DATABASE'];

        $this->adapter->createTable('adapter_fk_parent', $schema, [
            'columns' => [
                new Column('id', ['type' => Column::TYPE_INTEGER, 'size' => 11, 'notNull' => true, 'first' => true]),
            ],
            'indexes' => [
                new Index('PRIMARY', ['id'], Index::TYPE_PRIMARY),
            ],
        ]);

        $this->adapter->createTable('adapter_fk_child', $schema, [
            'columns' => [
                new Column('id', ['type' => Column::TYPE_INTEGER, 'size' => 11, 'notNull' => true, 'first' => true]),
                new Column('parent_id', ['type' => Column::TYPE_INTEGER, 'size' => 11, 'notNull' => true]),
            ],
        ]);

        $ref = new Reference('fk_test_add', [
            'referencedSchema'  => $schema,
            'referencedTable'   => 'adapter_fk_parent',
            'columns'           => ['parent_id'],
            'referencedColumns' => ['id'],
            'onUpdate'          => 'NO ACTION',
            'onDelete'          => 'NO ACTION',
        ]);

        $this->adapter->addForeignKey('adapter_fk_child', $schema, $ref);
        $refs = $this->adapter->listReferences($schema, 'adapter_fk_child');
        $this->assertArrayHasKey('fk_test_add', $refs);

        $this->adapter->dropForeignKey('adapter_fk_child', $schema, 'fk_test_add');
        $refs = $this->adapter->listReferences($schema, 'adapter_fk_child');
        $this->assertArrayNotHasKey('fk_test_add', $refs);
    }

    public function testCreateTableWithIndexesAndReferences(): void
    {
        $schema = $_ENV['MYSQL_TEST_DB_DATABASE'];

        $this->adapter->createTable('adapter_full_test', $schema, [
            'columns' => [
                new Column('id', ['type' => Column::TYPE_INTEGER, 'size' => 11, 'notNull' => true, 'first' => true]),
                new Column('name', ['type' => Column::TYPE_VARCHAR, 'size' => 100, 'notNull' => true]),
            ],
            'indexes' => [
                new Index('idx_name', ['name'], ''),
            ],
            'options' => ['ENGINE' => 'InnoDB'],
        ]);

        $tables = $this->adapter->listTables($schema);
        $this->assertContains('adapter_full_test', $tables);
    }

    public function testBeginCommitRollback(): void
    {
        $schema = $_ENV['MYSQL_TEST_DB_DATABASE'];

        $this->adapter->createTable('adapter_tx_test', $schema, [
            'columns' => [new Column('v', ['type' => Column::TYPE_INTEGER])],
        ]);

        $this->adapter->begin();
        $this->adapter->execute('INSERT INTO `adapter_tx_test` VALUES (1)');
        $this->adapter->commit();

        $row = $this->adapter->fetchOne('SELECT v FROM `adapter_tx_test`');
        $this->assertSame('1', (string) $row['v']);

        $this->adapter->begin();
        $this->adapter->execute('INSERT INTO `adapter_tx_test` VALUES (2)');
        $this->adapter->rollback();

        $rows = $this->adapter->fetchAll('SELECT v FROM `adapter_tx_test`');
        $this->assertCount(1, $rows);
    }

    public function testQuote(): void
    {
        $result = $this->adapter->quote('hello');

        $this->assertStringContainsString('hello', $result);
    }
}
