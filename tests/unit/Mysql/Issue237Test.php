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

namespace Phalcon\Migrations\Tests\Unit\Mysql;

use Phalcon\Migrations\Migrations;
use Phalcon\Migrations\Tests\AbstractMysqlTestCase;

use function ob_end_clean;
use function ob_get_clean;
use function ob_start;
use function sprintf;

/**
 * The table morph changes only the columns that are different from the file.
 *
 * @see https://github.com/phalcon/migrations/issues/237
 */
final class Issue237Test extends AbstractMysqlTestCase
{
    public function testRunAppliesChangeOfEnumValues(): void
    {
        $table = 'issue_237_values';
        $this->createTable($table);
        $this->generate($table);

        // The database at another version: the same size, other values
        $this->getPhalconDb()->execute(sprintf("ALTER TABLE `%s` MODIFY `kind` ENUM('a','c') NOT NULL", $table));

        $this->runMigration($table);

        $columns = $this->getPhalconDb()->listColumns($_ENV['MYSQL_TEST_DB_DATABASE'], $table);
        $this->assertSame(['a', 'b'], $columns['kind']->getOptions());
    }

    public function testRunDoesNotChangeSameColumns(): void
    {
        $table = 'issue_237_same';
        $this->createTable($table);
        $this->generate($table);

        $output = $this->runMigration($table);

        $this->assertStringNotContainsString('CHANGE COLUMN', $output);
    }

    private function createTable(string $table): void
    {
        $this->getPhalconDb()->execute(
            sprintf(
                "CREATE TABLE `%s` (
                    `id` INT NOT NULL,
                    `kind` ENUM('a','b') NOT NULL,
                    `os` SET('x','y') NOT NULL,
                    PRIMARY KEY (`id`)
                )",
                $table
            )
        );
    }

    /**
     * Each test uses its own table, because the class name comes from the
     * table name.
     */
    private function generate(string $table): void
    {
        ob_start();
        try {
            Migrations::generate([
                'config'        => static::getMigrationsConfig(),
                'migrationsDir' => $this->getOutputDir($table),
                'tableName'     => $table,
                'version'       => '1.0.0',
            ]);
        } finally {
            ob_end_clean();
        }
    }

    /**
     * Runs the migration with "verbose", and returns the output (each SQL
     * statement)
     */
    private function runMigration(string $table): string
    {
        ob_start();
        try {
            Migrations::run([
                'config'         => static::getMigrationsConfig(),
                'migrationsDir'  => $this->getOutputPath($table),
                'migrationsInDb' => true,
                'verbose'        => true,
            ]);
        } finally {
            $output = (string) ob_get_clean();
        }

        return $output;
    }
}
