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

use Phalcon\Migrations\Db\Adapter\Postgresql;
use Phalcon\Migrations\Db\Column;
use Phalcon\Migrations\Db\Connection;
use Phalcon\Migrations\Db\Index;
use Phalcon\Migrations\Db\Reference;
use Phalcon\Migrations\Tests\AbstractPostgresqlTestCase;

final class AdapterTest extends AbstractPostgresqlTestCase
{
    private Connection $connection;
    private Postgresql $adapter;
    private string $schema;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = Connection::fromConfig(static::getMigrationsConfig());
        $this->connection->connect();
        $this->adapter    = new Postgresql($this->connection);
        $this->schema     = static::$defaultSchema;
    }

    public function testGetCurrentSchema(): void
    {
        $schema = $this->adapter->getCurrentSchema();

        $this->assertSame($this->schema, $schema);
    }

    public function testListTables(): void
    {
        $this->adapter->createTable('pg_list_test', $this->schema, [
            'columns' => [new Column('id', ['type' => Column::TYPE_INTEGER, 'notNull' => true])],
        ]);

        $tables = $this->adapter->listTables($this->schema);

        $this->assertContains('pg_list_test', $tables);
    }

    public function testTableExists(): void
    {
        $this->adapter->createTable('pg_exists_test', $this->schema, [
            'columns' => [new Column('id', ['type' => Column::TYPE_INTEGER, 'notNull' => true])],
        ]);

        $this->assertTrue($this->adapter->tableExists('pg_exists_test', $this->schema));
        $this->assertFalse($this->adapter->tableExists('pg_missing', $this->schema));
    }

    public function testListColumns(): void
    {
        $this->adapter->createTable('pg_cols_test', $this->schema, [
            'columns' => [
                new Column('id', ['type' => Column::TYPE_INTEGER, 'notNull' => true, 'first' => true]),
                new Column('name', ['type' => Column::TYPE_VARCHAR, 'size' => 100, 'notNull' => false]),
            ],
        ]);

        $columns = $this->adapter->listColumns($this->schema, 'pg_cols_test');

        $this->assertArrayHasKey('id', $columns);
        $this->assertArrayHasKey('name', $columns);
    }

    public function testListIndexes(): void
    {
        $this->adapter->createTable('pg_idx_test', $this->schema, [
            'columns' => [
                new Column('id', ['type' => Column::TYPE_INTEGER, 'notNull' => true, 'first' => true]),
            ],
            'indexes' => [
                new Index('pg_idx_test_pkey', ['id'], Index::TYPE_PRIMARY),
            ],
        ]);

        $indexes = $this->adapter->listIndexes($this->schema, 'pg_idx_test');

        $this->assertNotEmpty($indexes);
    }

    public function testListReferences(): void
    {
        $this->adapter->createTable('pg_ref_parent', $this->schema, [
            'columns' => [
                new Column('id', [
                    'type'    => Column::TYPE_INTEGER,
                    'notNull' => true,
                    'primary' => true,
                    'first'   => true,
                ]),
            ],
            'indexes' => [
                new Index('pg_ref_parent_pkey', ['id'], Index::TYPE_PRIMARY),
            ],
        ]);

        $this->adapter->createTable('pg_ref_child', $this->schema, [
            'columns' => [
                new Column('id', ['type' => Column::TYPE_INTEGER, 'notNull' => true, 'first' => true]),
                new Column('parent_id', ['type' => Column::TYPE_INTEGER, 'notNull' => true]),
            ],
            'references' => [
                new Reference('fk_pg_ref', [
                    'referencedSchema'  => $this->schema,
                    'referencedTable'   => 'pg_ref_parent',
                    'columns'           => ['parent_id'],
                    'referencedColumns' => ['id'],
                    'onUpdate'          => 'NO ACTION',
                    'onDelete'          => 'NO ACTION',
                ]),
            ],
        ]);

        $refs = $this->adapter->listReferences($this->schema, 'pg_ref_child');

        $this->assertArrayHasKey('fk_pg_ref', $refs);
    }

    public function testGetTableOptionsReturnsEmptyArray(): void
    {
        $this->adapter->createTable('pg_opts_test', $this->schema, [
            'columns' => [new Column('id', ['type' => Column::TYPE_INTEGER, 'notNull' => true])],
        ]);

        $options = $this->adapter->getTableOptions($this->schema, 'pg_opts_test');

        $this->assertSame([], $options);
    }

    public function testModifyColumn(): void
    {
        $this->adapter->createTable('pg_modify_test', $this->schema, [
            'columns' => [
                new Column('id', ['type' => Column::TYPE_INTEGER, 'notNull' => true, 'first' => true]),
            ],
        ]);

        $old = new Column('id', ['type' => Column::TYPE_INTEGER, 'notNull' => true, 'first' => true]);
        $new = new Column('id', ['type' => Column::TYPE_BIGINTEGER, 'notNull' => true, 'first' => true]);

        $this->adapter->modifyColumn('pg_modify_test', $this->schema, $new, $old);

        $columns = $this->adapter->listColumns($this->schema, 'pg_modify_test');
        $this->assertArrayHasKey('id', $columns);
        $this->assertSame(Column::TYPE_BIGINTEGER, $columns['id']->getType());
    }

    public function testDropIndex(): void
    {
        $this->adapter->createTable('pg_dropidx_test', $this->schema, [
            'columns' => [
                new Column('id', ['type' => Column::TYPE_INTEGER, 'notNull' => true, 'first' => true]),
                new Column('name', ['type' => Column::TYPE_VARCHAR, 'size' => 100]),
            ],
        ]);

        $this->adapter->addIndex('pg_dropidx_test', '', new Index('pg_dropidx_idx', ['name'], ''));

        $this->adapter->dropIndex('pg_dropidx_test', '', 'pg_dropidx_idx');

        $indexes = $this->adapter->listIndexes($this->schema, 'pg_dropidx_test');
        $this->assertArrayNotHasKey('pg_dropidx_idx', $indexes);
    }

    public function testDropPrimaryKey(): void
    {
        $this->adapter->createTable('pg_droppk_test', $this->schema, [
            'columns' => [
                new Column('id', ['type' => Column::TYPE_INTEGER, 'notNull' => true, 'first' => true]),
            ],
            'indexes' => [
                new Index('pg_droppk_test_pkey', ['id'], Index::TYPE_PRIMARY),
            ],
        ]);

        $this->adapter->dropPrimaryKey('pg_droppk_test', $this->schema);

        $indexes = $this->adapter->listIndexes($this->schema, 'pg_droppk_test');
        $pkeys   = array_filter($indexes, fn($i) => $i->getType() === Index::TYPE_PRIMARY);
        $this->assertCount(0, $pkeys);
    }

    public function testAddAndDropForeignKey(): void
    {
        $this->adapter->createTable('pg_fk_parent', $this->schema, [
            'columns' => [
                new Column('id', [
                    'type'    => Column::TYPE_INTEGER,
                    'notNull' => true,
                    'primary' => true,
                    'first'   => true,
                ]),
            ],
            'indexes' => [
                new Index('pg_fk_parent_pkey', ['id'], Index::TYPE_PRIMARY),
            ],
        ]);

        $this->adapter->createTable('pg_fk_child', $this->schema, [
            'columns' => [
                new Column('id', ['type' => Column::TYPE_INTEGER, 'notNull' => true, 'first' => true]),
                new Column('parent_id', ['type' => Column::TYPE_INTEGER, 'notNull' => true]),
            ],
        ]);

        $ref = new Reference('fk_pg_add', [
            'referencedSchema'  => $this->schema,
            'referencedTable'   => 'pg_fk_parent',
            'columns'           => ['parent_id'],
            'referencedColumns' => ['id'],
            'onUpdate'          => 'NO ACTION',
            'onDelete'          => 'NO ACTION',
        ]);

        $this->adapter->addForeignKey('pg_fk_child', $this->schema, $ref);
        $refs = $this->adapter->listReferences($this->schema, 'pg_fk_child');
        $this->assertArrayHasKey('fk_pg_add', $refs);

        $this->adapter->dropForeignKey('pg_fk_child', $this->schema, 'fk_pg_add');
        $refs = $this->adapter->listReferences($this->schema, 'pg_fk_child');
        $this->assertArrayNotHasKey('fk_pg_add', $refs);
    }

    public function testAddAndDropColumn(): void
    {
        $this->adapter->createTable('pg_colops_test', $this->schema, [
            'columns' => [
                new Column('id', ['type' => Column::TYPE_INTEGER, 'notNull' => true, 'first' => true]),
            ],
        ]);

        $this->adapter->addColumn(
            'pg_colops_test',
            $this->schema,
            new Column('extra', ['type' => Column::TYPE_VARCHAR, 'size' => 50])
        );
        $cols = $this->adapter->listColumns($this->schema, 'pg_colops_test');
        $this->assertArrayHasKey('extra', $cols);

        $this->adapter->dropColumn('pg_colops_test', $this->schema, 'extra');
        $cols = $this->adapter->listColumns($this->schema, 'pg_colops_test');
        $this->assertArrayNotHasKey('extra', $cols);
    }

    public function testBeginCommitRollback(): void
    {
        $this->adapter->createTable('pg_tx_test', $this->schema, [
            'columns' => [new Column('v', ['type' => Column::TYPE_INTEGER])],
        ]);

        $this->adapter->begin();
        $this->adapter->execute('INSERT INTO pg_tx_test VALUES (1)');
        $this->adapter->commit();

        $row = $this->adapter->fetchOne('SELECT v FROM pg_tx_test');
        $this->assertSame('1', (string) $row['v']);

        $this->adapter->begin();
        $this->adapter->execute('INSERT INTO pg_tx_test VALUES (2)');
        $this->adapter->rollback();

        $rows = $this->adapter->fetchAll('SELECT v FROM pg_tx_test');
        $this->assertCount(1, $rows);
    }

    public function testModifyColumnWithDefaultChange(): void
    {
        $this->adapter->createTable('pg_moddef_test', $this->schema, [
            'columns' => [
                new Column('id', ['type' => Column::TYPE_INTEGER, 'notNull' => true, 'first' => true]),
                new Column('name', ['type' => Column::TYPE_VARCHAR, 'size' => 100]),
            ],
        ]);

        $old = new Column('name', ['type' => Column::TYPE_VARCHAR, 'size' => 100]);
        $new = new Column('name', ['type' => Column::TYPE_VARCHAR, 'size' => 100, 'default' => 'test']);

        $this->adapter->modifyColumn('pg_moddef_test', $this->schema, $new, $old);

        $cols = $this->adapter->listColumns($this->schema, 'pg_moddef_test');
        $this->assertArrayHasKey('name', $cols);
    }

    public function testModifyColumnNullDefault(): void
    {
        $this->adapter->createTable('pg_modnull_test', $this->schema, [
            'columns' => [
                new Column('id', ['type' => Column::TYPE_INTEGER, 'notNull' => true, 'first' => true]),
                new Column('name', ['type' => Column::TYPE_VARCHAR, 'size' => 100, 'default' => 'old']),
            ],
        ]);

        $old = new Column('name', ['type' => Column::TYPE_VARCHAR, 'size' => 100, 'default' => 'old']);
        $new = new Column('name', ['type' => Column::TYPE_VARCHAR, 'size' => 100, 'default' => null]);

        $this->adapter->modifyColumn('pg_modnull_test', $this->schema, $new, $old);

        $cols = $this->adapter->listColumns($this->schema, 'pg_modnull_test');
        $this->assertArrayHasKey('name', $cols);
    }

    public function testModifyColumnNotNullChange(): void
    {
        $this->adapter->createTable('pg_modnn_test', $this->schema, [
            'columns' => [
                new Column('id', ['type' => Column::TYPE_INTEGER, 'notNull' => true, 'first' => true]),
                new Column('name', ['type' => Column::TYPE_VARCHAR, 'size' => 100, 'notNull' => false]),
            ],
        ]);

        $old = new Column('name', ['type' => Column::TYPE_VARCHAR, 'size' => 100, 'notNull' => false]);
        $new = new Column('name', ['type' => Column::TYPE_VARCHAR, 'size' => 100, 'notNull' => true]);

        $this->adapter->modifyColumn('pg_modnn_test', $this->schema, $new, $old);

        $cols = $this->adapter->listColumns($this->schema, 'pg_modnn_test');
        $this->assertArrayHasKey('name', $cols);
        $this->assertTrue($cols['name']->isNotNull());
    }

    public function testModifyColumnRename(): void
    {
        $this->adapter->createTable('pg_rename_test', $this->schema, [
            'columns' => [
                new Column('id', ['type' => Column::TYPE_INTEGER, 'notNull' => true, 'first' => true]),
                new Column('old_name', ['type' => Column::TYPE_VARCHAR, 'size' => 100]),
            ],
        ]);

        $old = new Column('old_name', ['type' => Column::TYPE_VARCHAR, 'size' => 100]);
        $new = new Column('new_name', ['type' => Column::TYPE_VARCHAR, 'size' => 100]);

        $this->adapter->modifyColumn('pg_rename_test', $this->schema, $new, $old);

        $cols = $this->adapter->listColumns($this->schema, 'pg_rename_test');
        $this->assertArrayHasKey('new_name', $cols);
        $this->assertArrayNotHasKey('old_name', $cols);
    }
}
