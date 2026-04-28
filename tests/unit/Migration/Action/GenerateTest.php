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

use Nette\PhpGenerator\PhpFile;
use Phalcon\Migrations\Db\Column;
use Phalcon\Migrations\Db\Index;
use Phalcon\Migrations\Db\Reference;
use Phalcon\Migrations\Exception\RuntimeException;
use Phalcon\Migrations\Generator\Snippet;
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

    public function testCheckEntityExistsThrowsWhenEntityNotCreated(): void
    {
        $class = new Generate('mysql');

        $this->expectException(RuntimeException::class);

        $class->getEntity();
    }

    public function testCreateEntityReturnsPhpFile(): void
    {
        $class = new Generate('mysql');
        $class->createEntity('TestMigration');

        $entity = $class->getEntity();

        $this->assertInstanceOf(PhpFile::class, $entity);
    }

    public function testCreateEntityIsIdempotent(): void
    {
        $class = new Generate('mysql');
        $class->createEntity('TestMigration');
        $class->createEntity('TestMigration');

        $entity = $class->getEntity();

        $this->assertInstanceOf(PhpFile::class, $entity);
    }

    public function testCreateEntityWithRecreateFlagCreatesNewEntity(): void
    {
        $class = new Generate('mysql');
        $class->createEntity('TestMigration');
        $first = $class->getEntity();

        $class->createEntity('TestMigration', true);
        $second = $class->getEntity();

        $this->assertNotSame($first, $second);
    }

    public function testAddMorphGeneratesMorphMethod(): void
    {
        $columns = [
            new Column('id', ['type' => Column::TYPE_INTEGER, 'size' => 11, 'notNull' => true]),
        ];

        $class = new Generate('mysql', $columns);
        $class->createEntity('TestMigration');
        $class->addMorph(new Snippet(), 'test_table');

        $code = (string) $class->getEntity();

        $this->assertStringContainsString('morph', $code);
        $this->assertStringContainsString('test_table', $code);
    }

    public function testAddUpGeneratesUpMethod(): void
    {
        $class = new Generate('mysql');
        $class->createEntity('TestMigration');
        $class->addUp('test_table');

        $code = (string) $class->getEntity();

        $this->assertStringContainsString('function up', $code);
    }

    public function testAddUpWithAlwaysExportData(): void
    {
        $columns = [
            new Column('id', ['type' => Column::TYPE_INTEGER, 'size' => 11, 'notNull' => true]),
        ];

        $class = new Generate('mysql', $columns);
        $class->createEntity('TestMigration');
        foreach ($class->getColumns() as $name => $def) {
        }
        $class->addUp('test_table', 'always');

        $code = (string) $class->getEntity();

        $this->assertStringContainsString('batchInsert', $code);
    }

    public function testAddDownGeneratesDownMethod(): void
    {
        $class = new Generate('mysql');
        $class->createEntity('TestMigration');
        $class->addDown('test_table');

        $code = (string) $class->getEntity();

        $this->assertStringContainsString('function down', $code);
    }

    public function testAddDownWithAlwaysExportData(): void
    {
        $class = new Generate('mysql');
        $class->createEntity('TestMigration');
        $class->addDown('test_table', 'always');

        $code = (string) $class->getEntity();

        $this->assertStringContainsString('batchDelete', $code);
    }

    public function testAddAfterCreateTableWithOnCreate(): void
    {
        $columns = [
            new Column('id', ['type' => Column::TYPE_INTEGER, 'size' => 11, 'notNull' => true]),
        ];

        $class = new Generate('mysql', $columns);
        $class->createEntity('TestMigration');
        foreach ($class->getColumns() as $name => $def) {
        }
        $class->addAfterCreateTable('test_table', 'oncreate');

        $code = (string) $class->getEntity();

        $this->assertStringContainsString('afterCreateTable', $code);
    }

    public function testAddAfterCreateTableWithoutOnCreate(): void
    {
        $class = new Generate('mysql');
        $class->createEntity('TestMigration');
        $class->addAfterCreateTable('test_table', null);

        $this->assertInstanceOf(PhpFile::class, $class->getEntity());
    }

    public function testGetIndexesWithPrimaryAndRegularIndexes(): void
    {
        $indexes = [
            'PRIMARY'  => new Index('PRIMARY', ['id'], Index::TYPE_PRIMARY),
            'idx_name' => new Index('idx_name', ['name'], ''),
        ];

        $class  = new Generate('mysql', [], $indexes);
        $result = [];
        foreach ($class->getIndexes() as $name => $def) {
            $result[$name] = $def;
        }

        $this->assertArrayHasKey('PRIMARY', $result);
        $this->assertArrayHasKey('idx_name', $result);
    }

    public function testGetColumnSizeSkipsForPostgresqlNoSizeTypes(): void
    {
        $columns = [
            new Column('id', ['type' => Column::TYPE_INTEGER, 'notNull' => true]),
        ];

        $class  = new Generate('postgresql', $columns);
        $result = [];
        foreach ($class->getColumns() as $name => $def) {
            $result[$name] = $def;
        }

        $found = false;
        foreach ($result['id'] as $item) {
            if (strpos($item, "'size'") !== false) {
                $found = true;
            }
        }
        $this->assertFalse($found, 'Integer column in PostgreSQL should have no size');
    }

    public function testGetColumnSizeSkipsForEnumType(): void
    {
        $columns = [
            new Column('status', ['type' => Column::TYPE_ENUM, 'notNull' => true]),
        ];

        $class  = new Generate('mysql', $columns);
        $result = [];
        foreach ($class->getColumns() as $name => $def) {
            $result[$name] = $def;
        }

        $found = false;
        foreach ($result['status'] as $item) {
            if (strpos($item, "'size'") !== false) {
                $found = true;
            }
        }
        $this->assertFalse($found, 'ENUM column should have no size');
    }

    public function testWrapWithQuotes(): void
    {
        $class = new Generate('mysql');

        $this->assertSame("'column'", $class->wrapWithQuotes('column'));
        $this->assertSame('"column"', $class->wrapWithQuotes('column', '"'));
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
