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

namespace Phalcon\Migrations\Tests\Unit\Db\Adapter\Pdo;

use Codeception\Test\Unit;
use Phalcon\Db\Adapter\Pdo\AbstractPdo;
use Phalcon\Migrations\Db\Adapter\Pdo\PdoPostgresql;

final class PdoPostgresqlTest extends Unit
{
    public function testConstruct(): void
    {
        /** @var PdoPostgresql $class */
        $class = $this->createMock(PdoPostgresql::class);

        $this->assertInstanceOf(AbstractPdo::class, $class);
    }
}
