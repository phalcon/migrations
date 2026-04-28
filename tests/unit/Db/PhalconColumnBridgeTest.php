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

namespace Phalcon\Migrations\Tests\Unit\Db;

use Phalcon\Db\Column as PhalconColumn;
use Phalcon\Migrations\Db\Column;
use Phalcon\Migrations\Db\PhalconColumnBridge;
use Phalcon\Migrations\Tests\AbstractTestCase;

final class PhalconColumnBridgeTest extends AbstractTestCase
{
    public function testFromPhalconWithIntegerTypeConstant(): void
    {
        $phalconCol = new PhalconColumn('id', [
            'type'    => PhalconColumn::TYPE_INTEGER,
            'size'    => 11,
            'notNull' => true,
        ]);

        $col = PhalconColumnBridge::fromPhalcon($phalconCol);

        $this->assertInstanceOf(Column::class, $col);
        $this->assertSame('id', $col->getName());
        $this->assertSame(Column::TYPE_INTEGER, $col->getType());
        $this->assertTrue($col->isNotNull());
        $this->assertSame(11, $col->getSize());
    }

    public function testFromPhalconWithStringType(): void
    {
        $stub = new class ('my_col') {
            public function __construct(private string $name)
            {
            }

            public function getName(): string
            {
                return $this->name;
            }

            public function getType(): string
            {
                return 'varchar';
            }

            public function isNotNull(): bool
            {
                return false;
            }

            public function isUnsigned(): bool
            {
                return false;
            }

            public function isAutoIncrement(): bool
            {
                return false;
            }

            public function isPrimary(): bool
            {
                return false;
            }

            public function isFirst(): bool
            {
                return false;
            }

            public function getAfterPosition(): ?string
            {
                return null;
            }

            public function getSize(): mixed
            {
                return null;
            }

            public function getScale(): mixed
            {
                return null;
            }

            public function hasDefault(): bool
            {
                return false;
            }

            public function getDefault(): mixed
            {
                return null;
            }
        };

        $col = PhalconColumnBridge::fromPhalcon($stub);

        $this->assertSame('varchar', $col->getType());
        $this->assertSame('my_col', $col->getName());
    }

    public function testFromPhalconWithUnknownIntegerTypeDefaultsToVarchar(): void
    {
        $phalconCol = new PhalconColumn('col', [
            'type' => 999,
        ]);

        $col = PhalconColumnBridge::fromPhalcon($phalconCol);

        $this->assertSame(Column::TYPE_VARCHAR, $col->getType());
    }

    public function testFromPhalconWithScaleAndDefault(): void
    {
        $phalconCol = new PhalconColumn('amount', [
            'type'    => PhalconColumn::TYPE_DECIMAL,
            'size'    => 10,
            'scale'   => 2,
            'default' => '0.00',
        ]);

        $col = PhalconColumnBridge::fromPhalcon($phalconCol);

        $this->assertSame(Column::TYPE_DECIMAL, $col->getType());
        $this->assertSame(10, $col->getSize());
        $this->assertSame(2, $col->getScale());
        $this->assertTrue($col->hasDefault());
        $this->assertSame('0.00', $col->getDefault());
    }

    public function testFromPhalconWithAutoIncrementPrimary(): void
    {
        $phalconCol = new PhalconColumn('id', [
            'type'          => PhalconColumn::TYPE_INTEGER,
            'autoIncrement' => true,
            'primary'       => true,
            'first'         => true,
        ]);

        $col = PhalconColumnBridge::fromPhalcon($phalconCol);

        $this->assertTrue($col->isAutoIncrement());
        $this->assertTrue($col->isPrimary());
        $this->assertTrue($col->isFirst());
    }

    public function testFromPhalconWithGetComment(): void
    {
        $stub = new class ('col') {
            public function __construct(private string $name)
            {
            }

            public function getName(): string
            {
                return $this->name;
            }

            public function getType(): int
            {
                return 0;
            }

            public function isNotNull(): bool
            {
                return false;
            }

            public function isUnsigned(): bool
            {
                return false;
            }

            public function isAutoIncrement(): bool
            {
                return false;
            }

            public function isPrimary(): bool
            {
                return false;
            }

            public function isFirst(): bool
            {
                return false;
            }

            public function getAfterPosition(): ?string
            {
                return null;
            }

            public function getSize(): mixed
            {
                return null;
            }

            public function getScale(): mixed
            {
                return null;
            }

            public function hasDefault(): bool
            {
                return false;
            }

            public function getDefault(): mixed
            {
                return null;
            }

            public function getComment(): string
            {
                return 'a comment';
            }
        };

        $col = PhalconColumnBridge::fromPhalcon($stub);

        $this->assertSame('a comment', $col->getComment());
    }
}
