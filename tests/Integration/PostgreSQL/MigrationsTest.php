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

namespace Phalcon\Migrations\Tests\Integration\PostgreSQL;

use Exception;
use Phalcon\Db\Column;
use Phalcon\Migrations\Migrations;

use function Phalcon\Migrations\Tests\root_path;

final class MigrationsTest extends PostgreSQLIntegrationTestCase
{
    /**
     * @throws Exception
     */
    public function testPostgreSQLPhalconMigrationsTable(): void
    {
        $tableName = 'pg_phalcon_migrations';
        $migrationsDir = root_path('tests/var/output/' . __FUNCTION__);

        $this->db->createTable($tableName, $this->defaultSchema, [
            'columns' => [
                new Column('column_name', [
                    'type' => Column::TYPE_INTEGER,
                    'size' => 10,
                    'unsigned' => true,
                    'notNull' => true,
                    'first' => true,
                ]),
            ],
        ]);

        $options = [
            'migrationsDir' => [
                $migrationsDir,
            ],
            'config' => self::$generateConfig,
            'tableName' => '@',
        ];

        Migrations::generate($options);
        $this->db->dropTable($tableName);
        Migrations::run([
            'migrationsDir' => $migrationsDir,
            'config' => self::$generateConfig,
            'migrationsInDb' => true,
        ]);

        $indexes = $this->db->describeIndexes(Migrations::MIGRATION_LOG_TABLE);

        $this->assertTrue($this->db->tableExists($tableName, $this->defaultSchema));
        $this->assertTrue($this->db->tableExists(Migrations::MIGRATION_LOG_TABLE, $this->defaultSchema));
        $this->assertSame(1, count($indexes));
    }
}
