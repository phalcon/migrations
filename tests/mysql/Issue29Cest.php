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

namespace Phalcon\Migrations\Tests\Mysql;

use MysqlTester;
use Phalcon\Db\Exception;
use Phalcon\Migrations\Migrations;

use function codecept_data_dir;

/**
 * @see https://github.com/phalcon/migrations/issues/29
 */
class Issue29Cest
{
    /**
     * @param MysqlTester $I
     * @throws Exception
     */
    public function testIssue29(MysqlTester $I): void
    {
        $I->wantToTest('Issue #29 - Foreign key was created');

        ob_start();
        Migrations::run([
            'migrationsDir' => codecept_data_dir('issues/29'),
            'config' => $I->getMigrationsConfig(),
            'migrationsInDb' => true,
        ]);
        ob_clean();

        $I->assertTrue($I->getPhalconDb()->tableExists('tasks'));
        $I->assertTrue($I->getPhalconDb()->tableExists('task_jobs'));
        $I->assertArrayHasKey('task_jobs_tasks_id_fk', $I->getPhalconDb()->describeReferences('task_jobs'));
    }
}
