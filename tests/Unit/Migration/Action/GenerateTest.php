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

use Phalcon\Migrations\Migration\Action\Generate;
use PHPUnit\Framework\TestCase;

final class GenerateTest extends TestCase
{
    public function testConstruct(): void
    {
        $adapter = 'mysql';
        $class = new Generate($adapter);

        $this->assertSame($adapter, $class->getAdapter());
        $this->assertIsObject($class->getColumns());
        $this->assertIsObject($class->getIndexes());
        $this->assertIsObject($class->getReferences());
        $this->assertIsArray($class->getOptions(false));
        $this->assertIsArray($class->getNumericColumns());
        $this->assertNull($class->getPrimaryColumnName());
    }
}
