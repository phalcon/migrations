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
 * @see https://github.com/phalcon/migrations/issues/245
 */
final class Issue245Test extends AbstractMysqlTestCase
{
    public function testGenerateWithDataSkipsLogTable(): void
    {
        $db = $this->getPhalconDb();
        $db->execute('CREATE TABLE `issue_245_items` (`id` INT NOT NULL, PRIMARY KEY (`id`))');
        $db->execute('INSERT INTO `issue_245_items` (`id`) VALUES (1)');

        // The log table with one row
        Migrations::addCurrentVersion(['config' => static::getMigrationsConfig(), 'migrationsInDb' => true], '0.0.1');

        $migrationsDir = $this->getOutputDir(__FUNCTION__);
        ob_start();
        try {
            Migrations::generate([
                'config'        => static::getMigrationsConfig(),
                'exportData'    => 'always',
                'migrationsDir' => $migrationsDir,
                'tableName'     => '@',
                'version'       => '1.0.0',
            ]);
        } finally {
            ob_end_clean();
        }

        $this->assertFileExists($migrationsDir . '/1.0.0/issue_245_items.dat');
        $this->assertFileDoesNotExist($migrationsDir . '/1.0.0/' . Migrations::MIGRATION_LOG_TABLE . '.dat');
        $this->assertFileDoesNotExist($migrationsDir . '/1.0.0/' . Migrations::MIGRATION_LOG_TABLE . '.php');
    }
}
