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

namespace Phalcon\Migrations\Tests\Unit\Postgresql;

use Exception;
use Phalcon\Db\Column;
use Phalcon\Migrations\Migrations;
use Phalcon\Migrations\Tests\AbstractPostgresqlTestCase;

final class MigrationsTest extends AbstractPostgresqlTestCase
{
    /**
     * @throws Exception
     */
    public function testPostgresPhalconMigrationsTable(): void
    {
        $tableName     = 'pg_phalcon_migrations';
        $migrationsDir = $this->getOutputDir(__FUNCTION__);

        $this->getPhalconDb()->createTable($tableName, $this->getDefaultSchema(), [
            'columns' => [
                new Column('column_name', [
                    'type'    => Column::TYPE_INTEGER,
                    'size'    => 10,
                    'notNull' => true,
                    'first'   => true,
                ]),
            ],
        ]);

        ob_start();
        try {
            Migrations::generate([
                'migrationsDir' => [$migrationsDir],
                'config'        => static::getMigrationsConfig(),
                'tableName'     => '@',
            ]);
            $this->getPhalconDb()->dropTable($tableName);
            Migrations::run([
                'migrationsDir'  => $migrationsDir,
                'config'         => static::getMigrationsConfig(),
                'migrationsInDb' => true,
            ]);
        } finally {
            ob_end_clean();
        }

        $indexes = $this->getPhalconDb()->describeIndexes(Migrations::MIGRATION_LOG_TABLE);

        $this->assertTrue(
            $this->getPhalconDb()->tableExists($tableName, $this->getDefaultSchema())
        );
        $this->assertTrue(
            $this->getPhalconDb()->tableExists(Migrations::MIGRATION_LOG_TABLE, $this->getDefaultSchema())
        );
        $this->assertSame(1, count($indexes));
    }

    /**
     * @throws Exception
     */
    public function testGenerateWithExportOnCreate(): void
    {
        $tableName     = 'on_create';
        $migrationsDir = $this->getOutputDir(__FUNCTION__);

        $this->getPhalconDb()->createTable($tableName, $this->getDefaultSchema(), [
            'columns' => [
                new Column('id', [
                    'type'          => Column::TYPE_INTEGER,
                    'size'          => 10,
                    'notNull'       => true,
                    'first'         => true,
                    'primary'       => true,
                    'autoIncrement' => true,
                ]),
            ],
        ]);

        $this->getPhalconDb()->insert($tableName, [1], ['id']);
        $this->getPhalconDb()->insert($tableName, [2], ['id']);
        $this->getPhalconDb()->insert($tableName, [3], ['id']);

        ob_start();
        try {
            Migrations::generate([
                'migrationsDir'   => $migrationsDir,
                'config'          => static::getMigrationsConfig(),
                'tableName'       => '@',
                'noAutoIncrement' => true,
                'exportData'      => 'oncreate',
            ]);
        } finally {
            ob_end_clean();
        }

        $migrationContents = file_get_contents($migrationsDir . '/1.0.0/' . $tableName . '.php');

        $this->assertSame(1, substr_count($migrationContents, 'this->batchInsert'));
        $this->assertStringContainsString(
            '3',
            file_get_contents($migrationsDir . '/1.0.0/' . $tableName . '.dat')
        );
    }
}
