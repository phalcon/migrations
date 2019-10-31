<?php
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
