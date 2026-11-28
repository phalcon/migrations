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

use Phalcon\Migrations\Db\Column;
use Phalcon\Migrations\Db\Index;
use Phalcon\Migrations\Mvc\Model\Migration;
use Phalcon\Migrations\Tests\AbstractMysqlTestCase;
use Phalcon\Migrations\Tests\Fakes\Mvc\Model\MigrationFake;

/**
 * @see https://github.com/phalcon/migrations/issues/6
 */
final class Issue6Test extends AbstractMysqlTestCase
{
    private const CHILD = 'issue_6_child';

    protected function setUp(): void
    {
        parent::setUp();
        Migration::setup(static::getMigrationsConfig());

        $db = $this->getPhalconDb();
        $db->execute('CREATE TABLE `issue_6_parent` (`id` INT NOT NULL, PRIMARY KEY (`id`))');
        $db->execute(
            'CREATE TABLE `' . self::CHILD . '` (
                `id` INT NOT NULL,
                `parent_id` INT NOT NULL,
                PRIMARY KEY (`id`),
                CONSTRAINT `fk_issue_6_parent` FOREIGN KEY (`parent_id`) REFERENCES `issue_6_parent` (`id`)
            )'
        );
    }

    public function testMorphDropsTheForeignKeyOfADroppedColumn(): void
    {
        (new MigrationFake())->morphTable(self::CHILD, $this->definition() + ['references' => []]);

        $this->assertCount(1, $this->describeColumns(self::CHILD));
        $this->assertSame([], $this->describeReferences(self::CHILD));
    }

    /**
     * A file with no "references" key does not sync the foreign keys, but the
     * foreign key of a dropped column must go first.
     */
    public function testMorphDropsTheForeignKeyOfADroppedColumnWithNoReferencesKey(): void
    {
        (new MigrationFake())->morphTable(self::CHILD, $this->definition());

        $this->assertCount(1, $this->describeColumns(self::CHILD));
        $this->assertSame([], $this->describeReferences(self::CHILD));
    }

    private function definition(): array
    {
        return [
            'columns' => [
                new Column('id', ['type' => Column::TYPE_INTEGER, 'notNull' => true, 'first' => true]),
            ],
            'indexes' => [
                new Index('PRIMARY', ['id'], 'PRIMARY'),
            ],
        ];
    }
}
