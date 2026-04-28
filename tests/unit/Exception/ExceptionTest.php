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

namespace Phalcon\Migrations\Tests\Unit\Exception;

use Phalcon\Migrations\Console\Commands\CommandsException;
use Phalcon\Migrations\Db\Column;
use Phalcon\Migrations\Exception\Db\UnknownColumnTypeException;
use Phalcon\Migrations\Exception\InvalidArgumentException;
use Phalcon\Migrations\Exception\RuntimeException;
use Phalcon\Migrations\Script\ScriptException;
use Phalcon\Migrations\Tests\AbstractTestCase;

final class ExceptionTest extends AbstractTestCase
{
    public function testRuntimeExceptionIsThrowable(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('test message');

        throw new RuntimeException('test message');
    }

    public function testInvalidArgumentExceptionIsThrowable(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('invalid arg');

        throw new InvalidArgumentException('invalid arg');
    }

    public function testCommandsExceptionIsThrowable(): void
    {
        $this->expectException(CommandsException::class);
        $this->expectExceptionMessage('commands error');

        throw new CommandsException('commands error');
    }

    public function testScriptExceptionIsThrowable(): void
    {
        $this->expectException(ScriptException::class);
        $this->expectExceptionMessage('script error');

        throw new ScriptException('script error');
    }

    public function testUnknownColumnTypeExceptionContainsColumnInfo(): void
    {
        $column = new Column('my_column', ['type' => 9000]);

        try {
            throw new UnknownColumnTypeException($column);
        } catch (UnknownColumnTypeException $e) {
            $this->assertStringContainsString('my_column', $e->getMessage());
            $this->assertSame($column, $e->getColumn());
        }
    }

    public function testUnknownColumnTypeExceptionGetColumnReturnsColumn(): void
    {
        $column    = new Column('test_col', ['type' => Column::TYPE_INTEGER]);
        $exception = new UnknownColumnTypeException($column);

        $this->assertSame($column, $exception->getColumn());
        $this->assertSame('test_col', $exception->getColumn()->getName());
    }
}
