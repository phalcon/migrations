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

use Phalcon\Migrations\Tests\AbstractTestCase;
use Phalcon\Migrations\Version\IncrementalItem;
use Phalcon\Migrations\Version\ItemInterface;

final class IncrementalItemTest extends AbstractTestCase
{
    public function testImplementsItemInterface(): void
    {
        $item = new IncrementalItem('1.0.0');

        $this->assertInstanceOf(ItemInterface::class, $item);
    }

    public function testConstructorPadsShortVersionWithExtraPart(): void
    {
        $item = new IncrementalItem('1.0');

        $this->assertSame('1.0.0.0', $item->getVersion());
    }

    public function testConstructorDoesNotTruncateLongVersion(): void
    {
        $item = new IncrementalItem('1.0.0.1');

        $this->assertSame('1.0.0.1', $item->getVersion());
    }

    public function testConstructorTrimsWhitespace(): void
    {
        $item = new IncrementalItem('  1.0.0  ');

        $this->assertSame('1.0.0', $item->getVersion());
    }

    public function testGetVersionReturnsVersionString(): void
    {
        $item = new IncrementalItem('2.3.4');

        $this->assertSame('2.3.4', $item->getVersion());
    }

    public function testToStringReturnsVersionString(): void
    {
        $item = new IncrementalItem('1.2.3');

        $this->assertSame('1.2.3', (string) $item);
    }

    public function testGetStampCalculatesCorrectly(): void
    {
        $item = new IncrementalItem('1.2.3');

        $this->assertSame(123, $item->getStamp());
    }

    public function testGetPathReturnsEmptyByDefault(): void
    {
        $item = new IncrementalItem('1.0.0');

        $this->assertSame('', $item->getPath());
    }

    public function testSetPathAndGetPath(): void
    {
        $item = new IncrementalItem('1.0.0');
        $item->setPath('/migrations/1.0.0');

        $this->assertSame('/migrations/1.0.0', $item->getPath());
    }

    public function testAddMinorIncrementsLastPart(): void
    {
        $item = new IncrementalItem('1.0.0');
        $item->addMinor(1);

        $this->assertSame('1.0.1', $item->getVersion());
    }

    public function testAddMinorWithNonNumericPart(): void
    {
        $item = new IncrementalItem('1.0.a');
        $item->addMinor(1);

        $this->assertSame('1.0.98', $item->getVersion());
    }

    public function testSortAscReturnsVersionsInAscendingOrder(): void
    {
        $v1 = new IncrementalItem('2.0.0');
        $v2 = new IncrementalItem('1.0.0');
        $v3 = new IncrementalItem('3.0.0');

        $sorted = IncrementalItem::sortAsc([$v1, $v2, $v3]);

        $this->assertSame('1.0.0', $sorted[0]->getVersion());
        $this->assertSame('2.0.0', $sorted[1]->getVersion());
        $this->assertSame('3.0.0', $sorted[2]->getVersion());
    }

    public function testSortDescReturnsVersionsInDescendingOrder(): void
    {
        $v1 = new IncrementalItem('1.0.0');
        $v2 = new IncrementalItem('3.0.0');
        $v3 = new IncrementalItem('2.0.0');

        $sorted = IncrementalItem::sortDesc([$v1, $v2, $v3]);

        $this->assertSame('3.0.0', $sorted[0]->getVersion());
        $this->assertSame('2.0.0', $sorted[1]->getVersion());
        $this->assertSame('1.0.0', $sorted[2]->getVersion());
    }

    public function testMaximumReturnsNullForEmptyArray(): void
    {
        $this->assertNull(IncrementalItem::maximum([]));
    }

    public function testMaximumReturnsHighestVersion(): void
    {
        $v1 = new IncrementalItem('1.0.0');
        $v2 = new IncrementalItem('3.0.0');
        $v3 = new IncrementalItem('2.0.0');

        $max = IncrementalItem::maximum([$v1, $v2, $v3]);

        $this->assertNotNull($max);
        $this->assertSame('3.0.0', $max->getVersion());
    }

    public function testBetweenReturnsEmptyForSameVersion(): void
    {
        $v = new IncrementalItem('1.0.0');

        $result = IncrementalItem::between($v, new IncrementalItem('1.0.0'), [$v]);

        $this->assertSame([], $result);
    }

    public function testBetweenWithAscendingRange(): void
    {
        $v1 = new IncrementalItem('1.0.0');
        $v2 = new IncrementalItem('1.5.0');
        $v3 = new IncrementalItem('2.0.0');
        $v4 = new IncrementalItem('3.0.0');

        $result = IncrementalItem::between(
            new IncrementalItem('1.0.0'),
            new IncrementalItem('2.0.0'),
            [$v1, $v2, $v3, $v4]
        );

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

        $result = IncrementalItem::between(
            new IncrementalItem('2.0.0'),
            new IncrementalItem('1.0.0'),
            [$v1, $v2, $v3, $v4]
        );

        $this->assertCount(3, $result);
        $this->assertSame('2.0.0', $result[0]->getVersion());
        $this->assertSame('1.5.0', $result[1]->getVersion());
        $this->assertSame('1.0.0', $result[2]->getVersion());
    }

    public function testBetweenAcceptsStringBounds(): void
    {
        $v1 = new IncrementalItem('1.0.0');
        $v2 = new IncrementalItem('2.0.0');

        $result = IncrementalItem::between('1.0.0', '2.0.0', [$v1, $v2]);

        $this->assertCount(2, $result);
    }
}
