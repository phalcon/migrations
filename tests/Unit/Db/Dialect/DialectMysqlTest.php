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

namespace Phalcon\Migrations\Tests\Unit\Db\Dialect;

use Phalcon\Db\Dialect\Mysql;
use Phalcon\Migrations\Db\Dialect\DialectMysql;
use PHPUnit\Framework\TestCase;

final class DialectMysqlTest extends TestCase
{
    public function testConstruct(): void
    {
        /** @var DialectMysql $class */
        $class = $this->createMock(DialectMysql::class);

        $this->assertInstanceOf(Mysql::class, $class);
    }
}
