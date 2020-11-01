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
use Phalcon\Migrations\Exception\RuntimeException;

final class Issue99Cest
{
    public function failToCreateTableDuringMorph(MysqlTester $I): void
    {
        $I->wantToTest('Issue #99 - Exception during morph: create table');

        $throwable = new RuntimeException(
            'Failed to create table \'invalid_table\'. ' .
            'In \'InvalidTableMigration_100\' migration. ' .
            'DB error: SQLSTATE[42000]: Syntax error or access violation: 1075 Incorrect table definition; ' .
            'there can be only one auto column and it must be defined as a key'
        );

        $I->expectThrowable($throwable, function () use ($I) {
            $I->silentRun('issues/99');
        });
    }
}
