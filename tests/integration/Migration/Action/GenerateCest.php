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

use Codeception\Example;
use IntegrationTester;
use Phalcon\Db\Column;
use Phalcon\Migrations\Exception\Db\UnknownColumnTypeException;
use Phalcon\Migrations\Migration\Action\Generate;

final class GenerateCest
{
    /**
     * @dataProvider columnsDefinitionDataProvider
     * @param IntegrationTester $I
     * @param Example $example
     * @throws UnknownColumnTypeException
     */
    public function getColumns(IntegrationTester $I, Example $example): void
    {
        $I->wantToTest('Migration\Action\Generate - assert count definition');

        $class = new Generate('mysql', $example['definition']);

        $columns = [];
        foreach ($class->getColumns() as $columnName => $columnDefinition) {
            $columns[] = $columnDefinition;
        }

        $I->assertEquals($example['count'], count($columns));
    }

    /**
     * @param IntegrationTester $I
     * @throws UnknownColumnTypeException
     */
    public function getColumnsUnsupportedTypesException(IntegrationTester $I): void
    {
        $I->expectThrowable(UnknownColumnTypeException::class, function() {
            $class = new Generate('mysql', [
                new Column('unknown', [
                    'type' => 9000,
                ]),
            ]);

            foreach ($class->getColumns() as $column) {
                // Wait exception
            }
        });
    }

    /**
     * @return array
     */
    protected function columnsDefinitionDataProvider(): array
    {
        return [
            [
                'definition' => [
                    new Column('test1', [
                        'type' => Column::TYPE_INTEGER,
                        'size' => 10,
                        'primary' => true,
                    ]),
                ],
                'count' => 1,
            ],
            [
                'definition' => [
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
                'count' => 2,
            ]
        ];
    }
}
