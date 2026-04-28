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

use Phalcon\Migrations\Db\Column;
use Phalcon\Migrations\Db\Reference;
use Phalcon\Migrations\Tests\AbstractCliTestCase;

final class RunTest extends AbstractCliTestCase
{
    private string $configPath = 'tests/_data/cli/migrations.php';

    public function testRunCommandWithoutDbConfig(): void
    {
        $directory = $this->getOutputDir();

        $this->runCommand('php bin/phalcon-migrations run --directory=' . $directory);

        $this->assertInOutput('Phalcon Migrations');
        $this->assertInOutput("Error: Can't locate the configuration file.");
        $this->assertExitCode(1);
    }

    public function testGenerateAndRun(): void
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

        $this->runCommand('php bin/phalcon-migrations run --config=' . $this->configPath);
        $this->assertInOutput('Success: Version 1.0.0 was successfully migrated');
        $this->assertExitCode(0);
    }

    public function testExpectForeignKeyDbError1822(): void
    {
        $table1 = 'z-client';
        $table2 = 'skip-foreign-keys';
        $schema = $_ENV['MYSQL_TEST_DB_DATABASE'];

        $this->createTablesWithForeignKey($table1, $table2);

        $this->runCommand('php bin/phalcon-migrations generate --config=' . $this->configPath);
        $this->assertInOutput('Success: Version 1.0.0 was successfully generated');
        $this->assertExitCode(0);

        $migrationContent = file_get_contents($this->getOutputPath('1.0.0/' . $table2 . '.php'));
        $this->assertNotFalse(strpos($migrationContent, "'referencedTable' => '" . $table1 . "',"));

        $this->getPhalconDb()->dropTable($table2);
        $this->getPhalconDb()->dropPrimaryKey($table1, $schema);

        $this->runCommand('php bin/phalcon-migrations run --config=' . $this->configPath);
        $this->assertInOutput('SQLSTATE[HY000]: General error: 1822 Failed to add the foreign key constraint');
        $this->assertExitCode(1);
    }

    public function testExpectForeignKeyDbError1824(): void
    {
        $table1 = 'z-client';
        $table2 = 'skip-foreign-keys';

        $this->createTablesWithForeignKey($table1, $table2);

        $this->runCommand('php bin/phalcon-migrations generate --config=' . $this->configPath);
        $this->assertInOutput('Success: Version 1.0.0 was successfully generated');
        $this->assertExitCode(0);

        $migrationContent = file_get_contents($this->getOutputPath('1.0.0/' . $table2 . '.php'));
        $this->assertNotFalse(strpos($migrationContent, "'referencedTable' => '" . $table1 . "',"));

        $this->getPhalconDb()->dropTable($table2);
        $this->getPhalconDb()->dropTable($table1);

        $this->runCommand('php bin/phalcon-migrations run --config=' . $this->configPath);
        $this->assertInOutput('SQLSTATE[HY000]: General error: 1824 Failed to open the referenced table');
        $this->assertExitCode(1);
    }

    public function testExpectForeignKeyDbError3734(): void
    {
        $table1 = 'z-client';
        $table2 = 'skip-foreign-keys';
        $schema = $_ENV['MYSQL_TEST_DB_DATABASE'];

        $this->createTablesWithForeignKey($table1, $table2);

        $this->runCommand('php bin/phalcon-migrations generate --config=' . $this->configPath);
        $this->assertInOutput('Success: Version 1.0.0 was successfully generated');
        $this->assertExitCode(0);

        $migrationContent = file_get_contents($this->getOutputPath('1.0.0/' . $table2 . '.php'));
        $this->assertNotFalse(strpos($migrationContent, "'referencedTable' => '" . $table1 . "',"));

        $this->getPhalconDb()->dropTable($table2);
        $this->getPhalconDb()->addColumn($table1, $schema, new Column('stub', ['type' => Column::TYPE_INTEGER]));
        $this->getPhalconDb()->dropColumn($table1, $schema, 'id');

        $this->runCommand('php bin/phalcon-migrations run --config=' . $this->configPath);
        $this->assertInOutput('SQLSTATE[HY000]: General error: 3734 Failed to add the foreign key constraint');
        $this->assertExitCode(1);
    }

    public function testSkipForeignKeys(): void
    {
        $table1 = 'client';
        $table2 = 'x-skip-foreign-keys';

        $this->createTablesWithForeignKey($table1, $table2);

        $this->runCommand('php bin/phalcon-migrations generate --config=' . $this->configPath);
        $this->assertInOutput('Success: Version 1.0.0 was successfully generated');
        $this->assertExitCode(0);

        $migrationContent = file_get_contents($this->getOutputPath('1.0.0/' . $table2 . '.php'));
        $this->assertNotFalse(strpos($migrationContent, "'referencedTable' => 'client',"));

        $this->getPhalconDb()->dropTable($table2);
        $this->getPhalconDb()->dropTable($table1);

        $this->runCommand('php bin/phalcon-migrations run --skip-foreign-checks --config=' . $this->configPath);
        $this->assertInOutput('Success: Version 1.0.0 was successfully migrated');
        $this->assertExitCode(0);
    }

    private function createTablesWithForeignKey(string $table1, string $table2): void
    {
        $schema = $_ENV['MYSQL_TEST_DB_DATABASE'];

        $this->getPhalconDb()->createTable($table1, $schema, [
            'columns' => [
                new Column('id', [
                    'type'    => Column::TYPE_INTEGER,
                    'size'    => 11,
                    'notNull' => true,
                    'primary' => true,
                ]),
            ],
        ]);

        $this->getPhalconDb()->createTable($table2, $schema, [
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
                    'referencedTable'   => $table1,
                    'columns'           => ['clientId'],
                    'referencedColumns' => ['id'],
                    'onUpdate'          => 'NO ACTION',
                    'onDelete'          => 'NO ACTION',
                ]),
            ],
        ]);
    }
}
