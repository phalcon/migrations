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

namespace Phalcon\Migrations\Tests\PostgreSQL;

use Codeception\Example;
use Phalcon\Db\Column;
use Phalcon\Migrations\Migrations;
use Phalcon\Migrations\Script\ScriptException;
use Phalcon\Mvc\Model\Exception;
use PostgresqlTester;

final class ColumnTypesCest
{
    /**
     * @dataProvider columnsDataProvider
     *
     * @param PostgresqlTester $I
     * @param Example $example
     * @throws Exception
     * @throws ScriptException
     * @throws \Exception
     */
    public function columnDefinition(PostgresqlTester $I, Example $example): void
    {
        list($columnName, $definition, $values) = $example;

        $tableName = $columnName . '_test';
        $migrationsDir = codecept_output_dir(__FUNCTION__ . $columnName);

        $created = $I->getPhalconDb()->createTable($tableName, $I->getDefaultSchema(), [
            'columns' => [
                new Column($columnName, $definition),
            ],
        ]);

        /**
         * Generate | Drop | Run
         */
        ob_start();
        Migrations::generate([
            'migrationsDir' => $migrationsDir,
            'config' => $I->getMigrationsConfig(),
            'tableName' => $tableName,
        ]);
        $I->getPhalconDb()->dropTable($tableName);
        Migrations::run([
            'migrationsDir' => $migrationsDir,
            'config' => $I->getMigrationsConfig(),
            'migrationsInDb' => true,
        ]);
        ob_clean();

        /**
         * Insert values
         */
        foreach ($values as $value) {
            $I->getPhalconDb()->insert($tableName, [$value], [$columnName]);
        }

        $I->removeDir($migrationsDir);

        /** @var Column $column */
        $column = $I->getPhalconDb()->describeColumns($tableName, $I->getDefaultSchema())[0];
        $rows = $I->grabColumnFromDatabase($I->getDefaultSchema() . '.' . $tableName, $columnName);

        $I->assertSame($definition['type'], $column->getType());
        $I->assertEquals($values, $rows);
    }

    protected function columnsDataProvider(): array
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
}
