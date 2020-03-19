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

use Codeception\Test\Unit;
use Phalcon\Db\Dialect\Postgresql;
use Phalcon\Migrations\Db\Dialect\DialectPostgresql;

final class DialectPostgresqlTest extends Unit
{
    public function testConstruct(): void
    {
        /** @var DialectPostgresql $class */
        $class = $this->createMock(DialectPostgresql::class);

        $this->assertInstanceOf(Postgresql::class, $class);
    }
}
