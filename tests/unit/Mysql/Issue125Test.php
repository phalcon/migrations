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

use function dirname;
use function file_get_contents;
use function ob_end_clean;
use function ob_start;
use function sprintf;

/**
 * The ROW_FORMAT of a table goes into the migration, when the table sets it.
 *
 * @see https://github.com/phalcon/migrations/issues/125
 */
final class Issue125Test extends AbstractMysqlTestCase
{
    public function testGenerateLeavesOutDefaultRowFormat(): void
    {
        $file = $this->generate('issue_125_default', '');

        $this->assertStringNotContainsString('ROW_FORMAT', (string) file_get_contents($file));
    }

    public function testRunCreatesTableWithRowFormat(): void
    {
        $table = 'issue_125_compact';
        $file  = $this->generate($table, 'ROW_FORMAT=COMPACT');

        $this->assertStringContainsString("'ROW_FORMAT' => 'COMPACT'", (string) file_get_contents($file));

        $db = $this->getPhalconDb();
        $db->dropTable($table, $_ENV['MYSQL_TEST_DB_DATABASE']);
        ob_start();
        try {
            Migrations::run([
                'config'         => static::getMigrationsConfig(),
                'migrationsDir'  => dirname($file, 2),
                'migrationsInDb' => true,
            ]);
        } finally {
            ob_end_clean();
        }

        $row = $db->fetchOne(
            'SELECT row_format AS row_format FROM information_schema.tables
             WHERE table_schema = DATABASE() AND table_name = :table',
            ['table' => $table]
        );
        $this->assertSame('Compact', $row['row_format']);
    }

    /**
     * Creates the table with $options, generates its migration and returns the
     * path of the migration file. Each test uses its own table, because the
     * class name comes from the table name.
     */
    private function generate(string $table, string $options): string
    {
        $this->getPhalconDb()->execute(
            sprintf('CREATE TABLE `%s` (`id` INT NOT NULL, PRIMARY KEY (`id`)) %s', $table, $options)
        );

        $migrationsDir = $this->getOutputDir($table);
        ob_start();
        try {
            Migrations::generate([
                'config'        => static::getMigrationsConfig(),
                'migrationsDir' => $migrationsDir,
                'tableName'     => $table,
                'version'       => '1.0.0',
            ]);
        } finally {
            ob_end_clean();
        }

        return $migrationsDir . '/1.0.0/' . $table . '.php';
    }
}
