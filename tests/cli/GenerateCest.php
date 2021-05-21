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

final class GenerateCest
{
    /**
     * @param CliTester $I
     */
    public function tryWithoutDbConfig(CliTester $I): void
    {
        $directory = codecept_output_dir();

        $I->runShellCommand('php phalcon-migrations generate --directory=' . $directory, false);
        $I->seeInShellOutput('Phalcon Migrations');
        $I->seeInShellOutput("Error: Can't locate the configuration file.");
        $I->seeResultCodeIs(1);
    }

    /**
     * @param CliTester $I
     */
    public function generateEmptyDb(CliTester $I): void
    {
        $configPath = 'tests/_data/cli/migrations.php';

        $I->runShellCommand('php phalcon-migrations generate --config=' . $configPath);
        $I->seeInShellOutput('Info: Nothing to generate. You should create tables first.');
        $I->seeResultCodeIs(0);
    }

    /**
     * @param CliTester $I
     */
    public function generateFirstVersion(CliTester $I): void
    {
        $I->getPhalconDb()->createTable('cli-first-test', '', [
            'columns' => [
                new Column('id', [
                    'type' => Column::TYPE_INTEGER,
                    'size' => 10,
                    'notNull' => true,
                ]),
                new Column('num_point', [
                    'type' => Column::TYPE_FLOAT,
                    'notNull' => true,
                ]),
            ],
        ]);

        $configPath = 'tests/_data/cli/migrations.php';

        $I->runShellCommand('php phalcon-migrations generate --config=' . $configPath);
        $I->seeInShellOutput('Success: Version 1.0.0 was successfully generated');
        $I->seeResultCodeIs(0);
    }
}
