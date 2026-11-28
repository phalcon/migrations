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
 * @see https://github.com/phalcon/migrations/issues/252
 */
final class Issue252Test extends AbstractMysqlTestCase
{
    public function testGenerateDryWithDataWritesNoFiles(): void
    {
        $db = $this->getPhalconDb();
        $db->execute('CREATE TABLE `issue_252_items` (`id` INT NOT NULL, PRIMARY KEY (`id`))');
        $db->execute('INSERT INTO `issue_252_items` (`id`) VALUES (1)');

        $migrationsDir = $this->getOutputDir(__FUNCTION__);
        ob_start();
        try {
            Migrations::generate([
                'config'        => static::getMigrationsConfig(),
                'exportData'    => 'always',
                'migrationsDir' => $migrationsDir,
                'tableName'     => '@',
                'verbose'       => true,
                'version'       => '1.0.0',
            ]);
        } finally {
            ob_end_clean();
        }

        $this->assertDirectoryDoesNotExist($migrationsDir . '/1.0.0');
    }
}
