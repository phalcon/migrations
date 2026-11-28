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

namespace Phalcon\Migrations\Tests\Unit\Version;

use Phalcon\Migrations\Db\Adapter\AdapterFactory;
use Phalcon\Migrations\Db\Connection;
use Phalcon\Migrations\Migrations;
use Phalcon\Migrations\Tests\AbstractTestCase;
use Phalcon\Migrations\Utils\Config;
use Phalcon\Migrations\Version\ItemCollection;

use function array_keys;
use function sort;

/**
 * The option "log-table" sets the name of the log table, so that two sets of
 * migrations can have their own log in one database.
 *
 * @see https://github.com/phalcon/migrations/issues/60
 */
final class Issue60Test extends AbstractTestCase
{
    private string $dbname = '';

    protected function setUp(): void
    {
        parent::setUp();
        Migrations::resetStorage();
        ItemCollection::setType(ItemCollection::TYPE_INCREMENTAL);
        $this->dbname = $this->getOutputDir('issue-60') . '/log.sqlite';
    }

    protected function tearDown(): void
    {
        Migrations::resetStorage();
        parent::tearDown();
    }

    public function testDbLogQuotesLogTable(): void
    {
        $options = $this->options('app-migrations');

        Migrations::addCurrentVersion($options, '1.0.1');
        Migrations::addCurrentVersion($options, '1.0.2');
        Migrations::removeCurrentVersion($options, '1.0.2');

        $this->assertSame(['1.0.1'], array_keys(Migrations::getCompletedVersions($options)));
        $this->assertSame(['app-migrations'], $this->tables());
    }

    public function testDbLogUsesLogTable(): void
    {
        Migrations::addCurrentVersion($this->options('app_migrations'), '1.0.1');

        $this->assertSame(['app_migrations'], $this->tables());
    }

    public function testTwoLogTablesInOneDatabaseAreSeparate(): void
    {
        $first  = $this->options('first_log');
        $second = $this->options('second_log');

        Migrations::addCurrentVersion($first, '1.0.1');
        Migrations::resetStorage();
        Migrations::addCurrentVersion($second, '1.0.2');
        Migrations::resetStorage();

        $this->assertSame('1.0.1', Migrations::getCurrentVersion($first)->getVersion());
        Migrations::resetStorage();
        $this->assertSame('1.0.2', Migrations::getCurrentVersion($second)->getVersion());
        $this->assertSame(['first_log', 'second_log'], $this->tables());
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

    private function options(string $logTable): array
    {
        return [
            'config'         => $this->config(),
            'log-table'      => $logTable,
            'migrationsInDb' => true,
        ];
    }

    private function tables(): array
    {
        $tables = AdapterFactory::create(Connection::fromConfig($this->config()))->listTables('');
        sort($tables);

        return $tables;
    }
}
