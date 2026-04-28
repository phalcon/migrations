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

namespace Phalcon\Migrations\Tests\Unit\Listeners;

use Phalcon\Migrations\Db\Connection;
use Phalcon\Migrations\Listeners\DbProfilerListener;
use Phalcon\Migrations\Tests\AbstractMysqlTestCase;

final class DbProfilerListenerTest extends AbstractMysqlTestCase
{
    public function testAttachSetsProfilerOnConnection(): void
    {
        $connection = Connection::fromConfig(static::getMigrationsConfig());
        $listener   = new DbProfilerListener();

        $listener->attach($connection);

        $this->assertTrue(true);
    }

    public function testListenerCanBeInstantiated(): void
    {
        $listener = new DbProfilerListener();

        $this->assertInstanceOf(DbProfilerListener::class, $listener);
    }
}
