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

use Phalcon\Db\Column;
use Phalcon\Migrations\Tests\AbstractCliTestCase;

final class ListTest extends AbstractCliTestCase
{
    private string $configPath = 'tests/_data/cli/migrations.php';

    public function testRunCommandWithoutDbConfig(): void
    {
        $directory = $this->getOutputDir();

        $this->runCommand('php bin/phalcon-migrations list --directory=' . $directory);

        $this->assertInOutput('Phalcon Migrations');
        $this->assertInOutput("Error: Can't locate the configuration file.");
        $this->assertExitCode(1);
    }

    public function testRunList(): void
    {
        $this->getPhalconDb()->createTable('cli-first-test', '', [
            'columns' => [
                new Column('id', [
                    'type'    => Column::TYPE_INTEGER,
                    'size'    => 10,
                    'notNull' => true,
                ]),
            ],
        ]);

        $this->runCommand('php bin/phalcon-migrations generate --config=' . $this->configPath);
        $this->assertInOutput('Success: Version 1.0.0 was successfully generated');
        $this->assertExitCode(0);

        $this->runCommand('php bin/phalcon-migrations list --config=' . $this->configPath);
        $this->assertInOutput('Phalcon Migrations');
        $this->assertInOutput('│ Version');
        $this->assertInOutput('│ 1.0.0');
        $this->assertExitCode(0);
    }
}
