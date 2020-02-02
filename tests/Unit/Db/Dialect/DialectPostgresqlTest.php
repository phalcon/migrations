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

use Phalcon\Db\Dialect\Postgresql;
use Phalcon\Migrations\Db\Dialect\DialectPostgresql;
use PHPUnit\Framework\TestCase;

final class DialectPostgresqlTest extends TestCase
{
    public function testConstruct(): void
    {
        /** @var DialectPostgresql $class */
        $class = $this->createMock(DialectPostgresql::class);

        $this->assertInstanceOf(Postgresql::class, $class);
    }
}
