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
 * @see https://github.com/phalcon/migrations/issues/60
 */
final class Issue60Test extends AbstractMysqlTestCase
{
    private const LOG_TABLE = 'issue_60_log';

    public function testGenerateSkipsLogTable(): void
    {
        $this->getPhalconDb()->execute('CREATE TABLE `issue_60_items` (`id` INT NOT NULL, PRIMARY KEY (`id`))');
        $this->getPhalconDb()->execute(
            'CREATE TABLE `' . self::LOG_TABLE . '` (`version` VARCHAR(191) NOT NULL, PRIMARY KEY (`version`))'
        );

        $migrationsDir = $this->getOutputDir(__FUNCTION__);
        ob_start();
        try {
            Migrations::generate([
                'config'        => static::getMigrationsConfig(),
                'log-table'     => self::LOG_TABLE,
                'migrationsDir' => $migrationsDir,
                'tableName'     => '@',
                'version'       => '1.0.0',
            ]);
        } finally {
            ob_end_clean();
        }

        $this->assertFileExists($migrationsDir . '/1.0.0/issue_60_items.php');
        $this->assertFileDoesNotExist($migrationsDir . '/1.0.0/' . self::LOG_TABLE . '.php');
    }

    public function testRunUsesLogTable(): void
    {
        $this->getPhalconDb()->execute('CREATE TABLE `issue_60_items` (`id` INT NOT NULL, PRIMARY KEY (`id`))');

        $migrationsDir = $this->getOutputDir(__FUNCTION__);
        ob_start();
        try {
            Migrations::generate([
                'config'        => static::getMigrationsConfig(),
                'migrationsDir' => $migrationsDir,
                'tableName'     => '@',
                'version'       => '1.0.0',
            ]);
            Migrations::run([
                'config'         => static::getMigrationsConfig(),
                'log-table'      => self::LOG_TABLE,
                'migrationsDir'  => $migrationsDir,
                'migrationsInDb' => true,
            ]);
        } finally {
            ob_end_clean();
        }

        $tables = $this->getPhalconDb()->listTables($_ENV['MYSQL_TEST_DB_DATABASE']);
        $this->assertContains(self::LOG_TABLE, $tables);
        $this->assertNotContains(Migrations::MIGRATION_LOG_TABLE, $tables);
        $this->assertSame(['1.0.0'], $this->grabColumnFromDatabase(self::LOG_TABLE, 'version'));
    }
}
