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
use Phalcon\Migrations\Version\ItemCollection;

use function array_filter;
use function array_keys;
use function array_map;
use function explode;
use function ob_end_clean;
use function ob_get_clean;
use function ob_start;
use function sort;

/**
 * The export action prints the SQL of the schema of the migration files.
 *
 * @see https://github.com/phalcon/migrations/issues/25
 */
final class Issue25Test extends AbstractMysqlTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        ItemCollection::setType(ItemCollection::TYPE_INCREMENTAL);
    }

    public function testExportCreatesSameSchema(): void
    {
        $prefix = 'issue_25_my_same';
        $this->createTables($prefix);
        $dir = $this->getOutputDir($prefix);
        $this->generate($dir, '1.0.0');
        $expected = $this->describeSchema($prefix);

        $sql = $this->export($dir, null);
        $this->getPhalconDb()->dropTables([$prefix . '_child', $prefix . '_parent'], $_ENV['MYSQL_TEST_DB_DATABASE']);
        foreach ($this->statements($sql) as $statement) {
            $this->getPhalconDb()->execute($statement);
        }

        $this->assertSame($expected, $this->describeSchema($prefix));
    }

    public function testExportOfVersionUsesFilesOfThatVersion(): void
    {
        $prefix = 'issue_25_my_version';
        $this->createTables($prefix);
        $dir = $this->getOutputDir($prefix);
        $this->generate($dir, '1.0.0');
        $this->getPhalconDb()->execute('ALTER TABLE `' . $prefix . '_parent` ADD `extra` INT NULL');
        $this->generate($dir, '1.0.1');

        $this->assertStringNotContainsString('`extra`', $this->export($dir, '1.0.0'));
        $this->assertStringContainsString('`extra`', $this->export($dir, null));

        // The export changes nothing in the database
        $columns = $this->getPhalconDb()->listColumns($_ENV['MYSQL_TEST_DB_DATABASE'], $prefix . '_parent');
        $this->assertArrayHasKey('extra', $columns);
    }

    /**
     * Each test uses its own tables, because the class name comes from the
     * table name. "child" comes before "parent": its foreign key needs the
     * order tables, then foreign keys.
     */
    private function createTables(string $prefix): void
    {
        $db = $this->getPhalconDb();
        $db->execute(
            'CREATE TABLE `' . $prefix . '_parent` (`id` INT NOT NULL, `code` INT NOT NULL,'
            . ' PRIMARY KEY (`id`), UNIQUE KEY `uq_code` (`code`))'
        );
        $db->execute(
            'CREATE TABLE `' . $prefix . '_child` (`id` INT NOT NULL, `parent_id` INT NOT NULL, PRIMARY KEY (`id`),'
            . ' CONSTRAINT `fk_' . $prefix . '` FOREIGN KEY (`parent_id`) REFERENCES `' . $prefix . '_parent` (`id`))'
        );
    }

    /**
     * The columns, the indexes and the foreign keys of the two tables
     */
    private function describeSchema(string $prefix): array
    {
        $schema = [];
        foreach ([$prefix . '_child', $prefix . '_parent'] as $table) {
            $indexes = array_keys($this->describeIndexes($table));
            sort($indexes);
            $schema[$table] = [
                'columns'    => $this->getPhalconDb()->fetchAll(
                    "SELECT column_name AS name, column_type AS type, is_nullable AS nullable
                     FROM information_schema.columns
                     WHERE table_schema = DATABASE() AND table_name = '" . $table . "'
                     ORDER BY ordinal_position"
                ),
                'indexes'    => $indexes,
                'references' => array_keys($this->describeReferences($table)),
            ];
        }

        return $schema;
    }

    private function export(string $dir, ?string $version): string
    {
        ob_start();
        try {
            Migrations::export([
                'config'        => static::getMigrationsConfig(),
                'migrationsDir' => [$dir],
                'tableName'     => '@',
                'version'       => $version,
            ]);
        } finally {
            $output = (string) ob_get_clean();
        }

        return $output;
    }

    private function generate(string $dir, string $version): void
    {
        ob_start();
        try {
            Migrations::generate([
                'config'        => static::getMigrationsConfig(),
                'migrationsDir' => [$dir],
                'tableName'     => '@',
                'version'       => $version,
            ]);
        } finally {
            ob_end_clean();
        }
    }

    private function statements(string $sql): array
    {
        return array_filter(array_map('trim', explode(";\n", $sql)));
    }
}
