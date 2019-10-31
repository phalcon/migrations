<?php
declare(strict_types=1);

namespace Phalcon\Migrations\Tests\Unit\Db\Adapter\Pdo;

use Phalcon\Db\Adapter\Pdo\AbstractPdo;
use Phalcon\Migrations\Db\Adapter\Pdo\PdoPostgresql;
use PHPUnit\Framework\TestCase;

final class PdoPostgresqlTest extends TestCase
{
    public function testConstruct(): void
    {
        /** @var PdoPostgresql $class */
        $class = $this->createMock(PdoPostgresql::class);

        $this->assertInstanceOf(AbstractPdo::class, $class);
    }
}
