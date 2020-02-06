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

namespace Phalcon\Migrations\Tests\Integration\MySQL;

use Phalcon\Db\Column;
use Phalcon\Migrations\Migrations;
use Phalcon\Migrations\Script\ScriptException;
use Phalcon\Mvc\Model\Exception;

use function Phalcon\Migrations\Tests\root_path;

final class TimestampedVersionTest extends MySQLIntegrationTestCase
{
    /**
     * @throws ScriptException
     * @throws Exception
     * @throws \Exception
     */
    public function testSingleVersion(): void
    {
        $options = $this->getOptions(root_path('tests/var/output/timestamp-single-version'));

        $tableName = 'timestamp-versions-1';
        $this->db->createTable($tableName, '', [
            'columns' => [
                new Column('name', [
                    'type' => Column::TYPE_VARCHAR,
                    'size' => 25,
                ]),
            ],
        ]);

        Migrations::generate($options);
        $this->db->dropTable($tableName);
        Migrations::run($options);

        $this->assertTrue($this->db->tableExists($tableName));
    }

    /**
     * @throws Exception
     * @throws ScriptException
     * @throws \Exception
     */
    public function testSeveralVersions(): void
    {
        $options = $this->getOptions(root_path('tests/var/output/timestamp-several-versions'));

        /**
         * Generate first version
         */
        $tableName1 = 'timestamp-versions-2';
        $this->db->createTable($tableName1, '', [
            'columns' => [
                new Column('name', [
                    'type' => Column::TYPE_VARCHAR,
                    'size' => 25,
                ]),
            ],
        ]);

        Migrations::generate($options);

        /**
         * Generate second version
         */
        $tableName2 = 'timestamp-versions-3';
        $this->db->createTable($tableName2, '', [
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
        $this->db->dropTable($tableName1);
        $this->db->dropTable($tableName2);
        Migrations::run($options);

        $this->assertTrue($this->db->tableExists($tableName1));
        $this->assertTrue($this->db->tableExists($tableName2));
    }

    private function getOptions(string $path): array
    {
        return [
            'migrationsDir' => $path,
            'config' => self::$generateConfig,
            'tableName' => '@',
            'descr' => '1',
            'tsBased' => true,
            'migrationsInDb' => true,
        ];
    }
}
