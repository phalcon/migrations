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

namespace Phalcon\Migrations\Tests\Unit\Version;

use Phalcon\Migrations\Version\IncrementalItem;
use Phalcon\Migrations\Version\ItemInterface;
use PHPUnit\Framework\TestCase;

final class IncrementalItemTest extends TestCase
{
    public function testMockConstructor(): void
    {
        /** @var IncrementalItem $class */
        $class = $this->createMock(IncrementalItem::class);

        $this->assertInstanceOf(ItemInterface::class, $class);
    }
}
