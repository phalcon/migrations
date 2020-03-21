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

use IntegrationTester;
use Phalcon\Db\Column;
use Phalcon\Migrations\Exception\Db\UnknownColumnTypeException;
use Phalcon\Migrations\Migration\Action\Generate;

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
}
