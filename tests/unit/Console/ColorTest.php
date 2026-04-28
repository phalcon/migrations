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

namespace Phalcon\Migrations\Tests\Unit\Console;

use Phalcon\Migrations\Console\Color;
use Phalcon\Migrations\Tests\AbstractTestCase;

final class ColorTest extends AbstractTestCase
{
    public function testIsSupportedShellReturnsBool(): void
    {
        $this->assertIsBool(Color::isSupportedShell());
    }

    public function testColorizeReturnsOriginalStringWhenShellNotSupported(): void
    {
        if (Color::isSupportedShell()) {
            $this->markTestSkipped('Shell supports colors in this environment');
        }

        $result = Color::colorize('hello');

        $this->assertSame('hello', $result);
    }

    public function testColorizeWithColorsWhenShellSupported(): void
    {
        if (!Color::isSupportedShell()) {
            $this->markTestSkipped('Shell does not support colors in this environment');
        }

        $result = Color::colorize('hello', Color::FG_GREEN);

        $this->assertStringContainsString('hello', $result);
        $this->assertStringContainsString("\033[", $result);
    }

    public function testHeadContainsMessage(): void
    {
        $result = Color::head('test message');

        $this->assertStringContainsString('test message', $result);
    }

    public function testErrorContainsMessage(): void
    {
        $result = Color::error('something went wrong');

        $this->assertStringContainsString('Error: something went wrong', $result);
    }

    public function testErrorWithCustomPrefix(): void
    {
        $result = Color::error('something went wrong', 'Prefix: ');

        $this->assertStringContainsString('Prefix: something went wrong', $result);
    }

    public function testFatalContainsMessage(): void
    {
        $result = Color::fatal('critical failure');

        $this->assertStringContainsString('Fatal Error: critical failure', $result);
    }

    public function testFatalWithCustomPrefix(): void
    {
        $result = Color::fatal('critical failure', 'Custom: ');

        $this->assertStringContainsString('Custom: critical failure', $result);
    }

    public function testSuccessContainsMessage(): void
    {
        $result = Color::success('all done');

        $this->assertStringContainsString('Success: all done', $result);
    }

    public function testInfoContainsMessage(): void
    {
        $result = Color::info('some info');

        $this->assertStringContainsString('Info: some info', $result);
    }

    public function testErrorReturnsThreeLines(): void
    {
        $result = Color::error('msg');

        $this->assertSame(3, substr_count($result, PHP_EOL));
    }

    public function testFatalReturnsThreeLines(): void
    {
        $result = Color::fatal('msg');

        $this->assertSame(3, substr_count($result, PHP_EOL));
    }

    public function testSuccessReturnsThreeLines(): void
    {
        $result = Color::success('msg');

        $this->assertSame(3, substr_count($result, PHP_EOL));
    }

    public function testInfoReturnsThreeLines(): void
    {
        $result = Color::info('msg');

        $this->assertSame(3, substr_count($result, PHP_EOL));
    }
}
