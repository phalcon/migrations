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
use Phalcon\Migrations\Mvc\Model\Migration as ModelMigration;
use Phalcon\Migrations\Tests\AbstractMysqlTestCase;

use function array_map;
use function ob_end_clean;
use function ob_start;
use function sort;

/**
 * The option "truncate" empties a table before a migration loads its data.
 *
 * @see https://github.com/phalcon/migrations/issues/83
 */
final class Issue83Test extends AbstractMysqlTestCase
{
    protected function tearDown(): void
    {
        ModelMigration::setTruncate(false);

        parent::tearDown();
    }

    public function testRunWithoutTruncateKeepsRows(): void
    {
        $this->assertSame(['1', '2', '3'], $this->runMigration('issue_83_keep', false));
    }

    public function testRunWithTruncateReplacesRows(): void
    {
        $this->assertSame(['1', '2'], $this->runMigration('issue_83_truncate', true));
    }

    /**
     * Generates a migration with the rows 1 and 2, changes the rows of the
     * table to row 3 only, runs the migration and returns the ids. Each test
     * uses its own table, because the class name comes from the table name.
     */
    private function runMigration(string $table, bool $truncate): array
    {
        $db = $this->getPhalconDb();
        $db->execute('CREATE TABLE `' . $table . '` (`id` INT NOT NULL, PRIMARY KEY (`id`))');
        $db->execute('INSERT INTO `' . $table . '` (`id`) VALUES (1), (2)');

        $migrationsDir = $this->getOutputDir($table);
        ob_start();
        try {
            Migrations::generate([
                'config'        => static::getMigrationsConfig(),
                'exportData'    => 'always',
                'migrationsDir' => $migrationsDir,
                'tableName'     => $table,
                'version'       => '1.0.0',
            ]);

            $db->execute('DELETE FROM `' . $table . '`');
            $db->execute('INSERT INTO `' . $table . '` (`id`) VALUES (3)');

            Migrations::run([
                'config'         => static::getMigrationsConfig(),
                'migrationsDir'  => $migrationsDir,
                'migrationsInDb' => true,
                'truncate'       => $truncate,
            ]);
        } finally {
            ob_end_clean();
        }

        $ids = array_map('strval', $this->grabColumnFromDatabase($table, 'id'));
        sort($ids);

        return $ids;
    }
}
