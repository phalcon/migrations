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

namespace Phalcon\Migrations\Tests\Unit\Migration\Action;

use Phalcon\Migrations\Db\Column;
use Phalcon\Migrations\Db\Reference;
use Phalcon\Migrations\Migration\Action\Generate;
use Phalcon\Migrations\Mvc\Model\Migration;
use Phalcon\Migrations\Tests\AbstractTestCase;

final class GenerateTest extends AbstractTestCase
{
    /**
     * @throws UnknownColumnTypeException
     */
    public function testConstructMysql(): void
    {
        $adapter = 'mysql';
        $class   = new Generate($adapter);

        $this->assertSame($adapter, $class->getAdapter());
        $this->assertIsObject($class->getColumns());
        $this->assertIsObject($class->getIndexes());
        $this->assertIsObject($class->getReferences());
        $this->assertIsArray($class->getOptions(false));
        $this->assertIsArray($class->getNumericColumns());
        $this->assertNull($class->getPrimaryColumnName());
    }

    /**
     * @throws UnknownColumnTypeException
     */
    public function testConstructPostgresql(): void
    {
        $adapter = 'postgresql';
        $class   = new Generate($adapter);

        $this->assertSame($adapter, $class->getAdapter());
        $this->assertIsObject($class->getColumns());
        $this->assertIsObject($class->getIndexes());
        $this->assertIsObject($class->getReferences());
        $this->assertIsArray($class->getOptions(false));
        $this->assertIsArray($class->getNumericColumns());
        $this->assertNull($class->getPrimaryColumnName());
    }

    public function testGetReferences(): void
    {
        $references = [
            'fk_accessToken_client_1' => new Reference(
                'fk_accessToken_client_1',
                [
                    'referencedTable'   => 'client',
                    'referencedSchema'  => 'public',
                    'columns'           => ['clientId'],
                    'referencedColumns' => ['id'],
                    'onUpdate'          => 'NO ACTION',
                    'onDelete'          => 'NO ACTION',
                ]
            ),
        ];

        $class               = new Generate('mysql', [], [], $references);
        $generatedReferences = [];
        foreach ($class->getReferences() as $name => $reference) {
            $generatedReferences[$name] = $reference;
        }

        $this->assertSame(count($references), count($generatedReferences));
        $this->assertNotFalse(array_search("'referencedSchema' => 'public'", current($generatedReferences)));
    }

    public function testGetReferencesWithoutSchema(): void
    {
        $references1 = [
            'fk_accessToken_client_1' => new Reference(
                'fk_accessToken_client_1',
                [
                    'referencedTable'   => 'client',
                    'columns'           => ['clientId'],
                    'referencedColumns' => ['id'],
                    'onUpdate'          => 'NO ACTION',
                    'onDelete'          => 'NO ACTION',
                ]
            ),
        ];

        $references2 = [
            'fk_accessToken_client_1' => new Reference(
                'fk_accessToken_client_1',
                [
                    'referencedSchema'  => 'public',
                    'referencedTable'   => 'client',
                    'columns'           => ['clientId'],
                    'referencedColumns' => ['id'],
                    'onUpdate'          => 'NO ACTION',
                    'onDelete'          => 'NO ACTION',
                ]
            ),
        ];

        $schemaFound1        = false;
        $generatedReferences = [];
        $class               = new Generate('mysql', [], [], $references1);
        foreach ($class->getReferences() as $name => $reference) {
            $generatedReferences[$name] = $reference;
            foreach ($reference as $option) {
                if (strpos($option, 'referencedSchema') !== false) {
                    $schemaFound1 = true;
                    break;
                }
            }
        }

        $schemaFound2        = false;
        $generatedReferences = [];
        $class               = new Generate('mysql', [], [], $references1);
        foreach ($class->getReferences(true) as $name => $reference) {
            $generatedReferences[$name] = $reference;
            foreach ($reference as $option) {
                if (strpos($option, 'referencedSchema') !== false) {
                    $schemaFound2 = true;
                    break;
                }
            }
        }

        $this->assertSame(count($references1), count($generatedReferences));
        $this->assertFalse($schemaFound1);
        $this->assertSame(count($references2), count($generatedReferences));
        $this->assertFalse($schemaFound2);
    }

    public function testGetReferencesWithSchema(): void
    {
        $references = [
            'fk_accessToken_client_1' => new Reference(
                'fk_accessToken_client_1',
                [
                    'referencedTable'   => 'client',
                    'columns'           => ['clientId'],
                    'referencedColumns' => ['id'],
                    'onUpdate'          => 'NO ACTION',
                    'onDelete'          => 'NO ACTION',
                ]
            ),
        ];

        $schemaFound         = false;
        $generatedReferences = [];
        $class               = new Generate('mysql', [], [], $references);
        foreach ($class->getReferences() as $name => $reference) {
            $generatedReferences[$name] = $reference;
            foreach ($reference as $option) {
                if (strpos($option, 'referencedSchema') !== false) {
                    $schemaFound = true;
                    break;
                }
            }
        }

        $this->assertSame(count($references), count($generatedReferences));
        $this->assertFalse($schemaFound);
    }

    /**
     * @throws UnknownColumnTypeException
     */
    public function testGetQuoteWrappedColumns(): void
    {
        $columns = [
            new Column('column1', [
                'type'    => Column::TYPE_INTEGER,
                'size'    => 10,
                'notNull' => true,
            ]),
            new Column('column2', [
                'type'    => Column::TYPE_VARCHAR,
                'size'    => 255,
                'notNull' => true,
            ]),
        ];

        $class           = new Generate('mysql', $columns);
        $preparedColumns = [];
        foreach ($class->getColumns() as $name => $definition) {
            $preparedColumns[$name] = $definition;
        }

        $this->assertSame(count($columns), count($preparedColumns));
        $this->assertSame(count($columns), count($class->getQuoteWrappedColumns()));
        $this->assertSame("'column1'", $class->getQuoteWrappedColumns()[0]);
        $this->assertSame("'column2'", $class->getQuoteWrappedColumns()[1]);
    }

    public function testThrowUnknownColumnTypeException(): void
    {
        $this->expectException(\Phalcon\Migrations\Exception\Db\UnknownColumnTypeException::class);

        $columns = [
            new Column('unknown', [
                'type'    => 9000,
                'size'    => 10,
                'notNull' => true,
            ]),
        ];

        $data  = [];
        $class = new Generate('mysql', $columns);
        foreach ($class->getColumns() as $column) {
            $data[] = $column;
        }
    }

    /**
     * @throws UnknownColumnTypeException
     */
    public function testColumnHasDefault(): void
    {
        $expected           = "'default' => \"0\"";
        $columnsWithDefault = [
            new Column('column_default', [
                'type'    => Column::TYPE_INTEGER,
                'size'    => 10,
                'notNull' => true,
                'default' => 0,
            ]),
        ];

        $columnsWithDefaultAndAI = [
            new Column('column_ai', [
                'type'          => Column::TYPE_INTEGER,
                'size'          => 10,
                'notNull'       => true,
                'autoIncrement' => true,
                'first'         => true,
                'primary'       => true,
                'default'       => 0,
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

        $this->assertSame($expected, $array1[1]);
        $this->assertFalse(in_array($expected, $array2));
        $this->assertSame($expected, $array3[1]);
        $this->assertFalse(in_array($expected, $array4));
        $this->assertSame($expected, $array5[1]);
        $this->assertFalse(in_array($expected, $array6));
    }
}
