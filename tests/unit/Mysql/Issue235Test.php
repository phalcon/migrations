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

use Phalcon\Migrations\Tests\AbstractMysqlTestCase;

/**
 * @see https://github.com/phalcon/migrations/issues/235
 */
final class Issue235Test extends AbstractMysqlTestCase
{
    public function testListColumnsOfColumnsInMoreThanOneKey(): void
    {
        $db = $this->getPhalconDb();
        $db->execute(
            'CREATE TABLE `issue_235_parent` (
                `id` INT NOT NULL PRIMARY KEY,
                `code` INT NOT NULL,
                UNIQUE KEY `uq_code` (`code`)
            )'
        );
        $db->execute(
            'CREATE TABLE `issue_235_child` (
                `id` INT NOT NULL,
                `code` INT NOT NULL,
                `name` VARCHAR(10) NULL,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_child_code` (`code`),
                CONSTRAINT `fk_issue_235_id` FOREIGN KEY (`id`) REFERENCES `issue_235_parent` (`id`),
                CONSTRAINT `fk_issue_235_code` FOREIGN KEY (`code`) REFERENCES `issue_235_parent` (`code`)
            )'
        );

        $columns = $db->listColumns($_ENV['MYSQL_TEST_DB_DATABASE'], 'issue_235_child');

        $this->assertSame(['id', 'code', 'name'], array_keys($columns));
        $this->assertTrue($columns['id']->isPrimary());
        $this->assertTrue($columns['id']->isFirst());
        $this->assertFalse($columns['code']->isPrimary());
        $this->assertSame('id', $columns['code']->getAfterPosition());
        $this->assertSame('code', $columns['name']->getAfterPosition());
    }
}
