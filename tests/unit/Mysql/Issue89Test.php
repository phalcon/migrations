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
 * The table morph applies a change of a column comment.
 *
 * @see https://github.com/phalcon/migrations/issues/89
 */
final class Issue89Test extends AbstractMysqlTestCase
{
    private const LIVE_COMMENT = "ALTER TABLE `%s` MODIFY `name` VARCHAR(10) NOT NULL COMMENT 'old'";

    public function testRunChangesComment(): void
    {
        $this->assertSame('new', $this->runMigration('issue_89_change', "COMMENT 'new'", self::LIVE_COMMENT));
    }

    public function testRunClearsComment(): void
    {
        $this->assertSame('', $this->runMigration('issue_89_clear', '', self::LIVE_COMMENT));
    }

    /**
     * Generates a migration of a table with the comment of the column "name",
     * changes the live table with $change, runs the migration, and returns the
     * comment of the column. Each test uses its own table, because the class
     * name comes from the table name.
     */
    private function runMigration(string $table, string $comment, string $change): string
    {
        $db = $this->getPhalconDb();
        $db->execute(
            sprintf(
                'CREATE TABLE `%s` (`id` INT NOT NULL, `name` VARCHAR(10) NOT NULL %s, PRIMARY KEY (`id`))',
                $table,
                $comment
            )
        );

        $migrationsDir = $this->getOutputDir($table);
        ob_start();
        try {
            Migrations::generate([
                'config'        => static::getMigrationsConfig(),
                'migrationsDir' => $migrationsDir,
                'tableName'     => $table,
                'version'       => '1.0.0',
            ]);

            $db->execute(sprintf($change, $table));

            Migrations::run([
                'config'         => static::getMigrationsConfig(),
                'migrationsDir'  => $migrationsDir,
                'migrationsInDb' => true,
            ]);
        } finally {
            ob_end_clean();
        }

        return $db->listColumns($_ENV['MYSQL_TEST_DB_DATABASE'], $table)['name']->getComment();
    }
}
