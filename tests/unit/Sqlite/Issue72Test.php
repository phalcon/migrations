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

use function array_keys;
use function ob_end_clean;
use function ob_start;

/**
 * @see https://github.com/phalcon/migrations/issues/72
 */
final class Issue72Test extends AbstractTestCase
{
    private AdapterInterface $db;
    private string $dbname = '';

    protected function setUp(): void
    {
        parent::setUp();
        Migrations::resetStorage();
        ItemCollection::setType(ItemCollection::TYPE_INCREMENTAL);

        $this->dbname = $this->getOutputDir('issue-72') . '/clean.sqlite';
        $this->db     = AdapterFactory::create(Connection::fromConfig($this->config()));
        $this->db->execute('CREATE TABLE parent (id INTEGER NOT NULL PRIMARY KEY)');
        $this->db->execute(
            'CREATE TABLE child (id INTEGER NOT NULL PRIMARY KEY, parent_id INTEGER NOT NULL,'
            . ' FOREIGN KEY (parent_id) REFERENCES parent (id))'
        );
        $this->db->execute('INSERT INTO parent (id) VALUES (1)');
        $this->db->execute('INSERT INTO child (id, parent_id) VALUES (1, 1)');

        // The log table with one version
        Migrations::addCurrentVersion($this->options(), '1.0.0');
    }

    protected function tearDown(): void
    {
        Migrations::resetStorage();
        parent::tearDown();
    }

    public function testCleanDataOnlyKeepsTablesAndLog(): void
    {
        $this->clean(['data-only' => true]);

        $this->assertSame(['child', 'parent', Migrations::MIGRATION_LOG_TABLE], $this->db->listTables(''));
        $this->assertSame([], $this->db->fetchAll('SELECT id FROM parent'));
        $this->assertSame([], $this->db->fetchAll('SELECT id FROM child'));
        $this->assertSame(['1.0.0'], $this->completedVersions());
    }

    public function testCleanDropsAllTablesAndResetsLog(): void
    {
        $this->clean([]);

        $this->assertSame([Migrations::MIGRATION_LOG_TABLE], $this->db->listTables(''));
        $this->assertSame([], $this->completedVersions());
    }

    public function testCleanDropsOneTableAndKeepsLog(): void
    {
        $this->clean(['tableName' => 'child,missing,' . Migrations::MIGRATION_LOG_TABLE]);

        $this->assertSame(['parent', Migrations::MIGRATION_LOG_TABLE], $this->db->listTables(''));
        $this->assertSame(['1.0.0'], $this->completedVersions());
    }

    private function clean(array $options): void
    {
        ob_start();
        try {
            Migrations::clean($options + $this->options());
        } finally {
            ob_end_clean();
        }
    }

    private function completedVersions(): array
    {
        return array_keys(Migrations::getCompletedVersions($this->options()));
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

    private function options(): array
    {
        return [
            'config'         => $this->config(),
            'migrationsInDb' => true,
        ];
    }
}
