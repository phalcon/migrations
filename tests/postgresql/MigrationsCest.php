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

namespace Phalcon\Migrations\Tests\PostgreSQL;

use Exception;
use Phalcon\Db\Column;
use Phalcon\Migrations\Migrations;
use PostgresqlTester;

final class MigrationsCest
{
    /**
     * @throws Exception
     */
    public function postgresPhalconMigrationsTable(PostgresqlTester $I): void
    {
        $tableName = 'pg_phalcon_migrations';
        $migrationsDir = codecept_output_dir(__FUNCTION__);

        $I->getPhalconDb()->createTable($tableName, $I->getDefaultSchema(), [
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

        $I->assertTrue($I->getPhalconDb()->tableExists($tableName, $I->getDefaultSchema()));
        $I->assertTrue($I->getPhalconDb()->tableExists(Migrations::MIGRATION_LOG_TABLE, $I->getDefaultSchema()));
        $I->assertSame(1, count($indexes));
    }

    /**
     * @param PostgresqlTester $I
     * @throws Exception
     */
    public function generateWithExportOnCreate(PostgresqlTester $I): void
    {
        $dbName = getenv('POSTGRES_TEST_DB_SCHEMA');
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

        $I->getPhalconDb()->insert($tableName, [1], ['id']);
        $I->getPhalconDb()->insert($tableName, [2], ['id']);
        $I->getPhalconDb()->insert($tableName, [3], ['id']);

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
}
