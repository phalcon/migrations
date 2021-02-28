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

use Phalcon\Db\Exception;
use Phalcon\Migrations\Migrations;
use PostgresqlTester;

use function codecept_data_dir;

/**
 * @see https://github.com/phalcon/migrations/issues/104
 */
class Issue104Cest
{
    /**
     * @param PostgresqlTester $I
     * @throws Exception
     */
    public function normalRun(PostgresqlTester $I): void
    {
        $I->wantToTest('Issue #104 - Disable foreign keys');

        $I->getPhalconDb()->execute("SET session_replication_role = 'replica';");

        ob_start();
        Migrations::run([
            'migrationsDir' => codecept_data_dir('issues/104'),
            'config' => $I->getMigrationsConfig(),
            'migrationsInDb' => true,
        ]);
        ob_clean();

        $I->getPhalconDb()->execute("SET session_replication_role = 'origin';");

        $schema = getenv('POSTGRES_TEST_DB_SCHEMA');

        $I->assertTrue($I->getPhalconDb()->tableExists('phalcon_migrations', $schema));
        $I->assertTrue($I->getPhalconDb()->tableExists('foreign_keys_table1', $schema));
        $I->assertTrue($I->getPhalconDb()->tableExists('foreign_keys_table2', $schema));
    }
}
