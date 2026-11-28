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

/**
 * The clean action with tables that refer to each other.
 *
 * @see https://github.com/phalcon/migrations/issues/72
 */
final class Issue72Test extends AbstractMysqlTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $db = $this->getPhalconDb();
        $db->execute('CREATE TABLE `issue_72_parent` (`id` INT NOT NULL PRIMARY KEY)');
        $db->execute(
            'CREATE TABLE `issue_72_child` (`id` INT NOT NULL PRIMARY KEY, `parent_id` INT NOT NULL,'
            . ' CONSTRAINT `fk_issue_72` FOREIGN KEY (`parent_id`) REFERENCES `issue_72_parent` (`id`))'
        );
        $db->execute('INSERT INTO `issue_72_parent` (`id`) VALUES (1)');
        $db->execute('INSERT INTO `issue_72_child` (`id`, `parent_id`) VALUES (1, 1)');
        Migrations::addCurrentVersion(['config' => static::getMigrationsConfig(), 'migrationsInDb' => true], '1.0.0');
    }

    public function testCleanDataOnlyOfTablesWithForeignKey(): void
    {
        $this->clean(['data-only' => true]);

        $this->assertNumRecords(0, 'issue_72_parent');
        $this->assertNumRecords(0, 'issue_72_child');
        $this->assertNumRecords(1, Migrations::MIGRATION_LOG_TABLE);
    }

    public function testCleanDropsTablesWithForeignKey(): void
    {
        $this->clean([]);

        $this->assertSame(
            [Migrations::MIGRATION_LOG_TABLE],
            $this->getPhalconDb()->listTables($_ENV['MYSQL_TEST_DB_DATABASE'])
        );
        $this->assertNumRecords(0, Migrations::MIGRATION_LOG_TABLE);
        $this->assertSame('1', (string) $this->getPhalconDb()->fetchOne('SELECT @@foreign_key_checks AS c')['c']);
    }

    private function clean(array $options): void
    {
        ob_start();
        try {
            Migrations::clean($options + ['config' => static::getMigrationsConfig(), 'migrationsInDb' => true]);
        } finally {
            ob_end_clean();
        }
    }
}
