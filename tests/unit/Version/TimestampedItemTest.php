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

use InvalidArgumentException;
use Phalcon\Migrations\Tests\AbstractTestCase;
use Phalcon\Migrations\Version\ItemInterface;
use Phalcon\Migrations\Version\TimestampedItem;

final class TimestampedItemTest extends AbstractTestCase
{
    public function testImplementsItemInterface(): void
    {
        $item = new TimestampedItem('1234567_1');

        $this->assertInstanceOf(ItemInterface::class, $item);
    }

    public function testConstructorWithFullVersion(): void
    {
        $item = new TimestampedItem('1234567_abc');

        $this->assertSame('1234567_abc', $item->getVersion());
    }

    public function testConstructorWithTimestampOnly(): void
    {
        $item = new TimestampedItem('1234567');

        $this->assertSame('1234567', $item->getVersion());
    }

    public function testConstructorWithSpecialCase000(): void
    {
        $item = new TimestampedItem('000');

        $this->assertSame('000', $item->getVersion());
    }

    public function testConstructorThrowsOnInvalidVersion(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Wrong version number provided');

        new TimestampedItem('123456');
    }

    public function testConstructorThrowsOnAlphaVersion(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Wrong version number provided');

        new TimestampedItem('abc');
    }

    public function testGetStampReturnsTimestampPart(): void
    {
        $item = new TimestampedItem('1234567_1');

        $this->assertSame(1234567, $item->getStamp());
    }

    public function testGetStampWithTimestampOnly(): void
    {
        $item = new TimestampedItem('1777342845124457');

        $this->assertSame(1777342845124457, $item->getStamp());
    }

    public function testGetDescriptionReturnsDescriptionPartWhenFull(): void
    {
        $item = new TimestampedItem('1234567_abc');

        $this->assertSame('abc', $item->getDescription());
    }

    public function testGetDescriptionReturnsEmptyStringWhenNotFull(): void
    {
        $item = new TimestampedItem('1234567');

        $this->assertSame('', $item->getDescription());
    }

    public function testIsFullVersionReturnsTrueWhenFull(): void
    {
        $item = new TimestampedItem('1234567_abc');

        $this->assertTrue($item->isFullVersion());
    }

    public function testIsFullVersionReturnsFalseWhenNotFull(): void
    {
        $item = new TimestampedItem('1234567');

        $this->assertFalse($item->isFullVersion());
    }

    public function testGetPathReturnsEmptyByDefault(): void
    {
        $item = new TimestampedItem('1234567_1');

        $this->assertSame('', $item->getPath());
    }

    public function testSetPathAndGetPath(): void
    {
        $item = new TimestampedItem('1234567_1');
        $item->setPath('/migrations/1234567_1');

        $this->assertSame('/migrations/1234567_1', $item->getPath());
    }

    public function testGetVersion(): void
    {
        $item = new TimestampedItem('1234567_1');

        $this->assertSame('1234567_1', $item->getVersion());
    }

    public function testToString(): void
    {
        $item = new TimestampedItem('1234567_1');

        $this->assertSame('1234567_1', (string) $item);
    }
}
