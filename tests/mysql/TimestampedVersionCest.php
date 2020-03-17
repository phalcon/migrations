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

namespace Phalcon\Migrations\Tests\Mysql;

use MysqlTester;
use Phalcon\Db\Column;
use Phalcon\Migrations\Migrations;
use Phalcon\Migrations\Script\ScriptException;
use Phalcon\Mvc\Model\Exception;

final class TimestampedVersionCest
{
    /**
     * @param MysqlTester $I
     * @throws Exception
     * @throws ScriptException
     * @throws \Exception
     */
    public function singleVersion(MysqlTester $I): void
    {
        $options = $this->getOptions($I, codecept_output_dir('timestamp-single-version'));

        $tableName = 'timestamp-versions-1';
        $I->getPhalconDb()->createTable($tableName, '', [
            'columns' => [
                new Column('name', [
                    'type' => Column::TYPE_VARCHAR,
                    'size' => 25,
                ]),
            ],
        ]);

        ob_start();
        Migrations::generate($options);
        $I->getPhalconDb()->dropTable($tableName);
        Migrations::run($options);
        ob_clean();

        $I->assertTrue($I->getPhalconDb()->tableExists($tableName));
    }

    /**
     * @param MysqlTester $I
     * @throws Exception
     * @throws ScriptException
     * @throws \Exception
     */
    public function testSeveralVersions(MysqlTester $I): void
    {
        $options = $this->getOptions($I, codecept_output_dir('tests/var/output/timestamp-several-versions'));

        /**
         * Generate first version
         */
        $tableName1 = 'timestamp-versions-2';
        $I->getPhalconDb()->createTable($tableName1, '', [
            'columns' => [
                new Column('name', [
                    'type' => Column::TYPE_VARCHAR,
                    'size' => 25,
                ]),
            ],
        ]);

        ob_start();
        Migrations::generate($options);

        /**
         * Generate second version
         */
        $tableName2 = 'timestamp-versions-3';
        $I->getPhalconDb()->createTable($tableName2, '', [
            'columns' => [
                new Column('name', [
                    'type' => Column::TYPE_VARCHAR,
                    'size' => 25,
                ]),
            ],
        ]);

        Migrations::generate($options);

        /**
         * Drop tables and run migrations
         */
        $I->getPhalconDb()->dropTable($tableName1);
        $I->getPhalconDb()->dropTable($tableName2);
        Migrations::run($options);
        ob_clean();

        $I->assertTrue($I->getPhalconDb()->tableExists($tableName1));
        $I->assertTrue($I->getPhalconDb()->tableExists($tableName2));
    }

    /**
     * @param MysqlTester $I
     * @param string $path
     * @return array
     */
    private function getOptions(MysqlTester $I, string $path): array
    {
        return [
            'migrationsDir' => $path,
            'config' => $I->getMigrationsConfig(),
            'tableName' => '@',
            'descr' => '1',
            'tsBased' => true,
            'migrationsInDb' => true,
        ];
    }
}
