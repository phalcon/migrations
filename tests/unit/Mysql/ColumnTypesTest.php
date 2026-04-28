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
use Phalcon\Migrations\Migrations;
use Phalcon\Migrations\Tests\AbstractMysqlTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class ColumnTypesTest extends AbstractMysqlTestCase
{
    public static function columnsDataProvider(): array
    {
        return [
            [
                'column_uint',
                [
                    'type'     => Column::TYPE_INTEGER,
                    'size'     => 10,
                    'unsigned' => true,
                    'notNull'  => true,
                    'first'    => true,
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
                    'type'    => Column::TYPE_INTEGER,
                    'size'    => 11,
                    'first'   => true,
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
                    'type'     => Column::TYPE_MEDIUMINTEGER,
                    'size'     => 123,
                    'unsigned' => true,
                ],
                [16777215, 0],
            ],
            [
                'column_smallint',
                [
                    'type'    => Column::TYPE_SMALLINTEGER,
                    'size'    => 11,
                    'first'   => true,
                    'primary' => true,
                ],
                [1, 2, 3, 4],
            ],
            [
                'column_tinyint_big_display_size',
                [
                    'type'    => Column::TYPE_TINYINTEGER,
                    'size'    => 255,
                    'first'   => true,
                    'primary' => true,
                ],
                [-128, 0, 127],
            ],
            [
                'column_tiny_uint',
                [
                    'type'     => Column::TYPE_TINYINTEGER,
                    'unsigned' => true,
                ],
                [255, 0],
            ],
            [
                'column_bigint_primary',
                [
                    'type'    => Column::TYPE_BIGINTEGER,
                    'size'    => 7,
                    'first'   => true,
                    'primary' => true,
                ],
                [PHP_INT_MIN, PHP_INT_MIN + 1, 0, PHP_INT_MAX - 1, PHP_INT_MAX],
            ],
            [
                'column_int_pri_inc',
                [
                    'type'          => Column::TYPE_INTEGER,
                    'size'          => 11,
                    'first'         => true,
                    'primary'       => true,
                    'autoIncrement' => true,
                ],
                [1, 2, 3, 4],
            ],
            [
                'column_time',
                [
                    'type'    => Column::TYPE_TIME,
                    'notNull' => false,
                ],
                ['00:00:00', '23:59:55', '12:00:12'],
            ],
            [
                'column_json',
                [
                    'type'    => Column::TYPE_JSON,
                    'notNull' => true,
                ],
                ['{}', '{"type": "json"}', '{"random": 123, "is_true": false}'],
            ],
            [
                'column_enum_not_null',
                [
                    'type'    => Column::TYPE_ENUM,
                    'size'    => "'y','n','d', ''",
                    'notNull' => true,
                ],
                ['y', 'n', 'd', ''],
            ],
            [
                'column_decimal',
                [
                    'type'    => Column::TYPE_DECIMAL,
                    'size'    => 10,
                    'scale'   => 2,
                    'notNull' => true,
                ],
                [0, 1, 2.3, 4.56, 12345678.12],
            ],
        ];
    }

    /**
     * @throws Exception
     * @throws \Exception
     */
    #[DataProvider('columnsDataProvider')]
    public function testColumnDefinition(string $columnName, array $definition, array $values): void
    {
        $tableName     = $columnName . '_test';
        $migrationsDir = $this->getOutputDir(__FUNCTION__);

        $this->getPhalconDb()->createTable($tableName, $_ENV['MYSQL_TEST_DB_DATABASE'], [
            'columns' => [
                new Column($columnName, $definition),
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

        try {
            foreach ($values as $value) {
                $this->getPhalconDb()->insert($tableName, [$value], [$columnName]);
            }
        } finally {
            Migrations::resetStorage();
            $this->removeDir($migrationsDir);
        }

        /** @var Column $column */
        $column = $this->getPhalconDb()->describeColumns($tableName)[0];
        $rows   = $this->grabColumnFromDatabase($tableName, $columnName);

        $this->assertSame($definition['unsigned'] ?? false, $column->isUnsigned());
        $this->assertSame($definition['type'], $column->getType());
        $this->assertSame($definition['notNull'] ?? true, $column->isNotNull());
        $this->assertEquals($values, $rows);
    }
}
