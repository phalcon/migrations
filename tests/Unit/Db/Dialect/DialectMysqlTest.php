<?php
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
