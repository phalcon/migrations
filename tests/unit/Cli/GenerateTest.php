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
use Phalcon\Db\Reference;
use Phalcon\Migrations\Tests\AbstractCliTestCase;

final class GenerateTest extends AbstractCliTestCase
{
    private string $configPath = 'tests/_data/cli/migrations.php';

    public function testTryWithoutDbConfig(): void
    {
        $directory = $this->getCliOutputDir();

        $this->runCommand('php bin/phalcon-migrations generate --directory=' . $directory);

        $this->assertInOutput('Phalcon Migrations');
        $this->assertInOutput("Error: Can't locate the configuration file.");
        $this->assertExitCode(1);
    }

    public function testGenerateEmptyDb(): void
    {
        $this->runCommand('php bin/phalcon-migrations generate --config=' . $this->configPath);

        $this->assertInOutput('Info: Nothing to generate. You should create tables first.');
        $this->assertExitCode(0);
    }

    public function testGenerateFirstVersion(): void
    {
        $this->getPhalconDb()->createTable('cli-first-test', '', [
            'columns' => [
                new Column('id', [
                    'type'    => Column::TYPE_INTEGER,
                    'size'    => 10,
                    'notNull' => true,
                ]),
                new Column('num_point', [
                    'type'    => Column::TYPE_FLOAT,
                    'notNull' => true,
                ]),
            ],
        ]);

        $this->runCommand('php bin/phalcon-migrations generate --config=' . $this->configPath);

        $this->assertInOutput('Success: Version 1.0.0 was successfully generated');
        $this->assertExitCode(0);
    }

    public function testGenerateWithSkipRefSchema(): void
    {
        $schema = $_ENV['MYSQL_TEST_DB_DATABASE'];

        $this->createFKTables();

        $this->runCommand('php bin/phalcon-migrations generate --skip-ref-schema --config=' . $this->configPath);

        $this->assertInOutput('Success: Version 1.0.0 was successfully generated');
        $this->assertExitCode(0);

        $content = file_get_contents($this->getCliOutputDir('1.0.0/cli-skip-ref-schema.php'));

        $this->assertFalse(strpos($content, "'referencedSchema' => '$schema',"));
        $this->assertNotFalse(strpos($content, "'referencedTable' => 'client',"));
    }

    public function testGenerateWithRefSchema(): void
    {
        $schema = $_ENV['MYSQL_TEST_DB_DATABASE'];

        $this->createFKTables();

        $this->runCommand('php bin/phalcon-migrations generate --config=' . $this->configPath);

        $this->assertInOutput('Success: Version 1.0.0 was successfully generated');
        $this->assertExitCode(0);

        $content = file_get_contents($this->getCliOutputDir('1.0.0/cli-skip-ref-schema.php'));

        $this->assertNotFalse(strpos($content, "'referencedSchema' => '$schema',"));
        $this->assertNotFalse(strpos($content, "'referencedTable' => 'client',"));
    }

    private function createFKTables(): void
    {
        $schema = $_ENV['MYSQL_TEST_DB_DATABASE'];

        $this->getPhalconDb()->createTable('client', $schema, [
            'columns' => [
                new Column('id', [
                    'type'    => Column::TYPE_INTEGER,
                    'size'    => 11,
                    'notNull' => true,
                    'primary' => true,
                ]),
            ],
        ]);

        $this->getPhalconDb()->createTable('cli-skip-ref-schema', $schema, [
            'columns'    => [
                new Column('id', [
                    'type'    => Column::TYPE_INTEGER,
                    'size'    => 10,
                    'notNull' => true,
                ]),
                new Column('clientId', [
                    'type'    => Column::TYPE_INTEGER,
                    'size'    => 11,
                    'notNull' => true,
                ]),
            ],
            'references' => [
                new Reference('fk_client_1', [
                    'referencedSchema'  => $schema,
                    'referencedTable'   => 'client',
                    'columns'           => ['clientId'],
                    'referencedColumns' => ['id'],
                    'onUpdate'          => 'NO ACTION',
                    'onDelete'          => 'NO ACTION',
                ]),
            ],
        ]);
    }
}
