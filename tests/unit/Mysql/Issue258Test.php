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
use function ob_start;
use function sprintf;

/**
 * @see https://github.com/phalcon/migrations/issues/258
 */
final class Issue258Test extends AbstractMysqlTestCase
{
    public function testListColumnsReadsOnUpdate(): void
    {
        $table = 'issue_258_read';
        $this->createTable($table);
        $this->getPhalconDb()->execute(
            sprintf('ALTER TABLE `%s` ADD `precise` DATETIME(6) NULL ON UPDATE CURRENT_TIMESTAMP(6)', $table)
        );

        $columns = $this->getPhalconDb()->listColumns($_ENV['MYSQL_TEST_DB_DATABASE'], $table);

        $this->assertSame('CURRENT_TIMESTAMP', $columns['updated']->getOnUpdate());
        $this->assertNull($columns['created']->getOnUpdate());
        // A precision needs the precision of the column, which is not read
        $this->assertNull($columns['precise']->getOnUpdate());
    }

    public function testRunAppliesOnUpdate(): void
    {
        $table = 'issue_258_change';
        $this->createTable($table);
        $expected = $this->describeTable($table);
        $this->generate($table);

        // The database at another version: no ON UPDATE
        $this->getPhalconDb()->execute(
            sprintf('ALTER TABLE `%s` MODIFY `updated` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP', $table)
        );

        $this->runMigration($table);

        $this->assertSame($expected, $this->describeTable($table));
    }

    public function testRunCreatesTableWithOnUpdate(): void
    {
        $table = 'issue_258_create';
        $this->createTable($table);
        $expected = $this->describeTable($table);
        $this->generate($table);

        $this->getPhalconDb()->dropTable($table, $_ENV['MYSQL_TEST_DB_DATABASE']);
        $this->runMigration($table);

        $this->assertSame($expected, $this->describeTable($table));
    }

    private function createTable(string $table): void
    {
        $this->getPhalconDb()->execute(
            sprintf(
                'CREATE TABLE `%s` (
                    `id` INT NOT NULL,
                    `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                    `updated` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    PRIMARY KEY (`id`)
                )',
                $table
            )
        );
    }

    /**
     * The type, the default and the extra of each column, from the server
     */
    private function describeTable(string $table): array
    {
        return $this->getPhalconDb()->fetchAll(
            sprintf(
                "SELECT column_name AS name, column_type AS type, column_default AS def, extra AS extra
                 FROM information_schema.columns
                 WHERE table_schema = DATABASE() AND table_name = '%s'
                 ORDER BY ordinal_position",
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

    private function runMigration(string $table): void
    {
        ob_start();
        try {
            Migrations::run([
                'config'         => static::getMigrationsConfig(),
                'migrationsDir'  => $this->getOutputPath($table),
                'migrationsInDb' => true,
            ]);
        } finally {
            ob_end_clean();
        }
    }
}
