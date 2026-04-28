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

use Phalcon\Migrations\Db\Column;
use Phalcon\Migrations\Exception\RuntimeException;
use Phalcon\Migrations\Migrations;
use Phalcon\Migrations\Mvc\Model\Migration;
use Phalcon\Migrations\Tests\AbstractMysqlTestCase;
use Phalcon\Migrations\Tests\Fakes\Db\FakeColumn as PhalconColumn;
use Phalcon\Migrations\Tests\Fakes\Db\FakeIndex as PhalconIndex;
use Phalcon\Migrations\Tests\Fakes\Mvc\Model\MigrationFake;
use Phalcon\Migrations\Utils\Config;
use Phalcon\Migrations\Version\ItemCollection;

final class MigrationModelTest extends AbstractMysqlTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Migration::setup(static::getMigrationsConfig());
    }

    protected function tearDown(): void
    {
        Migrations::resetStorage();
        parent::tearDown();
    }

    // -------------------------------------------------------------------------
    // resolveDbSchema — pure logic, no query needed
    // -------------------------------------------------------------------------

    public function testResolveDbSchemaReturnsExplicitSchema(): void
    {
        $config = Config::fromArray([
            'database' => ['adapter' => 'mysql', 'dbname' => 'mydb', 'schema' => 'custom'],
        ]);

        $this->assertSame('custom', Migration::resolveDbSchema($config));
    }

    public function testResolveDbSchemaReturnsPublicForPostgresql(): void
    {
        $config = Config::fromArray([
            'database' => ['adapter' => 'postgresql', 'dbname' => 'mydb'],
        ]);

        $this->assertSame('public', Migration::resolveDbSchema($config));
    }

    public function testResolveDbSchemaReturnsNullForSqlite(): void
    {
        $config = Config::fromArray([
            'database' => ['adapter' => 'sqlite', 'dbname' => 'mydb'],
        ]);

        $this->assertNull(Migration::resolveDbSchema($config));
    }

    public function testResolveDbSchemaReturnsDbnameForMysql(): void
    {
        $config = Config::fromArray([
            'database' => ['adapter' => 'mysql', 'dbname' => 'app_db'],
        ]);

        $this->assertSame('app_db', Migration::resolveDbSchema($config));
    }

    // -------------------------------------------------------------------------
    // setup()
    // -------------------------------------------------------------------------

    public function testSetupThrowsWhenAdapterIsNull(): void
    {
        $config = Config::fromArray([
            'database' => ['dbname' => 'test'],
        ]);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Unspecified database Adapter');

        Migration::setup($config);
    }

    // -------------------------------------------------------------------------
    // Static setters / getters
    // -------------------------------------------------------------------------

    public function testSetSkipAutoIncrement(): void
    {
        Migration::setSkipAutoIncrement(false);
        Migration::setSkipAutoIncrement(true);

        $this->assertTrue(true);
    }

    public function testSetMigrationPath(): void
    {
        Migration::setMigrationPath('/some/path/');

        $this->assertTrue(true);
    }

    public function testGetAdapterReturnsAdapter(): void
    {
        $adapter = Migration::getAdapter();

        $this->assertNotNull($adapter);
    }

    public function testGetSchemaReturnsDatabaseName(): void
    {
        $schema = Migration::getSchema();

        $this->assertSame($_ENV['MYSQL_TEST_DB_DATABASE'], $schema);
    }

    public function testGetConnectionReturnsAdapter(): void
    {
        $fake = new MigrationFake();

        $this->assertSame(Migration::getAdapter(), $fake->getConnection());
    }

    // -------------------------------------------------------------------------
    // scanForVersions()
    // -------------------------------------------------------------------------

    public function testScanForVersionsReturnsEmptyForEmptyDir(): void
    {
        $dir = $this->getOutputDir('scan-versions-empty');

        $versions = Migration::scanForVersions($dir);

        $this->assertSame([], $versions);
    }

    public function testScanForVersionsDetectsIncrementalVersions(): void
    {
        $dir = $this->getOutputDir('scan-versions');
        mkdir($dir . '/1.0.0', 0755);
        mkdir($dir . '/1.0.1', 0755);
        mkdir($dir . '/not-a-version', 0755);

        ItemCollection::setType(ItemCollection::TYPE_INCREMENTAL);
        $versions = Migration::scanForVersions($dir);

        $this->assertCount(2, $versions);
        $versionStrings = array_map(fn($v) => $v->getVersion(), $versions);
        $this->assertContains('1.0.0', $versionStrings);
        $this->assertContains('1.0.1', $versionStrings);
        $this->assertNotContains('not-a-version', $versionStrings);
    }

    public function testScanForVersionsIgnoresFiles(): void
    {
        $dir = $this->getOutputDir('scan-versions-files');
        mkdir($dir . '/1.0.0', 0755);
        touch($dir . '/1.0.1');

        ItemCollection::setType(ItemCollection::TYPE_INCREMENTAL);
        $versions = Migration::scanForVersions($dir);

        $this->assertCount(1, $versions);
        $this->assertSame('1.0.0', $versions[0]->getVersion());
    }

    // -------------------------------------------------------------------------
    // morphTable() — new format (Phalcon\Migrations\Db\Column)
    // -------------------------------------------------------------------------

    public function testMorphTableCreatesTableWithNewFormatColumns(): void
    {
        $fake = new MigrationFake();
        $fake->morphTable('mm_new_create', [
            'columns' => [
                new Column('id', [
                    'type'    => Column::TYPE_INTEGER,
                    'size'    => 11,
                    'notNull' => true,
                    'first'   => true,
                ]),
                new Column('name', [
                    'type'    => Column::TYPE_VARCHAR,
                    'size'    => 100,
                    'notNull' => true,
                ]),
            ],
        ]);

        $this->assertTrue($this->getPhalconDb()->tableExists('mm_new_create'));
        $columns = $this->describeColumns('mm_new_create');
        $this->assertCount(2, $columns);
    }

    // -------------------------------------------------------------------------
    // morphTable() — old format (Phalcon\Db\Column) backwards compatibility
    // -------------------------------------------------------------------------

    public function testMorphTableCreatesTableWithOldFormatColumns(): void
    {
        $fake = new MigrationFake();
        $fake->morphTable('mm_old_create', [
            'columns' => [
                new PhalconColumn('id', [
                    'type'    => PhalconColumn::TYPE_INTEGER,
                    'size'    => 11,
                    'notNull' => true,
                    'first'   => true,
                ]),
                new PhalconColumn('username', [
                    'type'    => PhalconColumn::TYPE_VARCHAR,
                    'size'    => 100,
                    'notNull' => true,
                ]),
            ],
            'indexes' => [
                new PhalconIndex('PRIMARY', ['id'], 'PRIMARY'),
            ],
        ]);

        $this->assertTrue($this->getPhalconDb()->tableExists('mm_old_create'));
        $columns = $this->describeColumns('mm_old_create');
        $this->assertCount(2, $columns);
    }

    public function testMorphTableThrowsWhenColumnIsNotAnObject(): void
    {
        $fake = new MigrationFake();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Table must have at least one column');

        $fake->morphTable('mm_invalid', [
            'columns' => ['not-a-column-object'],
        ]);
    }

    public function testMorphTableThrowsWhenNoColumns(): void
    {
        $fake = new MigrationFake();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Table must have at least one column');

        $fake->morphTable('mm_no_cols', [
            'columns' => [],
        ]);
    }

    public function testMorphTableAltersExistingTableWithNewFormatColumns(): void
    {
        $schema = $_ENV['MYSQL_TEST_DB_DATABASE'];
        $table  = 'mm_alter_new';

        $this->getPhalconDb()->createTable($table, $schema, [
            'columns' => [
                new Column('id', [
                    'type'    => Column::TYPE_INTEGER,
                    'size'    => 11,
                    'notNull' => true,
                    'first'   => true,
                ]),
            ],
        ]);

        $fake = new MigrationFake();
        $fake->morphTable($table, [
            'columns' => [
                new Column('id', [
                    'type'    => Column::TYPE_INTEGER,
                    'size'    => 11,
                    'notNull' => true,
                    'first'   => true,
                ]),
                new Column('name', [
                    'type'    => Column::TYPE_VARCHAR,
                    'size'    => 100,
                    'notNull' => true,
                ]),
            ],
        ]);

        $columns = $this->describeColumns($table);
        $this->assertCount(2, $columns);
    }

    public function testMorphTableAltersExistingTableWithOldFormatColumns(): void
    {
        $schema = $_ENV['MYSQL_TEST_DB_DATABASE'];
        $table  = 'mm_alter_old';

        $this->getPhalconDb()->createTable($table, $schema, [
            'columns' => [
                new Column('id', [
                    'type'    => Column::TYPE_INTEGER,
                    'size'    => 11,
                    'notNull' => true,
                    'first'   => true,
                ]),
            ],
        ]);

        $fake = new MigrationFake();
        $fake->morphTable($table, [
            'columns' => [
                new PhalconColumn('id', [
                    'type'    => PhalconColumn::TYPE_INTEGER,
                    'size'    => 11,
                    'notNull' => true,
                    'first'   => true,
                ]),
                new PhalconColumn('name', [
                    'type'    => PhalconColumn::TYPE_VARCHAR,
                    'size'    => 100,
                    'notNull' => true,
                ]),
            ],
        ]);

        $columns = $this->describeColumns($table);
        $this->assertCount(2, $columns);
    }

    // -------------------------------------------------------------------------
    // batchInsert / batchDelete
    // -------------------------------------------------------------------------

    public function testBatchInsertSkipsWhenNoDatFile(): void
    {
        $schema = $_ENV['MYSQL_TEST_DB_DATABASE'];
        $this->getPhalconDb()->createTable('mm_batch_skip', $schema, [
            'columns' => [
                new Column('id', [
                    'type' => Column::TYPE_INTEGER, 'notNull' => true, 'first' => true,
                ]),
            ],
        ]);

        $dir = $this->getOutputDir('mm-batch-insert');
        mkdir($dir . '/1.0.0', 0755);

        Migration::setMigrationPath($dir . '/');
        $fake = new MigrationFake();
        $fake->setVersion('1.0.0');
        $fake->batchInsert('mm_batch_skip', ['id']);

        $this->assertNumRecords(0, 'mm_batch_skip');
    }

    public function testBatchInsertInsertsRowsFromDatFile(): void
    {
        $schema = $_ENV['MYSQL_TEST_DB_DATABASE'];
        $table  = 'mm_batch_insert';

        $this->getPhalconDb()->createTable($table, $schema, [
            'columns' => [
                new Column('id', [
                    'type'    => Column::TYPE_INTEGER,
                    'size'    => 11,
                    'notNull' => true,
                    'first'   => true,
                ]),
                new Column('name', [
                    'type'    => Column::TYPE_VARCHAR,
                    'size'    => 100,
                    'notNull' => true,
                ]),
            ],
        ]);

        $dir = $this->getOutputDir('mm-batch-insert-data');
        mkdir($dir . '/1.0.0', 0755);
        file_put_contents($dir . '/1.0.0/' . $table . '.dat', "1,Alice\n2,Bob\n3,Carol\n");

        Migration::setMigrationPath($dir . '/');
        $fake = new MigrationFake();
        $fake->setVersion('1.0.0');
        $fake->batchInsert($table, ['`id`', '`name`']);

        $this->assertNumRecords(3, $table);
    }

    public function testBatchDeleteSkipsWhenNoDatFile(): void
    {
        $schema = $_ENV['MYSQL_TEST_DB_DATABASE'];
        $this->getPhalconDb()->createTable('mm_batch_del_skip', $schema, [
            'columns' => [
                new Column('id', [
                    'type' => Column::TYPE_INTEGER, 'notNull' => true, 'first' => true,
                ]),
            ],
        ]);

        $dir = $this->getOutputDir('mm-batch-delete-skip');
        mkdir($dir . '/1.0.0', 0755);

        Migration::setMigrationPath($dir . '/');
        $fake = new MigrationFake();
        $fake->setVersion('1.0.0');
        $fake->batchDelete('mm_batch_del_skip');

        $this->assertTrue(true);
    }

    public function testBatchDeleteClearsTable(): void
    {
        $schema = $_ENV['MYSQL_TEST_DB_DATABASE'];
        $table  = 'mm_batch_delete';

        $this->getPhalconDb()->createTable($table, $schema, [
            'columns' => [
                new Column('id', [
                    'type'    => Column::TYPE_INTEGER,
                    'size'    => 11,
                    'notNull' => true,
                    'first'   => true,
                ]),
            ],
        ]);

        $this->getPhalconDb()->execute("INSERT INTO `{$table}` VALUES (1), (2), (3)");

        $dir = $this->getOutputDir('mm-batch-delete-data');
        mkdir($dir . '/1.0.0', 0755);
        file_put_contents($dir . '/1.0.0/' . $table . '.dat', "1\n2\n3\n");

        Migration::setMigrationPath($dir . '/');
        $fake = new MigrationFake();
        $fake->setVersion('1.0.0');
        $fake->batchDelete($table);

        $this->assertNumRecords(0, $table);
    }

    // -------------------------------------------------------------------------
    // Backwards-compatibility integration: full Migrations::run() stack
    // -------------------------------------------------------------------------

    public function testOldFormatMigrationRunsViaIntegration(): void
    {
        $this->silentRun('backcompat/old');

        $this->assertTrue($this->getPhalconDb()->tableExists('bc_old_users'));

        $columns     = $this->describeColumns('bc_old_users');
        $columnNames = array_map(fn($c) => $c->getName(), $columns);

        $this->assertContains('id', $columnNames);
        $this->assertContains('username', $columnNames);
        $this->assertContains('email', $columnNames);
    }

    public function testOldFormatMigrationRunsBothVersions(): void
    {
        $this->silentRun('backcompat/old');

        $columns     = $this->describeColumns('bc_old_users');
        $columnNames = array_map(fn($c) => $c->getName(), $columns);

        $this->assertContains('created_at', $columnNames);
    }

    public function testNewFormatMigrationRunsViaIntegration(): void
    {
        $this->silentRun('backcompat/new');

        $this->assertTrue($this->getPhalconDb()->tableExists('bc_new_users'));

        $columns     = $this->describeColumns('bc_new_users');
        $columnNames = array_map(fn($c) => $c->getName(), $columns);

        $this->assertContains('id', $columnNames);
        $this->assertContains('username', $columnNames);
        $this->assertContains('email', $columnNames);
    }

    public function testNewFormatMigrationRunsBothVersions(): void
    {
        $this->silentRun('backcompat/new');

        $columns     = $this->describeColumns('bc_new_users');
        $columnNames = array_map(fn($c) => $c->getName(), $columns);

        $this->assertContains('created_at', $columnNames);
    }

    public function testOldAndNewFormatProduceSameTableStructure(): void
    {
        $this->silentRun('backcompat/old');

        $oldColumns  = $this->describeColumns('bc_old_users');

        if ($this->getPhalconDb()->tableExists(Migrations::MIGRATION_LOG_TABLE)) {
            $this->getPhalconDb()->dropTable(Migrations::MIGRATION_LOG_TABLE);
        }
        Migrations::resetStorage();

        $this->silentRun('backcompat/new');

        $newColumns  = $this->describeColumns('bc_new_users');

        $oldNames = array_map(fn($c) => $c->getName(), $oldColumns);
        $newNames = array_map(fn($c) => $c->getName(), $newColumns);
        sort($oldNames);
        sort($newNames);

        $this->assertSame($oldNames, $newNames);
    }

    // -------------------------------------------------------------------------
    // Rollback: exercises createPrevClassWithMorphMethod (DIRECTION_BACK)
    // -------------------------------------------------------------------------

    /**
     * Runs old-format migrations forward to v1.0.1 (4 columns), then rolls
     * back to v1.0.0.  createPrevClassWithMorphMethod scans backwards,
     * finds the v1.0.0 morph() and removes the created_at column.
     */
    public function testRollbackOldFormatRestoresTableStructure(): void
    {
        $this->silentRun('backcompat/old');

        $afterForward = $this->describeColumns('bc_old_users');
        $this->assertCount(4, $afterForward);

        ob_start();
        try {
            Migrations::run([
                'migrationsDir'  => $this->getDataDir('backcompat/old'),
                'config'         => static::getMigrationsConfig(),
                'migrationsInDb' => true,
                'version'        => '1.0.0',
            ]);
        } finally {
            ob_end_clean();
        }

        $afterRollback = $this->describeColumns('bc_old_users');
        $columnNames   = array_map(fn($c) => $c->getName(), $afterRollback);

        $this->assertCount(3, $columnNames);
        $this->assertContains('id', $columnNames);
        $this->assertContains('username', $columnNames);
        $this->assertContains('email', $columnNames);
        $this->assertNotContains('created_at', $columnNames);
    }

    /**
     * Same rollback scenario using the new-format migrations to confirm
     * createPrevClassWithMorphMethod works identically for both formats.
     */
    public function testRollbackNewFormatRestoresTableStructure(): void
    {
        $this->silentRun('backcompat/new');

        $afterForward = $this->describeColumns('bc_new_users');
        $this->assertCount(4, $afterForward);

        ob_start();
        try {
            Migrations::run([
                'migrationsDir'  => $this->getDataDir('backcompat/new'),
                'config'         => static::getMigrationsConfig(),
                'migrationsInDb' => true,
                'version'        => '1.0.0',
            ]);
        } finally {
            ob_end_clean();
        }

        $afterRollback = $this->describeColumns('bc_new_users');
        $columnNames   = array_map(fn($c) => $c->getName(), $afterRollback);

        $this->assertCount(3, $columnNames);
        $this->assertContains('id', $columnNames);
        $this->assertContains('username', $columnNames);
        $this->assertContains('email', $columnNames);
        $this->assertNotContains('created_at', $columnNames);
    }
}
