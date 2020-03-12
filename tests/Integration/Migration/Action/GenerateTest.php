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

namespace Phalcon\Migrations\Tests\Integration\Migration\Action;

use Phalcon\Db\Column;
use Phalcon\Migrations\Exception\Db\UnknownColumnTypeException;
use Phalcon\Migrations\Migration\Action\Generate;
use Phalcon\Migrations\Tests\Integration\IntegrationTestCase;

final class GenerateTest extends IntegrationTestCase
{
    public function columnsDefinitionDataProvider(): array
    {
        return [
            [
                [
                    new Column('test1', [
                        'type' => Column::TYPE_INTEGER,
                        'size' => 10,
                        'primary' => true,
                    ]),
                ],
                1,
            ],
            [
                [
                    new Column('test1', [
                        'type' => Column::TYPE_INTEGER,
                        'size' => 10,
                        'primary' => true,
                    ]),
                    new Column('test1', [
                        'type' => Column::TYPE_VARCHAR,
                        'size' => 255,
                        'notNull' => false,
                    ]),
                ],
                2,
            ]
        ];
    }

    /**
     * @dataProvider columnsDefinitionDataProvider
     * @param $definition
     * @param int $countColumns
     * @throws UnknownColumnTypeException
     */
    public function testGetColumns(array $definition, int $countColumns): void
    {
        $class = new Generate('mysql', $definition);

        $columns = [];
        foreach ($class->getColumns() as $columnName => $columnDefinition) {
            $columns[] = $columnDefinition;
        }

        $this->assertEquals($countColumns, count($columns));
    }

    public function testGetColumnsUnsupportedTypesException(): void
    {
        $this->expectException(UnknownColumnTypeException::class);

        $class = new Generate('mysql', [
            new Column('unknown', [
                'type' => 9000,
            ]),
        ]);

        foreach ($class->getColumns() as $column) {
            // Wait exception
        }
    }
}
