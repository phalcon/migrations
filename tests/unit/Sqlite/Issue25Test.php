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

namespace Phalcon\Migrations\Tests\Unit\Sqlite;

use Phalcon\Migrations\Db\Adapter\AdapterFactory;
use Phalcon\Migrations\Db\Adapter\AdapterInterface;
use Phalcon\Migrations\Db\Connection;
use Phalcon\Migrations\Migrations;
use Phalcon\Migrations\Tests\AbstractTestCase;
use Phalcon\Migrations\Utils\Config;
use Phalcon\Migrations\Version\ItemCollection;

use function array_filter;
use function array_keys;
use function array_map;
use function array_values;
use function explode;
use function ob_end_clean;
use function ob_get_clean;
use function ob_start;

/**
 * The export action on SQLite: the foreign keys are in the CREATE TABLE.
 *
 * @see https://github.com/phalcon/migrations/issues/25
 */
final class Issue25Test extends AbstractTestCase
{
    private AdapterInterface $db;
    private string $dbname = '';

    protected function setUp(): void
    {
        parent::setUp();
        Migrations::resetStorage();
        ItemCollection::setType(ItemCollection::TYPE_INCREMENTAL);

        $this->dbname = $this->getOutputDir('issue-25-sqlite') . '/export.sqlite';
        $this->db     = AdapterFactory::create(Connection::fromConfig($this->config()));
        $this->db->execute('CREATE TABLE issue_25_parent (id INTEGER NOT NULL PRIMARY KEY, code INTEGER NOT NULL)');
        $this->db->execute(
            'CREATE TABLE issue_25_child (id INTEGER NOT NULL PRIMARY KEY, parent_id INTEGER NOT NULL,'
            . ' FOREIGN KEY (parent_id) REFERENCES issue_25_parent (id))'
        );
        $this->db->execute('CREATE INDEX issue_25_child_parent_idx ON issue_25_child (parent_id)');
    }

    protected function tearDown(): void
    {
        Migrations::resetStorage();
        parent::tearDown();
    }

    public function testExportCreatesSameSchema(): void
    {
        $dir = $this->getOutputDir('issue-25-sqlite-migrations');
        ob_start();
        try {
            Migrations::generate([
                'config'        => $this->config(),
                'migrationsDir' => [$dir],
                'tableName'     => '@',
                'version'       => '1.0.0',
            ]);
        } finally {
            ob_end_clean();
        }

        $expected = $this->describeSchema();

        ob_start();
        try {
            Migrations::export(['config' => $this->config(), 'migrationsDir' => [$dir], 'tableName' => '@']);
        } finally {
            $sql = (string) ob_get_clean();
        }

        $this->db->dropTables(['issue_25_child', 'issue_25_parent']);
        foreach (array_filter(array_map('trim', explode(";\n", $sql))) as $statement) {
            $this->db->execute($statement);
        }

        $this->assertSame($expected, $this->describeSchema());
    }

    private function config(): Config
    {
        return Config::fromArray([
            'database' => [
                'adapter' => 'sqlite',
                'dbname'  => $this->dbname,
            ],
        ]);
    }

    /**
     * The tables, the indexes and the foreign keys
     */
    private function describeSchema(): array
    {
        $schema = [];
        foreach (['issue_25_child', 'issue_25_parent'] as $table) {
            $references = [];
            foreach (array_values($this->db->listReferences('', $table)) as $reference) {
                $references[] = [$reference->getReferencedTable(), $reference->getColumns()];
            }

            $schema[$table] = [
                'columns'    => array_keys($this->db->listColumns('', $table)),
                'indexes'    => array_keys($this->db->listIndexes('', $table)),
                'references' => $references,
            ];
        }

        return $schema;
    }
}
