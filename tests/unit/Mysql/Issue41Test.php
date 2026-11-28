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

use function file_get_contents;
use function ob_end_clean;
use function ob_start;

/**
 * @see https://github.com/phalcon/migrations/issues/41
 * @see https://github.com/phalcon/migrations/issues/87
 */
final class Issue41Test extends AbstractMysqlTestCase
{
    private const TABLE = 'issue_41_test';

    public function testGenerateAndRunANamespacedMigration(): void
    {
        $db = $this->getPhalconDb();
        $db->execute('CREATE TABLE `' . self::TABLE . '` (`id` INT NOT NULL, `name` VARCHAR(20) NOT NULL)');

        $migrationsDir = $this->getOutputDir(__FUNCTION__);
        ob_start();
        try {
            Migrations::generate([
                'migrationsDir' => $migrationsDir,
                'config'        => static::getMigrationsConfig(),
                'tableName'     => self::TABLE,
                'namespace'     => 'App\\Migrations\\Issue41',
            ]);

            $content = file_get_contents($migrationsDir . '/1.0.0/' . self::TABLE . '.php');

            $db->dropTable(self::TABLE);
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

        $this->assertStringContainsString('declare(strict_types=1);', $content);
        $this->assertStringContainsString('namespace App\\Migrations\\Issue41;', $content);
        $this->assertTrue($db->tableExists(self::TABLE));
        $this->assertCount(2, $this->describeColumns(self::TABLE));
    }
}
