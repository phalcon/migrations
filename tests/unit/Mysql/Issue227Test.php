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

use function array_keys;
use function ob_end_clean;
use function ob_start;

/**
 * @see https://github.com/phalcon/migrations/issues/227
 */
final class Issue227Test extends AbstractMysqlTestCase
{
    private const CHILD = 'issue_227_child';

    public function testRunRemovesTheLastForeignKeyAndIndex(): void
    {
        $db = $this->getPhalconDb();
        $db->execute('CREATE TABLE `issue_227_parent` (`id` INT NOT NULL, PRIMARY KEY (`id`))');
        $db->execute(
            'CREATE TABLE `' . self::CHILD . '` (`id` INT NOT NULL, `parent_id` INT NOT NULL, PRIMARY KEY (`id`))'
        );

        // The new version of the table: no foreign key and no other index
        $migrationsDir = $this->getOutputDir(__FUNCTION__);
        ob_start();
        try {
            Migrations::generate([
                'migrationsDir' => $migrationsDir,
                'config'        => static::getMigrationsConfig(),
                'tableName'     => self::CHILD,
            ]);

            // The database at the old version: with the foreign key and an index
            $db->execute(
                'ALTER TABLE `' . self::CHILD . '` ADD CONSTRAINT `fk_issue_227_parent`
                 FOREIGN KEY (`parent_id`) REFERENCES `issue_227_parent` (`id`)'
            );
            $db->execute('CREATE INDEX `idx_issue_227_id_parent` ON `' . self::CHILD . '` (`id`, `parent_id`)');

            Migrations::run([
                'migrationsDir'  => $migrationsDir,
                'config'         => static::getMigrationsConfig(),
                'migrationsInDb' => true,
            ]);
        } finally {
            ob_end_clean();
            Migrations::resetStorage();
            $this->removeDir($migrationsDir);
        }

        $this->assertSame([], $this->describeReferences(self::CHILD));
        $this->assertSame(['PRIMARY'], array_keys($this->describeIndexes(self::CHILD)));
    }
}
