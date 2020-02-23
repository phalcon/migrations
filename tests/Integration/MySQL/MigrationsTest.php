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

namespace Phalcon\Migrations\Tests\Integration\MySQL;

use Exception;
use Faker\Factory as FakerFactory;
use Phalcon\Db\Column;
use Phalcon\Migrations\Migrations;

use function Phalcon\Migrations\Tests\db_batch_insert;
use function Phalcon\Migrations\Tests\root_path;

final class MigrationsTest extends MySQLIntegrationTestCase
{
    /**
     * Set Up Before Class Fixture
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
    }

    /**
     * @throws Exception
     */
    public function testGenerateEmptyDataBase(): void
    {
        $migrationsDir = root_path('tests/var/output/' . __FUNCTION__);

        $options = [
            'migrationsDir' => [
                $migrationsDir,
            ],
            'config' => self::$generateConfig,
        ];

        Migrations::generate($options);

        $this->assertDirectoryExists($migrationsDir);
        $this->assertSame(count(scandir($migrationsDir . '/1.0.0')), 2);
    }

    /**
     * @throws Exception
     */
    public function testGenerateSingleTable(): void
    {
        $migrationsDir = root_path('tests/var/output/' . __FUNCTION__);
        
        $this->db->createTable('test', getenv('MYSQL_TEST_DB_DATABASE'), [
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
        
        $options = [
            'migrationsDir' => [
                $migrationsDir,
            ],
            'config' => self::$generateConfig,
            'tableName' => '@',
        ];
        
        Migrations::generate($options);

        $this->assertDirectoryExists($migrationsDir);
        $this->assertSame(3, count(scandir($migrationsDir . '/1.0.0')));
    }

    /**
     * @throws Exception
     */
    public function testGenerateTwoTables(): void
    {
        $migrationsDir = root_path('tests/var/output/' . __FUNCTION__);

        $this->db->createTable('test', getenv('MYSQL_TEST_DB_DATABASE'), [
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

        $this->db->createTable('test2', getenv('MYSQL_TEST_DB_DATABASE'), [
            'columns' => [
                new Column('another_column', [
                    'type' => Column::TYPE_VARCHAR,
                    'size' => 255,
                    'unsigned' => true,
                    'notNull' => true,
                ]),
            ],
        ]);

        $options = [
            'migrationsDir' => [
                $migrationsDir,
            ],
            'config' => self::$generateConfig,
            'tableName' => '@',
        ];

        Migrations::generate($options);

        $this->assertDirectoryExists($migrationsDir);
        $this->assertSame(4, count(scandir($migrationsDir . '/1.0.0')));
    }

    /**
     * @throws Exception
     */
    public function testGenerateByMigrationsDirAsString(): void
    {
        $migrationsDir = root_path('tests/var/output/' . __FUNCTION__);

        $this->db->createTable('test', getenv('MYSQL_TEST_DB_DATABASE'), [
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

        $options = [
            'migrationsDir' => $migrationsDir,
            'config' => self::$generateConfig,
            'tableName' => '@',
        ];

        Migrations::generate($options);

        $this->assertDirectoryExists($migrationsDir);
        $this->assertSame(3, count(scandir($migrationsDir . '/1.0.0')));
    }

    /**
     * @throws Exception
     */
    public function testTypeDateWithManyRows(): void
    {
        $faker = FakerFactory::create();
        $tableName = 'test_date_with_many_rows';
        $migrationsDir = root_path('tests/var/output/' . __FUNCTION__);

        $this->db->createTable($tableName, getenv('MYSQL_TEST_DB_DATABASE'), [
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

        db_batch_insert($this->db, $tableName, ['id', 'name', 'create_date'], $data);

        Migrations::generate([
            'migrationsDir' => $migrationsDir,
            'config' => self::$generateConfig,
            'tableName' => '@',
        ]);

        for ($i = 0; $i < 3; $i++) {
            Migrations::run([
                'migrationsDir' => $migrationsDir,
                'config' => self::$generateConfig,
                'migrationsInDb' => true,
            ]);
        }

        $this->assertDirectoryExists($migrationsDir);
        $this->assertSame(3, count(scandir($migrationsDir)));
    }

    /**
     * @throws Exception
     */
    public function testMySQLPhalconMigrationsTable(): void
    {
        $tableName = 'test_mysql_phalcon_migrations';
        $migrationsDir = root_path('tests/var/output/' . __FUNCTION__);

        $this->db->createTable($tableName, getenv('MYSQL_TEST_DB_DATABASE'), [
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

        $options = [
            'migrationsDir' => [
                $migrationsDir,
            ],
            'config' => self::$generateConfig,
            'tableName' => '@',
        ];

        Migrations::generate($options);
        $this->db->dropTable($tableName);
        Migrations::run([
            'migrationsDir' => $migrationsDir,
            'config' => self::$generateConfig,
            'migrationsInDb' => true,
        ]);

        $indexes = $this->db->describeIndexes(Migrations::MIGRATION_LOG_TABLE);
        $currentIndex = current($indexes);

        $this->assertTrue($this->db->tableExists($tableName));
        $this->assertTrue($this->db->tableExists(Migrations::MIGRATION_LOG_TABLE));
        $this->assertSame(1, count($indexes));
        $this->assertArrayHasKey('PRIMARY', $indexes);
        $this->assertSame('PRIMARY', $currentIndex->getType());
    }
}
