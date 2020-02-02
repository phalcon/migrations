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

use Exception;
use Phalcon\Db\Column;
use Phalcon\Db\Enum;
use Phalcon\Helper\Arr;
use Phalcon\Migrations\Migrations;

use function Phalcon\Migrations\Tests\remove_dir;
use function Phalcon\Migrations\Tests\root_path;

final class ColumnTypesTest extends MySQLIntegrationTestCase
{
    public function columnsDataProvider(): array
    {
        return [
            [
                'column_int',
                [
                    'type' => Column::TYPE_INTEGER,
                    'size' => 10,
                    'unsigned' => true,
                    'notNull' => true,
                    'first' => true,
                ],
                [0, 1, 123, 9000],
            ],
            [
                'column_time',
                [
                    'type' => Column::TYPE_TIME,
                ],
                ['00:00:00', '23:59:55', '12:00:12'],
            ],
            [
                'column_json',
                [
                    'type' => Column::TYPE_JSON,
                    'notNull' => true,
                ],
                ['{}', '{"type": "json"}', '{"random": 123, "is_true": false}'],
            ]
        ];
    }

    /**
     * @dataProvider columnsDataProvider
     *
     * @param string $columnName
     * @param array $definition
     * @param array $values
     *
     * @throws \Phalcon\Migrations\Script\ScriptException
     * @throws \Phalcon\Mvc\Model\Exception
     * @throws Exception
     */
    public function testColumnDefinition(string $columnName, array $definition, array $values): void
    {
        $tableName = $columnName . '_test';
        $migrationsDir = root_path('tests/var/output/' . __FUNCTION__);

        $this->db->createTable($tableName, getenv('MYSQL_TEST_DB_DATABASE'), [
            'columns' => [
                new Column($columnName, $definition),
            ],
        ]);

        /**
         * Generate | Drop | Run
         */
        Migrations::generate([
            'migrationsDir' => $migrationsDir,
            'config' => self::$generateConfig,
            'tableName' => $tableName,
        ]);
        $this->db->query('DROP TABLE ' . $tableName);
        Migrations::run([
            'migrationsDir' => $migrationsDir,
            'config' => self::$generateConfig,
            'migrationsInDb' => true,
        ]);

        /**
         * Insert values
         */
        foreach ($values as $value) {
            $this->db->insert($tableName, [$value], [$columnName]);
        }

        Migrations::resetStorage();
        remove_dir($migrationsDir);

        /** @var Column $column */
        $column = $this->db->describeColumns($tableName)[0];
        $rows = $this->db->fetchAll("SELECT $columnName FROM $tableName", Enum::FETCH_ASSOC);
        $rows = Arr::flatten($rows);

        $this->assertSame($definition['type'], $column->getType());
        $this->assertEquals($values, $rows);
    }
}
