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
use Phalcon\Db\Index;
use Phalcon\Migrations\Db\Adapter\Pdo\PdoPostgresql;
use Phalcon\Migrations\Migrations;
use PostgresqlTester;

final class IssuesCest
{
    public function issue1(PostgresqlTester $I): void
    {
        $I->wantToTest('Issue #1 - Primary key was created');

        $tableName = 'table_primary_test';
        $migrationsDir = codecept_output_dir(__FUNCTION__);

        $I->getPhalconDb()->createTable($tableName, $I->getDefaultSchema(), [
            'columns' => [
                new Column('id', [
                    'type' => Column::TYPE_INTEGER,
                    'notNull' => true,
                    'first' => true,
                    'primary' => true,
                ]),
            ],
        ]);

        /**
         * Generate | Drop | Run
         */
        ob_start();
        Migrations::generate([
            'migrationsDir' => $migrationsDir,
            'config' => $I->getMigrationsConfig(),
            'tableName' => $tableName,
        ]);
        $I->getPhalconDb()->dropTable($tableName);
        Migrations::run([
            'migrationsDir' => $migrationsDir,
            'config' => $I->getMigrationsConfig(),
            'migrationsInDb' => true,
        ]);
        ob_clean();

        $indexes = $I->getPhalconDb()->describeIndexes($tableName, $I->getDefaultSchema());

        $I->assertSame(1, count($indexes));
    }

    /**
     * @throws Exception
     */
    public function testIssue111Fail(PostgresqlTester $I): void
    {
        $I->wantToTest('Issue #111 - Unrecognized PostgreSQL data type [FAIL]');

        $tableName = 'pg_phalcon_double';
        $migrationsDir = codecept_output_dir(__FUNCTION__);

        $I->seeExceptionThrown(
            Phalcon\Db\Exception::class,
            function () use ($I, $tableName) {
                $I->getPhalconDb()->createTable($tableName, $I->getDefaultSchema(), [
                    'columns' => [
                        new Column('point_double_column', [
                            'type' => Column::TYPE_DOUBLE,
                            'default' => 0,
                            'notNull' => false,
                            'comment' => "Double typed column",
                        ]),
                    ],
                ]);
            }
        );

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

        $I->assertFalse($I->getPhalconDb()->tableExists($tableName, $I->getDefaultSchema()));
        $I->assertTrue($I->getPhalconDb()->tableExists(Migrations::MIGRATION_LOG_TABLE, $I->getDefaultSchema()));
        $I->assertSame(1, count($indexes));
    }

    /**
     * @throws Exception
     */
    public function testIssue111Fixed(PostgresqlTester $I): void
    {
        $I->wantToTest('Issue #111 - Unrecognized PostgreSQL data type [FIXED]');

        $tableName = 'pg_phalcon_double';
        $migrationsDir = codecept_output_dir(__FUNCTION__);
        $I->getPhalconDb()->createTable($tableName, $I->getDefaultSchema(), [
            'columns' => [
                new Column('point_double_column', [
                    'type' => Column::TYPE_FLOAT,
                    'default' => 0,
                    'notNull' => false,
                    'comment' => "Double typed column",
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
     * @throws Exception
     */
    public function testIssue112(PostgresqlTester $I): void
    {
        $I->wantToTest('Issue #112 - Index should be primary key, instead of Normal Index');

        $tableName = 'pg_phalcon_primary_index';
        $I->getPhalconDb()->createTable($tableName, $I->getDefaultSchema(), [
            'columns' => [
                new Column('id', [
                    'type' => Column::TYPE_INTEGER,
                    'notNull' => true,
                    'first' => true,
                ]),
            ],
            'indexes' => [
                new Index('pk_id_0', ['id'], 'PRIMARY KEY'),
            ],
        ]);

        $indexes = $I->getPhalconDb()->describeIndexes($tableName, $I->getDefaultSchema());
        $index = array_shift($indexes);

        $I->assertSame(PdoPostgresql::INDEX_TYPE_PRIMARY, $index->getType());
    }
}
