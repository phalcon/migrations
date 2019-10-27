<?php
declare(strict_types=1);

namespace Phalcon\Migrations\Tests\Unit\Utils;

use Phalcon\Migrations\Utils\Nullify;
use PHPUnit\Framework\TestCase;

final class NullifyTest extends TestCase
{
    public function dataProvider(): array
    {
        return [
            [[1, 'test', 'NULL'], [1, 'test', null]],
            [[null, 'foo', 'bar'], [null, 'foo', 'bar']],
            [[null, 'null', 'Null'], [null, null, null]],
        ];
    }

    /**
     * @dataProvider dataProvider
     * @covers \Phalcon\Migrations\Utils\Nullify::__invoke
     *
     * @param mixed $actual
     * @param mixed $expected
     */
    public function testInvoke($actual, $expected): void
    {
        $nullify = new Nullify();
        $this->assertSame($expected, $nullify($actual));
    }
}
