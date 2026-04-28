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

use Phalcon\Db\Column;
use Phalcon\Db\Index;
use Phalcon\Migrations\Db\Adapter\AdapterFactory;
use Phalcon\Migrations\Db\Connection;
use Phalcon\Migrations\Db\Index as MigrationIndex;
use Phalcon\Migrations\Migrations;
use Phalcon\Migrations\Tests\AbstractPostgresqlTestCase;

final class IssuesTest extends AbstractPostgresqlTestCase
{
    public function testIssue1(): void
    {
        $tableName     = 'table_primary_test';
        $migrationsDir = $this->getOutputDir(__FUNCTION__);

        $this->getPhalconDb()->createTable($tableName, $this->getDefaultSchema(), [
            'columns' => [
                new Column('id', [
                    'type'    => Column::TYPE_INTEGER,
                    'notNull' => true,
                    'first'   => true,
                    'primary' => true,
                ]),
            ],
        ]);

        ob_start();
        try {
            Migrations::generate([
                'migrationsDir' => $migrationsDir,
                'config'        => static::getMigrationsConfig(),
                'tableName'     => $tableName,
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

        $indexes = $this->getPhalconDb()->describeIndexes($tableName, $this->getDefaultSchema());

        $this->assertSame(1, count($indexes));
    }

    public function testIssue111Fail(): void
    {
        $tableName     = 'pg_phalcon_double';
        $migrationsDir = $this->getOutputDir(__FUNCTION__);

        try {
            $this->getPhalconDb()->createTable($tableName, $this->getDefaultSchema(), [
                'columns' => [
                    new Column('point_double_column', [
                        'type'    => Column::TYPE_DOUBLE,
                        'default' => 0,
                        'notNull' => false,
                        'comment' => 'Double typed column',
                    ]),
                ],
            ]);
        } catch (\Phalcon\Db\Exception) {
            // TYPE_DOUBLE is not supported in PostgreSQL
        }

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

        $this->assertFalse($this->getPhalconDb()->tableExists($tableName, $this->getDefaultSchema()));
        $this->assertTrue($this->getPhalconDb()->tableExists(Migrations::MIGRATION_LOG_TABLE, $this->getDefaultSchema()));
        $this->assertSame(1, count($indexes));
    }

    public function testIssue111Fixed(): void
    {
        $tableName     = 'pg_phalcon_double';
        $migrationsDir = $this->getOutputDir(__FUNCTION__);

        $this->getPhalconDb()->createTable($tableName, $this->getDefaultSchema(), [
            'columns' => [
                new Column('point_double_column', [
                    'type'    => Column::TYPE_FLOAT,
                    'default' => 0,
                    'notNull' => false,
                    'comment' => 'Double typed column',
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

        $this->assertTrue($this->getPhalconDb()->tableExists($tableName, $this->getDefaultSchema()));
        $this->assertTrue($this->getPhalconDb()->tableExists(Migrations::MIGRATION_LOG_TABLE, $this->getDefaultSchema()));
        $this->assertSame(1, count($indexes));
    }

    public function testIssue112(): void
    {
        $tableName = 'pg_phalcon_primary_index';

        $this->getPhalconDb()->createTable($tableName, $this->getDefaultSchema(), [
            'columns' => [
                new Column('id', [
                    'type'    => Column::TYPE_INTEGER,
                    'notNull' => true,
                    'first'   => true,
                ]),
            ],
            'indexes' => [
                new Index('pk_id_0', ['id'], 'PRIMARY KEY'),
            ],
        ]);

        $config  = static::getMigrationsConfig();
        $adapter = AdapterFactory::create(Connection::fromConfig($config));
        $indexes = $adapter->listIndexes($this->getDefaultSchema(), $tableName);
        $index   = array_shift($indexes);

        $this->assertSame(MigrationIndex::TYPE_PRIMARY, $index->getType());
    }
}
