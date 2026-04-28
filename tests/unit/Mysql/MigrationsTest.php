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

use Exception;
use Faker\Factory as FakerFactory;
use Phalcon\Db\Column;
use Phalcon\Migrations\Migrations;
use Phalcon\Migrations\Tests\AbstractMysqlTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

use function count;

final class MigrationsTest extends AbstractMysqlTestCase
{
    /**
     * @throws Exception
     */
    public function testGenerateEmptyDataBase(): void
    {
        $migrationsDir = $this->getOutputDir(__FUNCTION__);

        ob_start();
        Migrations::generate([
            'migrationsDir' => [$migrationsDir],
            'config'        => static::getMigrationsConfig(),
        ]);
        ob_end_clean();

        $this->assertTrue(file_exists($migrationsDir) && is_dir($migrationsDir));
        $this->assertSame(2, count(scandir($migrationsDir . '/1.0.0')));
    }

    /**
     * @throws Exception
     */
    public function testGenerateSingleTable(): void
    {
        $migrationsDir = $this->getOutputDir(__FUNCTION__);
        $this->createSingleColumnTable();

        ob_start();
        Migrations::generate([
            'migrationsDir' => [$migrationsDir],
            'config'        => static::getMigrationsConfig(),
            'tableName'     => '@',
        ]);
        ob_end_clean();

        $this->assertTrue(file_exists($migrationsDir) && is_dir($migrationsDir));
        $this->assertSame(3, count(scandir($migrationsDir . '/1.0.0')));
    }

    /**
     * @throws Exception
     */
    public function testGenerateTwoTables(): void
    {
        $migrationsDir = $this->getOutputDir(__FUNCTION__);

        $this->getPhalconDb()->createTable('test', $_ENV['MYSQL_TEST_DB_DATABASE'], [
            'columns' => [
                new Column('column_name', [
                    'type'     => Column::TYPE_INTEGER,
                    'size'     => 10,
                    'unsigned' => true,
                    'notNull'  => true,
                    'first'    => true,
                ]),
                new Column('another_column', [
                    'type'    => Column::TYPE_VARCHAR,
                    'size'    => 255,
                    'notNull' => true,
                ]),
            ],
        ]);

        $this->createSingleColumnTable('test2');

        ob_start();
        Migrations::generate([
            'migrationsDir' => [$migrationsDir],
            'config'        => static::getMigrationsConfig(),
            'tableName'     => '@',
        ]);
        ob_end_clean();

        $this->assertTrue(file_exists($migrationsDir) && is_dir($migrationsDir));
        $this->assertSame(4, count(scandir($migrationsDir . '/1.0.0')));
    }

    /**
     * @throws Exception
     */
    public function testGenerateByMigrationsDirAsString(): void
    {
        $migrationsDir = $this->getOutputDir(__FUNCTION__);
        $this->createSingleColumnTable();

        ob_start();
        Migrations::generate([
            'migrationsDir' => $migrationsDir,
            'config'        => static::getMigrationsConfig(),
            'tableName'     => '@',
        ]);
        ob_end_clean();

        $this->assertTrue(file_exists($migrationsDir) && is_dir($migrationsDir));
        $this->assertSame(3, count(scandir($migrationsDir . '/1.0.0')));
    }

    /**
     * @throws \Phalcon\Db\Exception
     * @throws Exception
     */
    public function testTypeDateWithManyRows(): void
    {
        $faker         = FakerFactory::create();
        $tableName     = 'test_date_with_many_rows';
        $migrationsDir = $this->getOutputDir(__FUNCTION__);

        $this->getPhalconDb()->createTable($tableName, $_ENV['MYSQL_TEST_DB_DATABASE'], [
            'columns' => [
                new Column('id', [
                    'type'     => Column::TYPE_INTEGER,
                    'size'     => 10,
                    'unsigned' => true,
                    'notNull'  => true,
                    'first'    => true,
                ]),
                new Column('name', [
                    'type'    => Column::TYPE_VARCHAR,
                    'size'    => 255,
                    'notNull' => true,
                ]),
                new Column('create_date', [
                    'type'    => Column::TYPE_DATE,
                    'notNull' => true,
                ]),
            ],
        ]);

        $data = [];
        for ($id = 1; $id <= 10000; $id++) {
            $data[] = [
                'id'          => $id,
                'name'        => $faker->name(),
                'create_date' => $faker->date(),
            ];
        }

        $this->batchInsert($tableName, ['id', 'name', 'create_date'], $data);

        ob_start();
        Migrations::generate([
            'migrationsDir' => $migrationsDir,
            'config'        => static::getMigrationsConfig(),
            'tableName'     => '@',
        ]);

        for ($i = 0; $i < 3; $i++) {
            Migrations::run([
                'migrationsDir'  => $migrationsDir,
                'config'         => static::getMigrationsConfig(),
                'migrationsInDb' => true,
            ]);
        }
        ob_end_clean();

        $this->assertTrue(file_exists($migrationsDir) && is_dir($migrationsDir));
        $this->assertSame(3, count(scandir($migrationsDir)));
    }

    /**
     * @throws \Phalcon\Db\Exception
     * @throws Exception
     */
    public function testPhalconMigrationsTable(): void
    {
        $tableName     = 'test_mysql_phalcon_migrations';
        $migrationsDir = $this->getOutputDir(__FUNCTION__);

        $this->createSingleColumnTable($tableName);

        ob_start();
        Migrations::generate([
            'migrationsDir' => [$migrationsDir],
            'config'        => static::getMigrationsConfig(),
            'tableName'     => '@',
        ]);
        $this->getPhalconDb()->dropTable($tableName);
        Migrations::run([
            'migrationsDir'  => $migrationsDir,
            'config'         => static::getMigrationsConfig(),
            'migrationsInDb' => true,
        ]);
        ob_end_clean();

        $indexes      = $this->getPhalconDb()->describeIndexes(Migrations::MIGRATION_LOG_TABLE);
        $currentIndex = current($indexes);

        $this->assertTrue($this->getPhalconDb()->tableExists($tableName));
        $this->assertTrue($this->getPhalconDb()->tableExists(Migrations::MIGRATION_LOG_TABLE));
        $this->assertSame(1, count($indexes));
        $this->assertArrayHasKey('PRIMARY', $indexes);
        $this->assertSame('PRIMARY', $currentIndex->getType());
    }

    /**
     * @throws Exception
     */
    public function testGenerateWithAutoIncrement(): void
    {
        $dbName        = $_ENV['MYSQL_TEST_DB_DATABASE'];
        $tableName     = 'generate_ai';
        $migrationsDir = $this->getOutputDir(__FUNCTION__);

        $this->getPhalconDb()->createTable($tableName, $dbName, [
            'columns' => [
                new Column('id', [
                    'type'          => Column::TYPE_INTEGER,
                    'size'          => 10,
                    'unsigned'      => true,
                    'notNull'       => true,
                    'first'         => true,
                    'primary'       => true,
                    'autoIncrement' => true,
                ]),
            ],
        ]);

        $this->batchInsert($tableName, ['id'], [[1], [2], [3]]);

        $autoIncrement = $this->getPhalconDb()->fetchColumn(
            sprintf('SHOW TABLE STATUS FROM `%s` WHERE Name = "%s"', $dbName, $tableName),
            [],
            10
        );

        ob_start();
        Migrations::generate([
            'migrationsDir' => $migrationsDir,
            'config'        => static::getMigrationsConfig(),
            'tableName'     => '@',
        ]);
        ob_end_clean();

        $this->assertEquals(4, $autoIncrement);
        $this->assertStringContainsString(
            "'AUTO_INCREMENT' => '4'",
            file_get_contents($migrationsDir . '/1.0.0/' . $tableName . '.php')
        );
    }

    /**
     * @throws Exception
     */
    public function testGenerateWithoutAutoIncrement(): void
    {
        $dbName        = $_ENV['MYSQL_TEST_DB_DATABASE'];
        $tableName     = 'generate_no_ai';
        $migrationsDir = $this->getOutputDir(__FUNCTION__);

        $this->getPhalconDb()->createTable($tableName, $dbName, [
            'columns' => [
                new Column('id', [
                    'type'          => Column::TYPE_INTEGER,
                    'size'          => 10,
                    'unsigned'      => true,
                    'notNull'       => true,
                    'first'         => true,
                    'primary'       => true,
                    'autoIncrement' => true,
                ]),
            ],
        ]);

        $this->batchInsert($tableName, ['id'], [[1], [2], [3]]);

        $autoIncrement = $this->getPhalconDb()->fetchColumn(
            sprintf('SHOW TABLE STATUS FROM `%s` WHERE Name = "%s"', $dbName, $tableName),
            [],
            10
        );

        ob_start();
        Migrations::generate([
            'migrationsDir'   => $migrationsDir,
            'config'          => static::getMigrationsConfig(),
            'tableName'       => '@',
            'noAutoIncrement' => true,
        ]);
        ob_end_clean();

        $this->assertEquals(4, $autoIncrement);
        $this->assertStringContainsString(
            "'AUTO_INCREMENT' => ''",
            file_get_contents($migrationsDir . '/1.0.0/' . $tableName . '.php')
        );
    }

    /**
     * @throws \Phalcon\Db\Exception
     */
    public function testRunAllMigrations(): void
    {
        $this->runIssue66Migrations();

        $this->assertNumRecords(4, 'phalcon_migrations');
    }

    public static function specificMigrationsDataProvider(): array
    {
        return [
            [['0.0.1']],
            [['0.0.2', '0.0.3']],
            [['0.0.2', '0.0.3', '0.0.4']],
            [['0.0.1', '0.0.3', '0.0.4']],
            [['0.0.1', '0.0.4']],
            [['0.0.4']],
        ];
    }

    /**
     * @throws \Phalcon\Db\Exception
     */
    #[DataProvider('specificMigrationsDataProvider')]
    public function testRunSpecificMigrations(array $versions): void
    {
        $this->insertCompletedMigrations($versions);

        $this->assertNumRecords(count($versions), 'phalcon_migrations');

        $this->runIssue66Migrations();

        $this->assertNumRecords(4, 'phalcon_migrations');
    }

    /**
     * @throws Exception
     */
    public function testGenerateWithExportOnCreate(): void
    {
        $dbName        = $_ENV['MYSQL_TEST_DB_DATABASE'];
        $tableName     = 'on_create';
        $migrationsDir = $this->getOutputDir(__FUNCTION__);

        $this->getPhalconDb()->createTable($tableName, $dbName, [
            'columns' => [
                new Column('id', [
                    'type'          => Column::TYPE_INTEGER,
                    'size'          => 10,
                    'unsigned'      => true,
                    'notNull'       => true,
                    'first'         => true,
                    'primary'       => true,
                    'autoIncrement' => true,
                ]),
            ],
        ]);

        $this->batchInsert($tableName, ['id'], [[1], [2], [3]]);

        ob_start();
        Migrations::generate([
            'migrationsDir'   => $migrationsDir,
            'config'          => static::getMigrationsConfig(),
            'tableName'       => '@',
            'noAutoIncrement' => true,
            'exportData'      => 'oncreate',
        ]);
        ob_end_clean();

        $migrationContents = file_get_contents($migrationsDir . '/1.0.0/' . $tableName . '.php');

        $this->assertSame(1, substr_count($migrationContents, 'this->batchInsert'));
        $this->assertStringContainsString(
            '3',
            file_get_contents($migrationsDir . '/1.0.0/' . $tableName . '.dat')
        );
    }

    /**
     * @throws \Phalcon\Db\Exception
     */
    public function testUpdateColumnUnsigned(): void
    {
        $dbName        = $_ENV['MYSQL_TEST_DB_DATABASE'];
        $tableName     = 'update_unsigned_column';
        $migrationsDir = $this->getOutputDir(__FUNCTION__);

        $this->getPhalconDb()->createTable($tableName, $dbName, [
            'columns' => [
                new Column('id', [
                    'type'          => Column::TYPE_INTEGER,
                    'size'          => 10,
                    'unsigned'      => false,
                    'notNull'       => true,
                    'first'         => true,
                    'primary'       => true,
                    'autoIncrement' => true,
                ]),
            ],
        ]);

        ob_start();
        Migrations::generate([
            'migrationsDir' => $migrationsDir,
            'config'        => static::getMigrationsConfig(),
            'tableName'     => '@',
        ]);
        ob_end_clean();

        ob_start();
        Migrations::run([
            'migrationsDir'  => $this->getDataDir('issues/109'),
            'config'         => static::getMigrationsConfig(),
            'migrationsInDb' => true,
        ]);
        ob_end_clean();

        $columns = $this->getPhalconDb()->describeColumns($tableName);

        $this->assertTrue($columns[0]->isUnsigned());
    }

    /**
     * @throws \Phalcon\Db\Exception
     */
    public function testNullableTimestamp(): void
    {
        $dbName    = $_ENV['MYSQL_TEST_DB_DATABASE'];
        $tableName = 'nullable_timestamp';
        $migrationsDir = $this->getOutputDir(__FUNCTION__);

        $this->getPhalconDb()->createTable($tableName, $dbName, [
            'columns' => [
                new Column('created_at', [
                    'type'    => Column::TYPE_TIMESTAMP,
                    'default' => 'CURRENT_TIMESTAMP',
                    'notNull' => true,
                ]),
                new Column('deleted_at', [
                    'type'    => Column::TYPE_TIMESTAMP,
                    'default' => null,
                    'notNull' => false,
                    'after'   => 'created_at',
                ]),
            ],
        ]);

        ob_start();
        Migrations::generate([
            'migrationsDir' => [$migrationsDir],
            'config'        => static::getMigrationsConfig(),
            'tableName'     => '@',
        ]);
        $this->getPhalconDb()->dropTable($tableName);
        Migrations::run([
            'migrationsDir'  => $migrationsDir,
            'config'         => static::getMigrationsConfig(),
            'migrationsInDb' => true,
        ]);
        ob_end_clean();

        $columns = $this->getPhalconDb()->describeColumns($tableName);

        $this->assertFalse($columns[1]->isNotNull());
        $this->assertNull($columns[1]->getDefault());
        $this->assertNumRecords(0, $tableName);
    }

    private function runIssue66Migrations(): void
    {
        ob_start();
        Migrations::run([
            'migrationsDir'  => $this->getDataDir('issues/66'),
            'config'         => static::getMigrationsConfig(),
            'migrationsInDb' => true,
        ]);
        ob_end_clean();
    }

    private function insertCompletedMigrations(array $versions): void
    {
        $this->getPhalconDb()->createTable(Migrations::MIGRATION_LOG_TABLE, '', [
            'columns' => [
                new Column('version', [
                    'type'    => Column::TYPE_VARCHAR,
                    'size'    => 255,
                    'notNull' => true,
                    'first'   => true,
                    'primary' => true,
                ]),
                new Column('start_time', [
                    'type'    => Column::TYPE_TIMESTAMP,
                    'notNull' => true,
                    'default' => 'CURRENT_TIMESTAMP',
                ]),
                new Column('end_time', [
                    'type'    => Column::TYPE_TIMESTAMP,
                    'notNull' => true,
                    'default' => 'CURRENT_TIMESTAMP',
                ]),
            ],
        ]);

        $date = date('Y-m-d H:i:s');
        foreach ($versions as $version) {
            $this->getPhalconDb()->execute(sprintf(
                'INSERT INTO phalcon_migrations (version, start_time, end_time) VALUES ("%s", "%s", "%s")',
                $version,
                $date,
                $date
            ));
        }
    }

    private function createSingleColumnTable(string $tableName = 'test'): void
    {
        $this->getPhalconDb()->createTable($tableName, $_ENV['MYSQL_TEST_DB_DATABASE'], [
            'columns' => [
                new Column('column_name', [
                    'type'     => Column::TYPE_INTEGER,
                    'size'     => 10,
                    'unsigned' => true,
                    'notNull'  => true,
                    'first'    => true,
                ]),
            ],
        ]);
    }
}
