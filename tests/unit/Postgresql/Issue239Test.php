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
use function ob_get_clean;
use function ob_start;
use function sprintf;

/**
 * @see https://github.com/phalcon/migrations/issues/239
 */
final class Issue239Test extends AbstractPostgresqlTestCase
{
    public function testRunChangesIndex(): void
    {
        $table = 'issue_239_index';
        $db    = $this->getPhalconDb();
        $db->execute(
            sprintf(
                'CREATE TABLE %s (id SERIAL PRIMARY KEY, name CHARACTER VARYING(10) NOT NULL,'
                . ' email CHARACTER VARYING(10) NOT NULL)',
                $table
            )
        );
        $db->execute(sprintf('CREATE INDEX issue_239_idx ON %s (name)', $table));
        $this->generate($table);

        // The database at another version: the index has other columns
        $db->execute('DROP INDEX issue_239_idx');
        $db->execute(sprintf('CREATE INDEX issue_239_idx ON %s (name, email)', $table));

        $this->runMigration($table);

        $indexes = $db->listIndexes($this->getDefaultSchema(), $table);
        $this->assertSame(['name'], $indexes['issue_239_idx']->getColumns());
    }

    public function testRunDoesNotChangeSerialColumn(): void
    {
        $table = 'issue_239_serial';
        $this->getPhalconDb()->execute(
            sprintf('CREATE TABLE %s (id SERIAL PRIMARY KEY, name CHARACTER VARYING(10) NOT NULL)', $table)
        );
        $this->generate($table);

        $output = $this->runMigration($table);

        $columns = $this->getPhalconDb()->listColumns($this->getDefaultSchema(), $table);
        $this->assertFalse($columns['id']->hasDefault());
        $this->assertTrue($columns['id']->isAutoIncrement());
        $this->assertStringNotContainsString('ALTER COLUMN', $output);
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
