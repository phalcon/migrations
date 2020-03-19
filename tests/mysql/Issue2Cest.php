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
use Phalcon\Migrations\Migrations;

use function codecept_data_dir;

class Issue2Cest
{
    /**
     * @param MysqlTester $I
     * @throws \Phalcon\Migrations\Script\ScriptException
     * @throws \Phalcon\Mvc\Model\Exception
     */
    public function testDisableEnableForeignKeyChecks(MysqlTester $I): void
    {
        $I->wantToTest('Issue #2 - Foreign Key is created during alter table');

        ob_start();
        Migrations::run([
            'migrationsDir' => codecept_data_dir('issues/2'),
            'config' => $I->getMigrationsConfig(),
            'migrationsInDb' => true,
        ]);
        ob_clean();

        $I->assertTrue($I->getPhalconDb()->tableExists('accessToken'));
        $I->assertTrue($I->getPhalconDb()->tableExists('client'));
        $I->assertArrayHasKey('fk_accessToken_client_1', $I->getPhalconDb()->describeReferences('accessToken'));
    }
}
