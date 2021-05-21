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
use Phalcon\Migrations\Exception\Db\UnknownColumnTypeException;
use Phalcon\Migrations\Migration\Action\Generate;

final class GenerateCest
{
    /**
     * @param IntegrationTester $I
     * @throws UnknownColumnTypeException
     */
    public function constructMysql(IntegrationTester $I): void
    {
        $I->wantToTest('Migration\Action\Generate - __construct(Mysql)');

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
    public function constructPostgresql(IntegrationTester $I): void
    {
        $I->wantToTest('Migration\Action\Generate - __construct(Postgresql)');

        $adapter = 'postgresql';
        $class = new Generate($adapter);

        $I->assertSame($adapter, $class->getAdapter());
        $I->assertIsObject($class->getColumns());
        $I->assertIsObject($class->getIndexes());
        $I->assertIsObject($class->getReferences());
        $I->assertIsArray($class->getOptions(false));
        $I->assertIsArray($class->getNumericColumns());
        $I->assertNull($class->getPrimaryColumnName());
    }
}
