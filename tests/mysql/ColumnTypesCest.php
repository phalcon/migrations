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

use Codeception\Example;
use Exception;
use MysqlTester;
use PDO;
use Phalcon\Config;
use Phalcon\Db\Adapter\Pdo\AbstractPdo;
use Phalcon\Db\Column;
use Phalcon\Migrations\Migrations;

/**
 * @method Config getMigrationsConfig()
 * @method AbstractPdo getPhalconDb()
 * @method PDO getDb()
 * @method removeDir(string $path)
 */
final class ColumnTypesCest
{
    protected function columnsDataProvider(): array
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
                'column_int_primary',
                [
                    'type' => Column::TYPE_INTEGER,
                    'size' => 11,
                    'first' => true,
                    'primary' => true,
                ],
                [1, 2, 3, 4],
            ],
            [
                'column_int_pri_inc',
                [
                    'type' => Column::TYPE_INTEGER,
                    'size' => 11,
                    'first' => true,
                    'primary' => true,
                    'autoIncrement' => true,
                ],
                [1, 2, 3, 4],
            ],
            [
                'column_time',
                [
                    'type' => Column::TYPE_TIME,
                    'notNull' => false,
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
            ],
            [
                'column_enum_not_null',
                [
                    'type' => Column::TYPE_ENUM,
                    'size' => "'Y','N','D', ''",
                    'notNull' => true,
                ],
                ['Y', 'N', 'D', ''],
            ],
            [
                'column_decimal',
                [
                    'type' => Column::TYPE_DECIMAL,
                    'size' => 10,
                    'scale' => 2,
                    'notNull' => true,
                ],
                [0, 1, 2.3, 4.56, 12345678.12],
            ]
        ];
    }

    /**
     * @dataProvider columnsDataProvider
     *
     * @param MysqlTester $I
     * @param Example $example
     * @throws \Phalcon\Migrations\Script\ScriptException
     * @throws \Phalcon\Mvc\Model\Exception
     * @throws Exception
     */
    public function columnDefinition(MysqlTester $I, Example $example): void
    {
        list($columnName, $definition, $values) = $example;

        $tableName = $example[0] . '_test';
        $migrationsDir = codecept_output_dir('tests/var/output/' . __FUNCTION__);

        $I->getPhalconDb()->createTable($tableName, getenv('MYSQL_TEST_DB_DATABASE'), [
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

        Migrations::resetStorage();
        $I->removeDir($migrationsDir);

        /** @var Column $column */
        $column = $I->getPhalconDb()->describeColumns($tableName)[0];
        $rows = $I->grabColumnFromDatabase($tableName, $columnName);

        $I->assertSame($definition['type'], $column->getType());
        $I->assertSame($definition['notNull'] ?? true, $column->isNotNull());
        $I->assertEquals($values, $rows);
    }
}
