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
 * A default that is a function call, for example gen_random_uuid().
 *
 * @see https://github.com/phalcon/migrations/issues/113
 */
final class Issue113Test extends AbstractPostgresqlTestCase
{
    public function testListColumnsReadsFunctionDefault(): void
    {
        $table = 'issue_113_read';
        $this->createTable($table);

        $columns = $this->getPhalconDb()->listColumns($this->getDefaultSchema(), $table);

        $this->assertSame('gen_random_uuid()', $columns['id']->getDefault());
        $this->assertTrue($columns['id']->isDefaultExpression());
        // A text that looks like a function is not an expression
        $this->assertSame('foo()', $columns['name']->getDefault());
        $this->assertFalse($columns['name']->isDefaultExpression());
    }

    public function testRunAppliesFunctionDefault(): void
    {
        $table = 'issue_113_change';
        $this->createTable($table);
        $expected = $this->describeTable($table);
        $this->generate($table);

        // The database at another version: no default
        $this->getPhalconDb()->execute(sprintf('ALTER TABLE %s ALTER COLUMN id DROP DEFAULT', $table));

        $this->runMigration($table);

        $this->assertSame($expected, $this->describeTable($table));
    }

    public function testRunCreatesTableWithFunctionDefault(): void
    {
        $table = 'issue_113_create';
        $this->createTable($table);
        $expected = $this->describeTable($table);
        $this->generate($table);

        $this->getPhalconDb()->dropTable($table, $this->getDefaultSchema());
        $this->runMigration($table);

        $this->assertSame($expected, $this->describeTable($table));
    }

    private function createTable(string $table): void
    {
        $this->getPhalconDb()->execute(
            sprintf(
                "CREATE TABLE %s (
                    id UUID NOT NULL DEFAULT gen_random_uuid() PRIMARY KEY,
                    name CHARACTER VARYING(10) NOT NULL DEFAULT 'foo()'
                )",
                $table
            )
        );
    }

    /**
     * The default and the null flag of each column, from the server
     */
    private function describeTable(string $table): array
    {
        return $this->getPhalconDb()->fetchAll(
            sprintf(
                "SELECT column_name AS name, column_default AS def, is_nullable AS nullable
                 FROM information_schema.columns
                 WHERE table_schema = '%s' AND table_name = '%s'
                 ORDER BY ordinal_position",
                $this->getDefaultSchema(),
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
