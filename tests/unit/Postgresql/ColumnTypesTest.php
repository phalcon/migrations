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

namespace Phalcon\Migrations\Tests\Unit\Postgresql;

use Phalcon\Migrations\Db\Column;
use Phalcon\Migrations\Migrations;
use Phalcon\Migrations\Tests\AbstractPostgresqlTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class ColumnTypesTest extends AbstractPostgresqlTestCase
{
    public static function columnsDataProvider(): array
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
            ],
        ];
    }

    /**
     * @throws \Exception
     */
    #[DataProvider('columnsDataProvider')]
    public function testColumnDefinition(string $columnName, array $definition, array $values): void
    {
        $tableName     = $columnName . '_test';
        $migrationsDir = $this->getOutputDir(__FUNCTION__ . $columnName);

        $this->getPhalconDb()->createTable($tableName, $this->getDefaultSchema(), [
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
            $this->getPhalconDb()->dropTable($tableName, $this->getDefaultSchema());
            Migrations::run([
                'migrationsDir'  => $migrationsDir,
                'config'         => static::getMigrationsConfig(),
                'migrationsInDb' => true,
            ]);
        } finally {
            ob_end_clean();
        }

        foreach ($values as $value) {
            $this->insertRow($tableName, [$value], [$columnName]);
        }

        $this->removeDir($migrationsDir);

        $column = $this->describeColumns($tableName, $this->getDefaultSchema())[0];
        $rows   = $this->grabColumnFromDatabase($this->getDefaultSchema() . '.' . $tableName, $columnName);

        $this->assertSame($definition['type'], $column->getType());
        $this->assertEquals($values, $rows);
    }
}
