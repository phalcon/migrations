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

use Phalcon\Db\Column;
use Phalcon\Db\Exception;
use Phalcon\Db\Index;
use Phalcon\Migrations\Migrations;
use Phalcon\Migrations\Tests\AbstractMysqlTestCase;

/**
 * @see https://github.com/phalcon/migrations/issues/94
 */
final class Issue94Test extends AbstractMysqlTestCase
{
    /**
     * @throws Exception
     */
    public function testIssue94(): void
    {
        ob_start();
        Migrations::run([
            'migrationsDir'  => $this->getDataDir('issues/94'),
            'config'         => static::getMigrationsConfig(),
            'migrationsInDb' => true,
        ]);
        ob_end_clean();

        $options = $this->getPhalconDb()->tableOptions('memory_table');

        $this->assertSame('MEMORY', $options['engine']);
    }

    /**
     * @throws Exception
     */
    public function testGenerateIssue94(): void
    {
        $engine        = 'MyISAM';
        $tableName     = 'options_uppercase';
        $migrationsDir = $this->getOutputDir(__FUNCTION__);

        $this->getPhalconDb()->createTable($tableName, '', [
            'columns' => [
                new Column('id', [
                    'type'          => Column::TYPE_INTEGER,
                    'size'          => 20,
                    'notNull'       => true,
                    'autoIncrement' => true,
                ]),
            ],
            'indexes' => [
                new Index('PRIMARY', ['id'], 'PRIMARY'),
            ],
            'options' => [
                'TABLE_TYPE'      => 'BASE TABLE',
                'ENGINE'          => $engine,
                'TABLE_COLLATION' => 'utf8mb4_general_ci',
            ],
        ]);

        ob_start();
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
        ob_end_clean();

        $this->assertSame($engine, $this->getPhalconDb()->tableOptions($tableName)['engine']);
    }
}
