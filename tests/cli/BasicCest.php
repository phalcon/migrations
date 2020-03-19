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

namespace Phalcon\Migrations\Tests\Cli;

use CliTester;
use Codeception\Example;

final class BasicCest
{
    /**
     * @param CliTester $I
     */
    public function runCommand(CliTester $I): void
    {
        $I->runShellCommand('php phalcon-migrations');
        $I->seeInShellOutput('Phalcon Migrations');
        $I->seeInShellOutput('Help');
        $I->seeResultCodeIs(0);
    }

    /**
     * @dataProvider helpArgumentsDataProvider
     *
     * @param CliTester $I
     * @param Example $example
     */
    public function runHelp(CliTester $I, Example $example): void
    {
        $command = join(' ', ['php phalcon-migrations', (string)$example[0]]);

        $I->runShellCommand($command);
        $I->seeInShellOutput('Phalcon Migrations');
        $I->seeInShellOutput('Help');
        $I->seeResultCodeIs(0);
    }

    /**
     * @return array
     */
    protected function helpArgumentsDataProvider(): array
    {
        return [
            ['help'],
            ['--help'],
            ['h'],
            ['?'],
            [null],
        ];
    }
}
