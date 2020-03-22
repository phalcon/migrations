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
use Phalcon\Db\Reference;

final class GenerateCest
{
    /**
     * Path to migrations config
     *
     * @var string
     */
    private $configPath = 'tests/_data/cli/migrations.php';

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
        $I->runShellCommand('php phalcon-migrations generate --config=' . $this->configPath);
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
            ],
        ]);

        $I->runShellCommand('php phalcon-migrations generate --config=' . $this->configPath);
        $I->seeInShellOutput('Success: Version 1.0.0 was successfully generated');
        $I->seeResultCodeIs(0);
    }

    /**
     * @param CliTester $I
     */
    public function generateWithSkipRefSchema(CliTester $I): void
    {
        $I->wantToTest('generate with --skip-ref-schema option');

        $schema = getenv('MYSQL_TEST_DB_DATABASE');

        $this->createFKTables($I);

        $I->runShellCommand('php phalcon-migrations generate --skip-ref-schema --config=' . $this->configPath);
        $I->seeInShellOutput('Success: Version 1.0.0 was successfully generated');
        $I->seeResultCodeIs(0);

        $content = file_get_contents(codecept_output_dir('1.0.0/cli-skip-ref-schema.php'));

        $I->assertFalse(strpos($content, "'referencedSchema' => '$schema',"));
        $I->assertNotFalse(strpos($content, "'referencedTable' => 'client',"));
    }

    /**
     * @param CliTester $I
     */
    public function generateWithRefSchema(CliTester $I): void
    {
        $I->wantToTest('generate with referencedSchema');

        $schema = getenv('MYSQL_TEST_DB_DATABASE');

        $this->createFKTables($I);

        $I->runShellCommand('php phalcon-migrations generate --config=' . $this->configPath);
        $I->seeInShellOutput('Success: Version 1.0.0 was successfully generated');
        $I->seeResultCodeIs(0);

        $content = file_get_contents(codecept_output_dir('1.0.0/cli-skip-ref-schema.php'));

        $I->assertNotFalse(strpos($content, "'referencedSchema' => '$schema',"));
        $I->assertNotFalse(strpos($content, "'referencedTable' => 'client',"));
    }

    /**
     * @param CliTester $I
     */
    protected function createFKTables(CliTester $I): void
    {
        $schema = getenv('MYSQL_TEST_DB_DATABASE');
        $I->getPhalconDb()->createTable('client', $schema, [
            'columns' => [
                new Column('id', [
                    'type' => Column::TYPE_INTEGER,
                    'size' => 11,
                    'notNull' => true,
                    'primary' => true,
                ]),
            ],
        ]);

        $I->getPhalconDb()->createTable('cli-skip-ref-schema', $schema, [
            'columns' => [
                new Column('id', [
                    'type' => Column::TYPE_INTEGER,
                    'size' => 10,
                    'notNull' => true,
                ]),
                new Column('clientId', [
                    'type' => Column::TYPE_INTEGER,
                    'size' => 11,
                    'notNull' => true,
                ]),
            ],
            'references' => [
                new Reference(
                    'fk_client_1',
                    [
                        'referencedSchema' => $schema,
                        'referencedTable' => 'client',
                        'columns' => ['clientId'],
                        'referencedColumns' => ['id'],
                        'onUpdate' => 'NO ACTION',
                        'onDelete' => 'NO ACTION',
                    ]
                ),
            ],
        ]);
    }
}
