<?php
declare(strict_types=1);

namespace Phalcon\Migrations\Tests\Integration;

use Exception;
use Phalcon\Config;
use Phalcon\Db\Column;
use Phalcon\Migrations\Migrations;
use function Phalcon\Migrations\Tests\root_path;

final class MigrationsTest extends IntegrationTestCase
{
    /**
     * @var array
     */
    protected static $generateConfig;

    /**
     * Set Up Before Class Fixture
     */
    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        
        self::$generateConfig = new Config([
            'database' => [
                'adapter' => getenv('TEST_DB_ADAPTER'),
                'host' => getenv('TEST_DB_HOST'),
                'username' => getenv('TEST_DB_USER'),
                'password' => getenv('TEST_DB_PASSWORD'),
                'dbname' => getenv('TEST_DB_DATABASE'),
            ],
        ]);
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
        
        $this->db->createTable('test', getenv('TEST_DB_DATABASE'), [
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

        $this->db->createTable('test', getenv('TEST_DB_DATABASE'), [
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

        $this->db->createTable('test2', getenv('TEST_DB_DATABASE'), [
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

        $this->db->createTable('test', getenv('TEST_DB_DATABASE'), [
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
}
