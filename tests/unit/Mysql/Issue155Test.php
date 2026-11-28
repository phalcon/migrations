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
use Phalcon\Migrations\Migrations;
use Phalcon\Migrations\Tests\AbstractMysqlTestCase;

use function ob_end_clean;
use function ob_start;

/**
 * The MySQL SET type, the JSON size, enum values with a comma or a quote, and
 * the CURRENT_TIMESTAMP defaults (B17).
 *
 * @see https://github.com/phalcon/migrations/issues/155
 */
final class Issue155Test extends AbstractMysqlTestCase
{
    private const TABLE = 'issue_155';

    public function testListColumnsReadsSetEnumAndCurrentTimestamp(): void
    {
        $this->createTable('`changed` DATETIME(6) NULL DEFAULT CURRENT_TIMESTAMP(6)');

        $columns = $this->getPhalconDb()->listColumns($_ENV['MYSQL_TEST_DB_DATABASE'], self::TABLE);

        $this->assertSame(Column::TYPE_SET, $columns['os']->getType());
        $this->assertSame(['Android', 'iOS', 'Windows'], $columns['os']->getOptions());
        $this->assertSame(['a', 'b,c', "it's"], $columns['kind']->getOptions());
        $this->assertSame('b,c', $columns['kind']->getDefault());
        $this->assertSame('CURRENT_TIMESTAMP', $columns['created']->getDefault());
        // A default with a precision needs the precision of the column, which is not read
        $this->assertFalse($columns['changed']->hasDefault());
    }

    public function testRunCreatesSameTable(): void
    {
        $this->createTable('`changed` DATETIME NULL DEFAULT CURRENT_TIMESTAMP');
        $expected = $this->describeTable();

        $migrationsDir = $this->getOutputDir(__FUNCTION__);
        ob_start();
        try {
            Migrations::generate([
                'config'        => static::getMigrationsConfig(),
                'migrationsDir' => $migrationsDir,
                'tableName'     => self::TABLE,
                'version'       => '1.0.0',
            ]);

            $this->getPhalconDb()->dropTable(self::TABLE, $_ENV['MYSQL_TEST_DB_DATABASE']);

            Migrations::run([
                'config'         => static::getMigrationsConfig(),
                'migrationsDir'  => $migrationsDir,
                'migrationsInDb' => true,
            ]);
        } finally {
            ob_end_clean();
        }

        $this->assertSame($expected, $this->describeTable());
    }

    private function createTable(string $changed): void
    {
        $this->getPhalconDb()->execute(
            "CREATE TABLE `" . self::TABLE . "` (
                `id` INT NOT NULL,
                `doc` JSON NULL,
                `os` SET('Android','iOS','Windows') NOT NULL,
                `kind` ENUM('a','b,c','it''s') NOT NULL DEFAULT 'b,c',
                `created` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                {$changed},
                PRIMARY KEY (`id`)
            )"
        );
    }

    /**
     * The type, the default and the null flag of each column, from the server
     */
    private function describeTable(): array
    {
        return $this->getPhalconDb()->fetchAll(
            "SELECT column_name AS name, column_type AS type, column_default AS def, is_nullable AS nullable
             FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = '" . self::TABLE . "'
             ORDER BY ordinal_position"
        );
    }
}
