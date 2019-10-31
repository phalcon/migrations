<?php
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
