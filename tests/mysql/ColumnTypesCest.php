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
use MysqlTester;
use PDO;
use Phalcon\Config\Config;
use Phalcon\Db\Adapter\Pdo\AbstractPdo;
use Phalcon\Db\Column;
use Phalcon\Db\Exception;
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
                'column_uint',
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
                'column_bigint',
                [
                    'type' => Column::TYPE_BIGINTEGER,
                ],
                [PHP_INT_MIN, PHP_INT_MIN + 1, 0, PHP_INT_MAX - 1, PHP_INT_MAX],
            ],
            [
                'column_int_primary',
                [
                    'type' => Column::TYPE_INTEGER,
                    'size' => 11,
                    'first' => true,
                    'primary' => true,
                ],
                [-2147483648, 0, 2147483647],
            ],
            [
                'column_mediumint_size',
                [
                    'type' => Column::TYPE_MEDIUMINTEGER,
                    'size' => 1,
                ],
                [8388607, 0, -8388608],
            ],
            [
                'column_mediumint',
                [
                    'type' => Column::TYPE_MEDIUMINTEGER,
                ],
                [8388607, 0, -8388608],
            ],
            [
                'column_mediumint_small_display_size',
                [
                    'type' => Column::TYPE_MEDIUMINTEGER,
                    'size' => 1,
                ],
                [8388607, 0, -8388608],
            ],
            [
                'column_medium_uint',
                [
                    'type' => Column::TYPE_MEDIUMINTEGER,
                    'size' => 123,
                    'unsigned' => true,
                ],
                [16777215, 0],
            ],
            [
                'column_smallint',
                [
                    'type' => Column::TYPE_SMALLINTEGER,
                    'size' => 11,
                    'first' => true,
                    'primary' => true,
                ],
                [1, 2, 3, 4],
            ],
            [
                'column_tinyint_big_display_size',
                [
                    'type' => Column::TYPE_TINYINTEGER,
                    'size' => 255,
                    'first' => true,
                    'primary' => true,
                ],
                [-128, 0, 127],
            ],
            [
                'column_tiny_uint',
                [
                    'type' => Column::TYPE_TINYINTEGER,
                    'unsigned' => true,
                ],
                [255, 0],
            ],
            [
                'column_bigint_primary',
                [
                    'type' => Column::TYPE_BIGINTEGER,
                    'size' => 7,
                    'first' => true,
                    'primary' => true,
                ],
                [PHP_INT_MIN, PHP_INT_MIN + 1, 0, PHP_INT_MAX - 1, PHP_INT_MAX],
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
     *
     * @throws Exception
     * @throws \Exception
     */
    public function columnDefinition(MysqlTester $I, Example $example): void
    {
        list($columnName, $definition, $values) = $example;

        $tableName = $example[0] . '_test';
        $migrationsDir = codecept_output_dir('tests/var/output/' . __FUNCTION__);

        $I->getPhalconDb()
            ->createTable($tableName, $_ENV['MYSQL_TEST_DB_DATABASE'], [
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

        try {
            /**
             * Insert values
             */
            foreach ($values as $value) {
                $I->getPhalconDb()->insert($tableName, [$value], [$columnName]);
            }
        } finally {
            Migrations::resetStorage();
            $I->removeDir($migrationsDir);
        }

        /** @var Column $column */
        $column = $I->getPhalconDb()->describeColumns($tableName)[0];
        $rows = $I->grabColumnFromDatabase($tableName, $columnName);

        $I->assertSame($definition['unsigned'] ?? false, $column->isUnsigned());
        $I->assertSame($definition['type'], $column->getType());
        $I->assertSame($definition['notNull'] ?? true, $column->isNotNull());
        $I->assertEquals($values, $rows);
    }
}
