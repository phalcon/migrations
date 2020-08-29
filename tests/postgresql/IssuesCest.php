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

use Phalcon\Db\Column;
use Phalcon\Migrations\Migrations;
use PostgresqlTester;

final class IssuesCest
{
    public function issue1(PostgresqlTester $I): void
    {
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
}
