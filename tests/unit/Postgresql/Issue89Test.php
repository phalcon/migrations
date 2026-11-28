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

namespace Phalcon\Migrations\Tests\Unit\Postgresql;

use Phalcon\Migrations\Migrations;
use Phalcon\Migrations\Tests\AbstractPostgresqlTestCase;

use function ob_end_clean;
use function ob_start;
use function sprintf;

/**
 * The table morph writes the column comments on PostgreSQL.
 *
 * @see https://github.com/phalcon/migrations/issues/89
 */
final class Issue89Test extends AbstractPostgresqlTestCase
{
    private const LIVE_COMMENT = "COMMENT ON COLUMN %s.name IS 'old'";

    public function testRunAddsColumnWithComment(): void
    {
        $this->assertSame('new', $this->runMigration('issue_89_pg_add', 'new', 'ALTER TABLE %s DROP COLUMN name'));
    }

    public function testRunChangesComment(): void
    {
        $this->assertSame('new', $this->runMigration('issue_89_pg_change', 'new', self::LIVE_COMMENT));
    }

    public function testRunClearsComment(): void
    {
        $this->assertSame('', $this->runMigration('issue_89_pg_clear', '', self::LIVE_COMMENT));
    }

    public function testRunCreatesTableWithComment(): void
    {
        $this->assertSame('new', $this->runMigration('issue_89_pg_create', 'new', 'DROP TABLE %s'));
    }

    /**
     * Generates a migration of a table with the comment of the column "name",
     * changes the live table with $change, runs the migration, and returns the
     * comment of the column. Each test uses its own table, because the class
     * name comes from the table name (the MySQL tests use other names).
     */
    private function runMigration(string $table, string $comment, string $change): string
    {
        $db = $this->getPhalconDb();
        $db->execute(
            sprintf('CREATE TABLE %s (id INTEGER NOT NULL PRIMARY KEY, name CHARACTER VARYING(10) NOT NULL)', $table)
        );
        if ($comment !== '') {
            $db->execute(sprintf("COMMENT ON COLUMN %s.name IS '%s'", $table, $comment));
        }

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

        return $db->listColumns($this->getDefaultSchema(), $table)['name']->getComment();
    }
}
