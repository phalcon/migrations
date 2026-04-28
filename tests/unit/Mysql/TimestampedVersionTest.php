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

use Phalcon\Migrations\Db\Column;
use Phalcon\Migrations\Migrations;
use Phalcon\Migrations\Script\ScriptException;
use Phalcon\Migrations\Tests\AbstractMysqlTestCase;

final class TimestampedVersionTest extends AbstractMysqlTestCase
{
    /**
     * @throws ScriptException
     * @throws \Exception
     */
    public function testSingleVersion(): void
    {
        $options   = $this->getOptions($this->getOutputDir('timestamp-single-version'));
        $tableName = 'timestamp-versions-1';

        $this->getPhalconDb()->createTable($tableName, '', [
            'columns' => [
                new Column('name', [
                    'type' => Column::TYPE_VARCHAR,
                    'size' => 25,
                ]),
            ],
        ]);

        ob_start();
        Migrations::generate($options);
        $this->getPhalconDb()->dropTable($tableName);
        Migrations::run($options);
        ob_end_clean();

        $this->assertTrue($this->getPhalconDb()->tableExists($tableName));
    }

    /**
     * @throws ScriptException
     * @throws \Exception
     */
    public function testSeveralVersions(): void
    {
        $options = $this->getOptions($this->getOutputDir('timestamp-several-versions'));

        $tableName1 = 'timestamp-versions-2';
        $this->getPhalconDb()->createTable($tableName1, '', [
            'columns' => [
                new Column('name', [
                    'type' => Column::TYPE_VARCHAR,
                    'size' => 25,
                ]),
            ],
        ]);

        ob_start();
        Migrations::generate($options);

        $tableName2 = 'timestamp-versions-3';
        $this->getPhalconDb()->createTable($tableName2, '', [
            'columns' => [
                new Column('name', [
                    'type' => Column::TYPE_VARCHAR,
                    'size' => 25,
                ]),
            ],
        ]);

        Migrations::generate($options);

        $this->getPhalconDb()->dropTable($tableName1);
        $this->getPhalconDb()->dropTable($tableName2);
        Migrations::run($options);
        ob_end_clean();

        $this->assertTrue($this->getPhalconDb()->tableExists($tableName1));
        $this->assertTrue($this->getPhalconDb()->tableExists($tableName2));
    }

    private function getOptions(string $path): array
    {
        return [
            'migrationsDir'  => $path,
            'config'         => static::getMigrationsConfig(),
            'tableName'      => '@',
            'descr'          => '1',
            'tsBased'        => true,
            'migrationsInDb' => true,
        ];
    }
}
