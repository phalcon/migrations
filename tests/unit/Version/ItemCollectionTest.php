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

use LogicException;
use Phalcon\Migrations\Tests\AbstractTestCase;
use Phalcon\Migrations\Version\IncrementalItem;
use Phalcon\Migrations\Version\ItemCollection;
use Phalcon\Migrations\Version\TimestampedItem;

final class ItemCollectionTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        ItemCollection::setType(ItemCollection::TYPE_INCREMENTAL);
    }

    protected function tearDown(): void
    {
        ItemCollection::setType(ItemCollection::TYPE_INCREMENTAL);
        parent::tearDown();
    }

    public function testDefaultTypeIsIncremental(): void
    {
        $this->assertSame(ItemCollection::TYPE_INCREMENTAL, ItemCollection::$type);
    }

    public function testSetTypeToTimestamped(): void
    {
        ItemCollection::setType(ItemCollection::TYPE_TIMESTAMPED);

        $this->assertSame(ItemCollection::TYPE_TIMESTAMPED, ItemCollection::$type);
    }

    public function testCreateItemIncrementalWithDefaultVersion(): void
    {
        $item = ItemCollection::createItem();

        $this->assertInstanceOf(IncrementalItem::class, $item);
        $this->assertSame('0.0.0', $item->getVersion());
    }

    public function testCreateItemIncrementalWithVersion(): void
    {
        $item = ItemCollection::createItem('1.2.3');

        $this->assertInstanceOf(IncrementalItem::class, $item);
        $this->assertSame('1.2.3', $item->getVersion());
    }

    public function testCreateItemTimestampedWithDefaultVersion(): void
    {
        ItemCollection::setType(ItemCollection::TYPE_TIMESTAMPED);

        $item = ItemCollection::createItem();

        $this->assertInstanceOf(TimestampedItem::class, $item);
        $this->assertSame('0000000_0', $item->getVersion());
    }

    public function testCreateItemTimestampedWithVersion(): void
    {
        ItemCollection::setType(ItemCollection::TYPE_TIMESTAMPED);

        $item = ItemCollection::createItem('1234567_1');

        $this->assertInstanceOf(TimestampedItem::class, $item);
        $this->assertSame('1234567_1', $item->getVersion());
    }

    public function testCreateItemThrowsForUnknownType(): void
    {
        ItemCollection::$type = 0;

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('Could not create an item of unknown type.');

        ItemCollection::createItem();
    }

    public function testIsCorrectVersionIncrementalValid(): void
    {
        $this->assertTrue(ItemCollection::isCorrectVersion('1.0.0'));
        $this->assertTrue(ItemCollection::isCorrectVersion('10.20.30'));
    }

    public function testIsCorrectVersionIncrementalInvalid(): void
    {
        $this->assertFalse(ItemCollection::isCorrectVersion('abc'));
        $this->assertFalse(ItemCollection::isCorrectVersion(''));
    }

    public function testIsCorrectVersionTimestampedValid(): void
    {
        ItemCollection::setType(ItemCollection::TYPE_TIMESTAMPED);

        $this->assertTrue(ItemCollection::isCorrectVersion('1234567'));
        $this->assertTrue(ItemCollection::isCorrectVersion('1777342845124457_1'));
    }

    public function testIsCorrectVersionTimestampedInvalid(): void
    {
        ItemCollection::setType(ItemCollection::TYPE_TIMESTAMPED);

        $this->assertFalse(ItemCollection::isCorrectVersion('123456'));
        $this->assertFalse(ItemCollection::isCorrectVersion('abc'));
    }

    public function testIsCorrectVersionReturnsFalseForUnknownType(): void
    {
        ItemCollection::$type = 0;

        $this->assertFalse(ItemCollection::isCorrectVersion('1.0.0'));
    }

    public function testMaximumReturnsNullForEmptyArray(): void
    {
        $this->assertNull(ItemCollection::maximum([]));
    }

    public function testMaximumReturnsHighestVersion(): void
    {
        $v1 = new IncrementalItem('1.0.0');
        $v2 = new IncrementalItem('3.0.0');
        $v3 = new IncrementalItem('2.0.0');

        $max = ItemCollection::maximum([$v1, $v2, $v3]);

        $this->assertNotNull($max);
        $this->assertSame('3.0.0', $max->getVersion());
    }

    public function testSortAscReturnsVersionsInAscendingOrder(): void
    {
        $v1 = new IncrementalItem('2.0.0');
        $v2 = new IncrementalItem('1.0.0');
        $v3 = new IncrementalItem('3.0.0');

        $sorted = ItemCollection::sortAsc([$v1, $v2, $v3]);

        $this->assertSame('1.0.0', $sorted[0]->getVersion());
        $this->assertSame('2.0.0', $sorted[1]->getVersion());
        $this->assertSame('3.0.0', $sorted[2]->getVersion());
    }

    public function testSortDescReturnsVersionsInDescendingOrder(): void
    {
        $v1 = new IncrementalItem('1.0.0');
        $v2 = new IncrementalItem('3.0.0');
        $v3 = new IncrementalItem('2.0.0');

        $sorted = ItemCollection::sortDesc([$v1, $v2, $v3]);

        $this->assertSame('3.0.0', $sorted[0]->getVersion());
        $this->assertSame('2.0.0', $sorted[1]->getVersion());
        $this->assertSame('1.0.0', $sorted[2]->getVersion());
    }

    public function testBetweenReturnsEmptyForSameStamp(): void
    {
        $v1 = new IncrementalItem('1.0.0');
        $v2 = new IncrementalItem('1.0.0');
        $v3 = new IncrementalItem('1.0.0');

        $result = ItemCollection::between($v1, $v2, [$v3]);

        $this->assertSame([], $result);
    }

    public function testBetweenReturnsVersionsInAscendingRange(): void
    {
        $v1 = new IncrementalItem('1.0.0');
        $v2 = new IncrementalItem('1.5.0');
        $v3 = new IncrementalItem('2.0.0');
        $v4 = new IncrementalItem('3.0.0');

        $initial = new IncrementalItem('1.0.0');
        $final   = new IncrementalItem('2.0.0');

        $result = ItemCollection::between($initial, $final, [$v1, $v2, $v3, $v4]);

        $this->assertCount(3, $result);
        $this->assertSame('1.0.0', $result[0]->getVersion());
        $this->assertSame('1.5.0', $result[1]->getVersion());
        $this->assertSame('2.0.0', $result[2]->getVersion());
    }

    public function testBetweenWithDescendingBoundsSwapsOrder(): void
    {
        $v1 = new IncrementalItem('1.0.0');
        $v2 = new IncrementalItem('1.5.0');
        $v3 = new IncrementalItem('2.0.0');
        $v4 = new IncrementalItem('3.0.0');

        $initial = new IncrementalItem('2.0.0');
        $final   = new IncrementalItem('1.0.0');

        $result = ItemCollection::between($initial, $final, [$v1, $v2, $v3, $v4]);

        $this->assertCount(3, $result);
        $this->assertSame('2.0.0', $result[0]->getVersion());
        $this->assertSame('1.5.0', $result[1]->getVersion());
        $this->assertSame('1.0.0', $result[2]->getVersion());
    }
}
