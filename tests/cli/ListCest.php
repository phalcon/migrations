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
use Phalcon\Db\Column;

final class ListCest
{
    /**
     * @param CliTester $I
     */
    public function runCommandWithoutDbConfig(CliTester $I): void
    {
        $directory = codecept_output_dir();

        $I->runShellCommand('php phalcon-migrations list --directory=' . $directory, false);
        $I->seeInShellOutput('Phalcon Migrations');
        $I->seeInShellOutput("Error: Can't locate the configuration file.");
        $I->seeResultCodeIs(1);
    }

    /**
     * @param CliTester $I
     */
    public function runList(CliTester $I): void
    {
        $I->getPhalconDb()->createTable('cli-first-test', '', [
            'columns' => [
                new Column('id', [
                    'type' => Column::TYPE_INTEGER,
                    'size' => 10,
                    'notNull' => true,
                ]),
            ],
        ]);

        $configPath = 'tests/_data/cli/migrations.php';

        $I->runShellCommand('php phalcon-migrations generate --config=' . $configPath);
        $I->seeInShellOutput('Success: Version 1.0.0 was successfully generated');
        $I->seeResultCodeIs(0);

        $I->runShellCommand('php phalcon-migrations list --config=' . $configPath);
        $I->seeInShellOutput('Phalcon Migrations');
        $I->seeInShellOutput('│ Version');
        $I->seeInShellOutput('│ 1.0.0');
        $I->seeResultCodeIs(0);
    }
}
