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

use Phalcon\Db\Column;
use Phalcon\Db\Enum;
use Phalcon\Helper\Arr;
use Phalcon\Migrations\Migrations;
use Phalcon\Migrations\Script\ScriptException;
use Phalcon\Mvc\Model\Exception;

use function Phalcon\Migrations\Tests\remove_dir;
use function Phalcon\Migrations\Tests\root_path;

final class ColumnTypesTest extends PostgreSQLIntegrationTestCase
{
    public function columnsDataProvider(): array
    {
        return [
            [
                'pg_column_jsonb',
                [
                    'type' => Column::TYPE_JSONB,
                ],
                ['{}', '{"type": "json"}', '{"random": 123, "is_true": false}'],
            ],
            [
                'pg_column_json',
                [
                    'type' => Column::TYPE_JSON,
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
     * @throws ScriptException
     * @throws Exception
     */
    public function testColumnDefinition(string $columnName, array $definition, array $values): void
    {
        $tableName = $columnName . '_test';
        $migrationsDir = root_path('tests/var/output/' . __FUNCTION__ . $columnName);

        $this->db->createTable($tableName, $this->defaultSchema, [
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
        $this->db->dropTable($tableName);
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

        remove_dir($migrationsDir);

        /** @var Column $column */
        $column = $this->db->describeColumns($tableName, $this->defaultSchema)[0];
        $rows = $this->db->fetchAll("SELECT $columnName FROM $tableName", Enum::FETCH_ASSOC);
        $rows = Arr::flatten($rows);

        $this->assertSame($definition['type'], $column->getType());
        $this->assertEquals($values, $rows);
    }
}
