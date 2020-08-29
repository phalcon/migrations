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

use IntegrationTester;
use Phalcon\Db\Column;
use Phalcon\Db\Reference;
use Phalcon\Migrations\Exception\Db\UnknownColumnTypeException;
use Phalcon\Migrations\Migration\Action\Generate;
use Phalcon\Migrations\Mvc\Model\Migration;

final class GenerateCest
{
    /**
     * @param IntegrationTester $I
     * @throws UnknownColumnTypeException
     */
    public function construct(IntegrationTester $I): void
    {
        $I->wantToTest('Migration\Action\Generate - __construct()');

        $adapter = 'mysql';
        $class = new Generate($adapter);

        $I->assertSame($adapter, $class->getAdapter());
        $I->assertIsObject($class->getColumns());
        $I->assertIsObject($class->getIndexes());
        $I->assertIsObject($class->getReferences());
        $I->assertIsArray($class->getOptions(false));
        $I->assertIsArray($class->getNumericColumns());
        $I->assertNull($class->getPrimaryColumnName());
    }

    /**
     * @param IntegrationTester $I
     */
    public function getReferences(IntegrationTester $I): void
    {
        $I->wantToTest('Migration\Action\Generate - getReferences()');

        $references = [
            'fk_accessToken_client_1' => new Reference(
                'fk_accessToken_client_1',
                [
                    'referencedTable' => 'client',
                    'referencedSchema' => 'public',
                    'columns' => ['clientId'],
                    'referencedColumns' => ['id'],
                    'onUpdate' => 'NO ACTION',
                    'onDelete' => 'NO ACTION',
                ]
            ),
        ];

        $class = new Generate('mysql', [], [], $references);
        $generatedReferences = [];
        foreach ($class->getReferences() as $name => $reference) {
            $generatedReferences[$name] = $reference;
        }

        $I->assertSame(count($references), count($generatedReferences));
        $I->assertNotFalse(array_search("'referencedSchema' => 'public'", current($generatedReferences)));
    }

    /**
     * @param IntegrationTester $I
     */
    public function getReferencesWithoutSchema(IntegrationTester $I): void
    {
        $I->wantToTest('Migration\Action\Generate - getReferences() without schema');

        $references1 = [
            'fk_accessToken_client_1' => new Reference(
                'fk_accessToken_client_1',
                [
                    'referencedTable' => 'client',
                    'columns' => ['clientId'],
                    'referencedColumns' => ['id'],
                    'onUpdate' => 'NO ACTION',
                    'onDelete' => 'NO ACTION',
                ]
            ),
        ];

        $references2 = [
            'fk_accessToken_client_1' => new Reference(
                'fk_accessToken_client_1',
                [
                    'referencedSchema' => 'public',
                    'referencedTable' => 'client',
                    'columns' => ['clientId'],
                    'referencedColumns' => ['id'],
                    'onUpdate' => 'NO ACTION',
                    'onDelete' => 'NO ACTION',
                ]
            ),
        ];

        /**
         * Case 1 - when 'referencedSchema' wasn't specified
         */
        $schemaFound1 = false;
        $generatedReferences = [];
        $class = new Generate('mysql', [], [], $references1);
        foreach ($class->getReferences() as $name => $reference) {
            $generatedReferences[$name] = $reference;

            foreach ($reference as $option) {
                if (strpos($option, 'referencedSchema') !== false) {
                    $schemaFound1 = true;
                    break;
                }
            }
        }

        /**
         * Case 2 - when option 'skip-ref-schema' was provided
         */
        $schemaFound2 = false;
        $generatedReferences = [];
        $class = new Generate('mysql', [], [], $references1);
        foreach ($class->getReferences(true) as $name => $reference) {
            $generatedReferences[$name] = $reference;

            foreach ($reference as $option) {
                if (strpos($option, 'referencedSchema') !== false) {
                    $schemaFound2 = true;
                    break;
                }
            }
        }

        $I->assertSame(count($references1), count($generatedReferences));
        $I->assertFalse($schemaFound1);
        $I->assertSame(count($references2), count($generatedReferences));
        $I->assertFalse($schemaFound2);
    }

    /**
     * @param IntegrationTester $I
     */
    public function getReferencesWithSchema(IntegrationTester $I): void
    {
        $I->wantToTest('Migration\Action\Generate - getReferences() with schema');

        $references = [
            'fk_accessToken_client_1' => new Reference(
                'fk_accessToken_client_1',
                [
                    'referencedTable' => 'client',
                    'columns' => ['clientId'],
                    'referencedColumns' => ['id'],
                    'onUpdate' => 'NO ACTION',
                    'onDelete' => 'NO ACTION',
                ]
            ),
        ];

        $schemaFound = false;
        $generatedReferences = [];
        $class = new Generate('mysql', [], [], $references);
        foreach ($class->getReferences() as $name => $reference) {
            $generatedReferences[$name] = $reference;

            foreach ($reference as $option) {
                if (strpos($option, 'referencedSchema') !== false) {
                    $schemaFound = true;
                    break;
                }
            }
        }

        $I->assertSame(count($references), count($generatedReferences));
        $I->assertFalse($schemaFound);
    }

    /**
     * @param IntegrationTester $I
     * @throws UnknownColumnTypeException
     */
    public function getQuoteWrappedColumns(IntegrationTester $I): void
    {
        $I->wantToTest('Migration\Action\Generate - getQuoteWrappedColumns()');

        $columns = [
            new Column('column1', [
                'type' => Column::TYPE_INTEGER,
                'size' => 10,
                'notNull' => true,
            ]),
            new Column('column2', [
                'type' => Column::TYPE_VARCHAR,
                'size' => 255,
                'notNull' => true,
            ]),
        ];

        $class = new Generate('mysql', $columns);
        $preparedColumns = [];
        foreach ($class->getColumns() as $name => $definition) {
            $preparedColumns[$name] = $definition;
        }

        $I->assertSame(count($columns), count($preparedColumns));
        $I->assertSame(count($columns), count($class->getQuoteWrappedColumns()));
        $I->assertSame("'column1'", $class->getQuoteWrappedColumns()[0]);
        $I->assertSame("'column2'", $class->getQuoteWrappedColumns()[1]);
    }

    /**
     * @param IntegrationTester $I
     */
    public function throwUnknownColumnTypeException(IntegrationTester $I): void
    {
        $I->wantToTest('Migration\Action\Generate - getColumns() throw UnknownColumnTypeException');

        $I->expectThrowable(UnknownColumnTypeException::class, function () {
            $columns = [
                new Column('unknown', [
                    'type' => 9000,
                    'size' => 10,
                    'notNull' => true,
                ]),
            ];

            $data = [];
            $class = new Generate('mysql', $columns);
            foreach ($class->getColumns() as $column) {
                // Wait error
                $data[] = $column;
            }
        });
    }

    /**
     * @param IntegrationTester $I
     * @throws UnknownColumnTypeException
     */
    public function columnHasDefault(IntegrationTester $I): void
    {
        $I->wantToTest('Migration\Action\Generate - getColumns() has default');

        $expected = "'default' => \"0\"";
        $columnsWithDefault = [
            new Column('column_default', [
                'type' => Column::TYPE_INTEGER,
                'size' => 10,
                'notNull' => true,
                'default' => 0,
            ]),
        ];

        $columnsWithDefaultAndAI = [
            new Column('column_ai', [
                'type' => Column::TYPE_INTEGER,
                'size' => 10,
                'notNull' => true,
                'autoIncrement' => true,
                'first' => true,
                'primary' => true,
                'default' => 0,
            ]),
        ];

        $class1 = new Generate(Migration::DB_ADAPTER_MYSQL, $columnsWithDefault);
        $class2 = new Generate(Migration::DB_ADAPTER_MYSQL, $columnsWithDefaultAndAI);
        $class3 = new Generate(Migration::DB_ADAPTER_POSTGRESQL, $columnsWithDefault);
        $class4 = new Generate(Migration::DB_ADAPTER_POSTGRESQL, $columnsWithDefaultAndAI);
        $class5 = new Generate(Migration::DB_ADAPTER_SQLITE, $columnsWithDefault);
        $class6 = new Generate(Migration::DB_ADAPTER_SQLITE, $columnsWithDefaultAndAI);

        $array1 = current(iterator_to_array($class1->getColumns()));
        $array2 = current(iterator_to_array($class2->getColumns()));
        $array3 = current(iterator_to_array($class3->getColumns()));
        $array4 = current(iterator_to_array($class4->getColumns()));
        $array5 = current(iterator_to_array($class5->getColumns()));
        $array6 = current(iterator_to_array($class6->getColumns()));

        $I->assertSame($expected, $array1[1]);
        $I->assertFalse(in_array($expected, $array2));
        $I->assertSame($expected, $array3[1]);
        $I->assertFalse(in_array($expected, $array4));
        $I->assertSame($expected, $array5[1]);
        $I->assertFalse(in_array($expected, $array6));
    }
}
