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

final class RunCest
{
    /**
     * @var string
     */
    protected $configPath = 'tests/_data/cli/migrations.php';

    /**
     * @param CliTester $I
     */
    public function runCommandWithoutDbConfig(CliTester $I): void
    {
        $directory = codecept_output_dir();

        $I->runShellCommand('php phalcon-migrations run --directory=' . $directory, false);
        $I->seeInShellOutput('Phalcon Migrations');
        $I->seeInShellOutput("Error: Can't locate the configuration file.");
        $I->seeResultCodeIs(1);
    }

    /**
     * @param CliTester $I
     */
    public function generateAndRun(CliTester $I): void
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

        $I->runShellCommand('php phalcon-migrations run --config=' . $this->configPath);
        $I->seeInShellOutput('Success: Version 1.0.0 was successfully migrated');
        $I->seeResultCodeIs(0);
    }

    /**
     * @skip Some strange behavior with MySQL Server in Github Actions. Back to this test in some future
     *
     * @param CliTester $I
     */
    public function expectForeignKeyDbError(CliTester $I): void
    {
        $table1 = 'z-client';
        $table2 = 'skip-foreign-keys';

        $this->createTablesWithForeignKey($I, $table1, $table2);

        $I->runShellCommand('php phalcon-migrations generate --config=' . $this->configPath);
        $I->seeInShellOutput('Success: Version 1.0.0 was successfully generated');
        $I->seeResultCodeIs(0);

        $migrationContent = file_get_contents(codecept_output_dir('1.0.0/' . $table2 . '.php'));
        $I->assertNotFalse(strpos($migrationContent, "'referencedTable' => '" . $table1 . "',"));

        $I->getPhalconDb()->dropTable($table2);
        $I->getPhalconDb()->dropTable($table1);

        $I->runShellCommand('php phalcon-migrations run --config=' . $this->configPath, false);
        $I->seeInShellOutput('Fatal Error: SQLSTATE[HY000]: General error: 1215 Cannot add foreign key constraint');
        $I->seeResultCodeIs(1);
    }

    /**
     * @param CliTester $I
     */
    public function skipForeignKeys(CliTester $I): void
    {
        $table1 = 'client';
        $table2 = 'x-skip-foreign-keys';

        $this->createTablesWithForeignKey($I, $table1, $table2);

        $I->runShellCommand('php phalcon-migrations generate --config=' . $this->configPath);
        $I->seeInShellOutput('Success: Version 1.0.0 was successfully generated');
        $I->seeResultCodeIs(0);

        $migrationContent = file_get_contents(codecept_output_dir('1.0.0/' . $table2 . '.php'));
        $I->assertNotFalse(strpos($migrationContent, "'referencedTable' => 'client',"));

        $I->getPhalconDb()->dropTable($table2);
        $I->getPhalconDb()->dropTable($table1);

        $I->runShellCommand('php phalcon-migrations run --skip-foreign-checks --config=' . $this->configPath);
        $I->seeInShellOutput('Success: Version 1.0.0 was successfully migrated');
        $I->seeResultCodeIs(0);
    }

    /**
     * DRY!
     *
     * @param CliTester $I
     * @param string $table1
     * @param string $table2
     */
    protected function createTablesWithForeignKey(CliTester $I, string $table1, string $table2): void
    {
        $schema = getenv('MYSQL_TEST_DB_DATABASE');

        $I->getPhalconDb()->createTable($table1, $schema, [
            'columns' => [
                new Column('id', [
                    'type' => Column::TYPE_INTEGER,
                    'size' => 11,
                    'notNull' => true,
                    'primary' => true,
                ]),
            ],
        ]);

        $I->getPhalconDb()->createTable($table2, $schema, [
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
                        'referencedTable' => $table1,
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
