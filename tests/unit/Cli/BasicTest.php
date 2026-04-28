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

namespace Phalcon\Migrations\Tests\Unit\Cli;

use Phalcon\Migrations\Tests\AbstractCliTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class BasicTest extends AbstractCliTestCase
{
    public function testRunCommand(): void
    {
        $this->runCommand('php bin/phalcon-migrations');

        $this->assertInOutput('Phalcon Migrations');
        $this->assertInOutput('Help');
        $this->assertExitCode(0);
    }

    public static function helpArgumentsDataProvider(): array
    {
        return [
            ['help'],
            ['--help'],
            ['h'],
            ['?'],
            [null],
        ];
    }

    #[DataProvider('helpArgumentsDataProvider')]
    public function testRunHelp(string|null $argument): void
    {
        $command = 'php bin/phalcon-migrations' . ($argument !== null ? ' ' . $argument : '');

        $this->runCommand($command);

        $this->assertInOutput('Phalcon Migrations');
        $this->assertInOutput('Help');
        $this->assertExitCode(0);
    }
}
