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

namespace Phalcon\Migrations\Tests\Unit\Mysql;

use Phalcon\Migrations\Exception\RuntimeException;
use Phalcon\Migrations\Tests\AbstractMysqlTestCase;

final class Issue99Test extends AbstractMysqlTestCase
{
    public function testFailToCreateTableDuringMorph(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage(
            'Failed to create table \'invalid_table\'. ' .
            'In \'InvalidTableMigration_100\' migration. ' .
            'DB error: SQLSTATE[42000]: Syntax error or access violation: 1075 Incorrect table definition; ' .
            'there can be only one auto column and it must be defined as a key'
        );

        $this->silentRun('issues/99');
    }
}
