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
    private Mysql $adapter;
    private Connection $connection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->connection = Connection::fromConfig(static::getMigrationsConfig());
        $this->connection->connect();
        $this->adapter    = new Mysql($this->connection);
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

    /**
     * MariaDB and MySQL before 8.0.19 give the display width in column_type
     * (int(11)). MySQL 8.0.19+ does not (int). information_schema gives the
     * numeric precision as the size, which is not the display width.
     *
     * @see https://github.com/phalcon/migrations/issues/139
     */
    public function testBuildColumnUsesDisplayWidthOfInteger(): void
    {
        $adapter = new class ($this->connection) extends Mysql {
            public function column(string $type, string $extended, ?int $size, ?int $scale): Column
            {
                return $this->buildColumn(
                    [
                        'name'          => 'col',
                        'type'          => $type,
                        'size'          => $size,
                        'numeric_scale' => $scale,
                        'extended'      => $extended,
                    ],
                    true,
                    null
                );
            }
        };

        $this->assertSame(11, $adapter->column('int', 'int(11)', 10, 0)->getSize());
        $this->assertSame(20, $adapter->column('bigint', 'bigint(20) unsigned', 20, 0)->getSize());
        $this->assertNull($adapter->column('int', 'int', 10, 0)->getSize());
        $this->assertNull($adapter->column('bigint', 'bigint unsigned', 20, 0)->getSize());

        $boolean = $adapter->column('tinyint', 'tinyint(1)', 3, 0);
        $this->assertSame(Column::TYPE_BOOLEAN, $boolean->getType());
        $this->assertNull($boolean->getSize());

        $decimal = $adapter->column('decimal', 'decimal(10,2)', 10, 2);
        $this->assertSame(10, $decimal->getSize());
        $this->assertSame(2, $decimal->getScale());

        $this->assertSame(20, $adapter->column('varchar', 'varchar(20)', 20, null)->getSize());
    }

    /**
     * @see https://github.com/phalcon/migrations/issues/218
     */
    public function testCreateTableWithBooleanSize(): void
    {
        $schema = $_ENV['MYSQL_TEST_DB_DATABASE'];

        $this->adapter->createTable('adapter_bool_test', $schema, [
            'columns' => [
                new Column('active', ['type' => Column::TYPE_BOOLEAN, 'size' => 3, 'notNull' => true]),
            ],
        ]);

        $column = $this->adapter->listColumns($schema, 'adapter_bool_test')['active'];
        $row    = $this->adapter->fetchOne(
            "SELECT column_type AS t FROM information_schema.columns
             WHERE table_schema = :schema AND table_name = 'adapter_bool_test' AND column_name = 'active'",
            ['schema' => $schema]
        );

        $this->assertSame(Column::TYPE_BOOLEAN, $column->getType());
        $this->assertSame('tinyint(1)', $row['t']);
    }

    public function testCreateTableWithCurrentTimestampDefault(): void
    {
        $schema = $_ENV['MYSQL_TEST_DB_DATABASE'];

        $this->adapter->createTable('adapter_ts_default_test', $schema, [
            'columns' => [
                new Column('id', ['type' => Column::TYPE_INTEGER, 'size' => 11, 'notNull' => true, 'first' => true]),
                new Column('created_at', [
                    'type'    => Column::TYPE_TIMESTAMP,
                    'notNull' => true,
                    'default' => 'current_timestamp()',
                ]),
            ],
        ]);

        $this->adapter->execute('INSERT INTO `adapter_ts_default_test` (id) VALUES (1)');
        $row = $this->adapter->fetchOne('SELECT created_at FROM `adapter_ts_default_test`');

        $this->assertMatchesRegularExpression(
            '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}/',
            (string) $row['created_at']
        );
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

    public function testGetConnection(): void
    {
        $this->assertSame($this->connection, $this->adapter->getConnection());
    }

    public function testGetCurrentSchema(): void
    {
        $schema = $this->adapter->getCurrentSchema();

        $this->assertSame($_ENV['MYSQL_TEST_DB_DATABASE'], $schema);
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

    /**
     * @see https://github.com/phalcon/migrations/issues/139
     */
    public function testListColumnsIntegerSizes(): void
    {
        $schema = $_ENV['MYSQL_TEST_DB_DATABASE'];

        $this->adapter->execute(
            'CREATE TABLE `adapter_int_sizes_test` (
                `c_big` BIGINT UNSIGNED NOT NULL,
                `c_int` INT NOT NULL,
                `c_bool` TINYINT(1) NOT NULL,
                `c_dec` DECIMAL(10,2) NOT NULL,
                `c_name` VARCHAR(20) NOT NULL
            )'
        );

        $columns = $this->adapter->listColumns($schema, 'adapter_int_sizes_test');
        $types   = array_column(
            $this->adapter->fetchAll(
                "SELECT column_name AS n, column_type AS t FROM information_schema.columns
                 WHERE table_schema = :schema AND table_name = 'adapter_int_sizes_test'",
                ['schema' => $schema]
            ),
            't',
            'n'
        );
        // The display width in column_type, if the server gives one
        $width = static fn(string $name): ?int => preg_match('/^[a-z]+\((\d+)\)/', $types[$name], $m) === 1
            ? (int) $m[1]
            : null;

        $this->assertSame($width('c_big'), $columns['c_big']->getSize());
        $this->assertSame($width('c_int'), $columns['c_int']->getSize());
        $this->assertSame(Column::TYPE_BOOLEAN, $columns['c_bool']->getType());
        $this->assertNull($columns['c_bool']->getSize());
        $this->assertSame(10, $columns['c_dec']->getSize());
        $this->assertSame(2, $columns['c_dec']->getScale());
        $this->assertSame(20, $columns['c_name']->getSize());
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

    public function testListTables(): void
    {
        $schema = $_ENV['MYSQL_TEST_DB_DATABASE'];

        $this->adapter->createTable('adapter_list_test', $schema, [
            'columns' => [new Column('id', ['type' => Column::TYPE_INTEGER, 'notNull' => true])],
        ]);

        $tables = $this->adapter->listTables($schema);

        $this->assertContains('adapter_list_test', $tables);
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

    public function testQuote(): void
    {
        $result = $this->adapter->quote('hello');

        $this->assertStringContainsString('hello', $result);
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
}
