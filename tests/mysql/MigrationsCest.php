<?php

declare(strict_types=1);

namespace Phalcon\Migrations\Tests\Mysql;

use Codeception\Example;
use Exception;
use Faker\Factory as FakerFactory;
use MysqlTester;
use PDO;
use Phalcon\Config;
use Phalcon\Db\Adapter\Pdo\AbstractPdo;
use Phalcon\Db\Column;
use Phalcon\Migrations\Migrations;

use function count;

/**
 * @method Config getMigrationsConfig()
 * @method AbstractPdo getPhalconDb()
 * @method PDO getDb()
 */
final class MigrationsCest
{
    /**
     * @param MysqlTester $I
     * @throws Exception
     */
    public function generateEmptyDataBase(MysqlTester $I): void
    {
        $migrationsDir = codecept_output_dir(__FUNCTION__);

        ob_start();
        Migrations::generate([
            'migrationsDir' => [
                $migrationsDir,
            ],
            'config' => $I->getMigrationsConfig(),
        ]);
        ob_clean();

        $I->assertTrue(file_exists($migrationsDir) && is_dir($migrationsDir));
        $I->assertSame(count(scandir($migrationsDir . '/1.0.0')), 2);
    }

    /**
     * @param MysqlTester $I
     * @throws Exception
     */
    public function generateSingleTable(MysqlTester $I): void
    {
        $migrationsDir = codecept_output_dir(__FUNCTION__);
        $this->createSingleColumnTable($I);

        ob_start();
        Migrations::generate([
            'migrationsDir' => [
                $migrationsDir,
            ],
            'config' => $I->getMigrationsConfig(),
            'tableName' => '@',
        ]);
        ob_clean();

        $I->assertTrue(file_exists($migrationsDir) && is_dir($migrationsDir));
        $I->assertSame(3, count(scandir($migrationsDir . '/1.0.0')));
    }

    /**
     * @param MysqlTester $I
     * @throws Exception
     */
    public function generateTwoTables(MysqlTester $I): void
    {
        $migrationsDir = codecept_output_dir(__FUNCTION__);

        $I->getPhalconDb()->createTable('test', getenv('MYSQL_TEST_DB_DATABASE'), [
            'columns' => [
                new Column('column_name', [
                    'type' => Column::TYPE_INTEGER,
                    'size' => 10,
                    'unsigned' => true,
                    'notNull' => true,
                    'first' => true,
                ]),
                new Column('another_column', [
                    'type' => Column::TYPE_VARCHAR,
                    'size' => 255,
                    'unsigned' => true,
                    'notNull' => true,
                ]),
            ],
        ]);

        $this->createSingleColumnTable($I, 'test2');

        ob_start();
        Migrations::generate([
            'migrationsDir' => [
                $migrationsDir,
            ],
            'config' => $I->getMigrationsConfig(),
            'tableName' => '@',
        ]);
        ob_clean();

        $I->assertTrue(file_exists($migrationsDir) && is_dir($migrationsDir));
        $I->assertSame(4, count(scandir($migrationsDir . '/1.0.0')));
    }

    /**
     * @param MysqlTester $I
     * @throws Exception
     */
    public function generateByMigrationsDirAsString(MysqlTester $I): void
    {
        $migrationsDir = codecept_output_dir(__FUNCTION__);
        $this->createSingleColumnTable($I);

        ob_start();
        Migrations::generate([
            'migrationsDir' => $migrationsDir,
            'config' => $I->getMigrationsConfig(),
            'tableName' => '@',
        ]);
        ob_clean();

        $I->assertTrue(file_exists($migrationsDir) && is_dir($migrationsDir));
        $I->assertSame(3, count(scandir($migrationsDir . '/1.0.0')));
    }

    /**
     * @param MysqlTester $I
     * @throws \Phalcon\Db\Exception
     * @throws Exception
     */
    public function typeDateWithManyRows(MysqlTester $I): void
    {
        $faker = FakerFactory::create();
        $tableName = 'test_date_with_many_rows';
        $migrationsDir = codecept_output_dir(__FUNCTION__);

        $I->getPhalconDb()->createTable($tableName, getenv('MYSQL_TEST_DB_DATABASE'), [
            'columns' => [
                new Column('id', [
                    'type' => Column::TYPE_INTEGER,
                    'size' => 10,
                    'unsigned' => true,
                    'notNull' => true,
                    'first' => true,
                ]),
                new Column('name', [
                    'type' => Column::TYPE_VARCHAR,
                    'size' => 255,
                    'notNull' => true,
                ]),
                new Column('create_date', [
                    'type' => Column::TYPE_DATE,
                    'notNull' => true,
                ]),
            ],
        ]);

        $data = [];
        for ($id = 1; $id <= 10000; $id++) {
            $data[] = [
                'id' => $id,
                'name' => $faker->name,
                'create_date' => $faker->date(),
            ];
        }

        $I->batchInsert($tableName, ['id', 'name', 'create_date'], $data);

        ob_start();
        Migrations::generate([
            'migrationsDir' => $migrationsDir,
            'config' => $I->getMigrationsConfig(),
            'tableName' => '@',
        ]);

        for ($i = 0; $i < 3; $i++) {
            Migrations::run([
                'migrationsDir' => $migrationsDir,
                'config' => $I->getMigrationsConfig(),
                'migrationsInDb' => true,
            ]);
        }
        ob_clean();

        $I->assertTrue(file_exists($migrationsDir) && is_dir($migrationsDir));
        $I->assertSame(3, count(scandir($migrationsDir)));
    }

    /**
     * @param MysqlTester $I
     * @throws \Phalcon\Db\Exception
     * @throws Exception
     */
    public function phalconMigrationsTable(MysqlTester $I): void
    {
        $tableName = 'test_mysql_phalcon_migrations';
        $migrationsDir = codecept_output_dir(__FUNCTION__);

        $this->createSingleColumnTable($I, $tableName);

        ob_start();
        Migrations::generate([
            'migrationsDir' => [
                $migrationsDir,
            ],
            'config' => $I->getMigrationsConfig(),
            'tableName' => '@',
        ]);
        $I->getPhalconDb()->dropTable($tableName);
        Migrations::run([
            'migrationsDir' => $migrationsDir,
            'config' => $I->getMigrationsConfig(),
            'migrationsInDb' => true,
        ]);
        ob_clean();

        $indexes = $I->getPhalconDb()->describeIndexes(Migrations::MIGRATION_LOG_TABLE);
        $currentIndex = current($indexes);

        $I->assertTrue($I->getPhalconDb()->tableExists($tableName));
        $I->assertTrue($I->getPhalconDb()->tableExists(Migrations::MIGRATION_LOG_TABLE));
        $I->assertSame(1, count($indexes));
        $I->assertArrayHasKey('PRIMARY', $indexes);
        $I->assertSame('PRIMARY', $currentIndex->getType());
    }

    /**
     * @param MysqlTester $I
     * @throws Exception
     */
    public function generateWithAutoIncrement(MysqlTester $I): void
    {
        $dbName = getenv('MYSQL_TEST_DB_DATABASE');
        $tableName = 'generate_ai';
        $migrationsDir = codecept_output_dir(__FUNCTION__);

        $I->getPhalconDb()->createTable($tableName, $dbName, [
            'columns' => [
                new Column('id', [
                    'type' => Column::TYPE_INTEGER,
                    'size' => 10,
                    'unsigned' => true,
                    'notNull' => true,
                    'first' => true,
                    'primary' => true,
                    'autoIncrement' => true,
                ]),
            ],
        ]);

        $I->batchInsert($tableName, ['id'], [
            [1],
            [2],
            [3],
        ]);
        $autoIncrement = $I
            ->getPhalconDb()
            ->fetchColumn(sprintf('SHOW TABLE STATUS FROM `%s` WHERE Name = "%s"', $dbName, $tableName), [], 10);

        ob_start();
        Migrations::generate([
            'migrationsDir' => $migrationsDir,
            'config' => $I->getMigrationsConfig(),
            'tableName' => '@',
        ]);
        ob_clean();

        $I->assertEquals(4, $autoIncrement);
        $I->assertContains(
            "'AUTO_INCREMENT' => '4'",
            file_get_contents($migrationsDir . '/1.0.0/' . $tableName . '.php')
        );
    }

    /**
     * @param MysqlTester $I
     * @throws Exception
     */
    public function generateWithoutAutoIncrement(MysqlTester $I): void
    {
        $dbName = getenv('MYSQL_TEST_DB_DATABASE');
        $tableName = 'generate_no_ai';
        $migrationsDir = codecept_output_dir(__FUNCTION__);

        $I->getPhalconDb()->createTable($tableName, $dbName, [
            'columns' => [
                new Column('id', [
                    'type' => Column::TYPE_INTEGER,
                    'size' => 10,
                    'unsigned' => true,
                    'notNull' => true,
                    'first' => true,
                    'primary' => true,
                    'autoIncrement' => true,
                ]),
            ],
        ]);

        $I->batchInsert($tableName, ['id'], [
            [1],
            [2],
            [3],
        ]);
        $autoIncrement = $I
            ->getPhalconDb()
            ->fetchColumn(sprintf('SHOW TABLE STATUS FROM `%s` WHERE Name = "%s"', $dbName, $tableName), [], 10);

        ob_start();
        Migrations::generate([
            'migrationsDir' => $migrationsDir,
            'config' => $I->getMigrationsConfig(),
            'tableName' => '@',
            'noAutoIncrement' => true,
        ]);
        ob_clean();

        $I->assertEquals(4, $autoIncrement);
        $I->assertContains(
            "'AUTO_INCREMENT' => ''",
            file_get_contents($migrationsDir . '/1.0.0/' . $tableName . '.php')
        );
    }

    /**
     * @param MysqlTester $I
     * @throws \Phalcon\Db\Exception
     */
    public function runAllMigrations(MysqlTester $I): void
    {
        $this->runIssue66Migrations($I);

        $I->seeNumRecords(4, 'phalcon_migrations');
    }

    /**
     * @dataProvider specificMigrationsDataProvider
     *
     * @param MysqlTester $I
     * @param Example $example
     * @throws \Phalcon\Db\Exception
     */
    public function runSpecificMigrations(MysqlTester $I, Example $example): void
    {
        $versions = current($example);
        $this->insertCompletedMigrations($I, $versions);

        $I->seeNumRecords(count($versions), 'phalcon_migrations');

        $this->runIssue66Migrations($I);

        $I->seeNumRecords(4, 'phalcon_migrations');
    }

    /**
     * @param MysqlTester $I
     * @throws Exception
     */
    public function generateWithExportOnCreate(MysqlTester $I): void
    {
        $dbName = getenv('MYSQL_TEST_DB_DATABASE');
        $tableName = 'on_create';
        $migrationsDir = codecept_output_dir(__FUNCTION__);

        $I->getPhalconDb()->createTable($tableName, $dbName, [
            'columns' => [
                new Column('id', [
                    'type' => Column::TYPE_INTEGER,
                    'size' => 10,
                    'unsigned' => true,
                    'notNull' => true,
                    'first' => true,
                    'primary' => true,
                    'autoIncrement' => true,
                ]),
            ],
        ]);

        $I->batchInsert($tableName, ['id'], [
            [1],
            [2],
            [3],
        ]);

        ob_start();
        Migrations::generate([
            'migrationsDir' => $migrationsDir,
            'config' => $I->getMigrationsConfig(),
            'tableName' => '@',
            'noAutoIncrement' => true,
            'exportData' => 'oncreate',
        ]);
        ob_clean();

        $migrationContents = file_get_contents($migrationsDir . '/1.0.0/' . $tableName . '.php');

        $I->assertSame(1, substr_count($migrationContents, 'this->batchInsert'));
        $I->assertContains(
            '3',
            file_get_contents($migrationsDir . '/1.0.0/' . $tableName . '.dat')
        );
    }

    public function nullableTimestamp(MysqlTester $I): void
    {
        $dbName = getenv('MYSQL_TEST_DB_DATABASE');
        $tableName = 'nullable_timestamp';
        $migrationsDir = codecept_output_dir(__FUNCTION__);

        $I->getPhalconDb()->createTable($tableName, $dbName, [
            'columns' => [
                new Column(
                    'created_at',
                    [
                        'type' => Column::TYPE_TIMESTAMP,
                        'default' => 'CURRENT_TIMESTAMP',
                        'notNull' => true,
                    ]
                ),
                new Column(
                    'deleted_at',
                    [
                        'type' => Column::TYPE_TIMESTAMP,
                        'default' => null,
                        'notNull' => false,
                        'after' => 'created_at',
                    ]
                ),
            ],
        ]);

        ob_start();
        Migrations::generate([
            'migrationsDir' => [
                $migrationsDir,
            ],
            'config' => $I->getMigrationsConfig(),
            'tableName' => '@',
        ]);
        $I->getPhalconDb()->dropTable($tableName);
        Migrations::run([
            'migrationsDir' => $migrationsDir,
            'config' => $I->getMigrationsConfig(),
            'migrationsInDb' => true,
        ]);
        ob_clean();

        $columns = $I->getPhalconDb()->describeColumns($tableName);

        $I->assertFalse($columns[1]->isNotNull());
        $I->assertNull($columns[1]->getDefault());
        $I->seeNumRecords(0, $tableName);
    }

    /**
     * @param MysqlTester $I
     * @throws \Phalcon\Db\Exception
     */
    protected function runIssue66Migrations(MysqlTester $I): void
    {
        ob_start();

        Migrations::run([
            'migrationsDir' => codecept_data_dir('issues/66'),
            'config' => $I->getMigrationsConfig(),
            'migrationsInDb' => true,
        ]);

        ob_clean();
    }

    /**
     * @param MysqlTester $I
     * @param array $versions
     */
    protected function insertCompletedMigrations(MysqlTester $I, array $versions): void
    {
        $I->getPhalconDb()->createTable(Migrations::MIGRATION_LOG_TABLE, '', [
            'columns' => [
                new Column(
                    'version',
                    [
                        'type' => Column::TYPE_VARCHAR,
                        'size' => 255,
                        'notNull' => true,
                        'first' => true,
                        'primary' => true,
                    ]
                ),
                new Column(
                    'start_time',
                    [
                        'type' => Column::TYPE_TIMESTAMP,
                        'notNull' => true,
                        'default' => 'CURRENT_TIMESTAMP',
                    ]
                ),
                new Column(
                    'end_time',
                    [
                        'type' => Column::TYPE_TIMESTAMP,
                        'notNull' => true,
                        'default' => 'CURRENT_TIMESTAMP',
                    ]
                )
            ],
        ]);

        $date = date('Y-m-d H:i:s');
        foreach ($versions as $version) {
            $sql = sprintf(
                'INSERT INTO phalcon_migrations (version, start_time, end_time) VALUES ("%s", "%s", "%s")',
                $version,
                $date,
                $date
            );
            $I->getPhalconDb()->execute($sql);
        }
    }

    /**
     * @return array
     */
    protected function specificMigrationsDataProvider(): array
    {
        return [
            ['0.0.1'],
            ['0.0.2', '0.0.3'],
            ['0.0.2', '0.0.3', '0.0.4'],
            ['0.0.1', '0.0.3', '0.0.4'],
            ['0.0.1', '0.0.4'],
            ['0.0.4'],
        ];
    }

    /**
     * @param MysqlTester $I
     * @param string $tableName
     */
    protected function createSingleColumnTable(MysqlTester $I, string $tableName = 'test'): void
    {
        $I->getPhalconDb()->createTable($tableName, getenv('MYSQL_TEST_DB_DATABASE'), [
            'columns' => [
                new Column('column_name', [
                    'type' => Column::TYPE_INTEGER,
                    'size' => 10,
                    'unsigned' => true,
                    'notNull' => true,
                    'first' => true,
                ]),
            ],
        ]);
    }
}
