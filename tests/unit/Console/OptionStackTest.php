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

namespace Phalcon\Migrations\Tests\Unit\Console;

use Phalcon\Migrations\Console\OptionStack;
use Phalcon\Migrations\Tests\AbstractTestCase;
use Phalcon\Migrations\Version\IncrementalItem;
use Phalcon\Migrations\Version\ItemCollection;
use Phalcon\Migrations\Version\TimestampedItem;
use PHPUnit\Framework\Attributes\DataProvider;

final class OptionStackTest extends AbstractTestCase
{
    /**
     * @see testSetOptionAndGetOption11
     */
    public static function setOptionAndGetOption11DataProvider(): array
    {
        return [
            ['foo-bar', 'bar-foo', 'foo-bar'],
            [null, 'bar-foo', 'bar-foo'],
        ];
    }

    /**
     * @see testSetDefaultOptionIfOptionDidntExist
     */
    public static function setDefaultOptionIfOptionDidntExistDataProvider(): array
    {
        return [
            ['test', 'foo-bar', 'bar'],
            ['test2', 'bar-foo', 'bar-foo'],
        ];
    }

    public function testGetAndSetOptions(): void
    {
        $data = [
            'test'  => 'foo',
            'test2' => 'bar',
        ];

        $options = new OptionStack($data);

        $this->assertSame($data, $options->getOptions());
    }

    #[DataProvider('setOptionAndGetOption11DataProvider')]
    public function testSetOptionAndGetOption11($option, $defaultValue, $expected): void
    {
        $key = 'set-test';
        $options = new OptionStack();
        $options->offsetSetOrDefault($key, $option, $defaultValue);

        $this->assertSame($expected, $options->offsetGet($key));
    }

    #[DataProvider('setDefaultOptionIfOptionDidntExistDataProvider')]
    public function testSetDefaultOptionIfOptionDidntExist($key, $defaultValue, $expected): void
    {
        $options = new OptionStack();

        $options->offsetSet('test', 'bar');
        $options->offsetSetDefault($key, $defaultValue);

        $this->assertSame($expected, $options->offsetGet($key));
    }

    public function testReturnPrefixFromOptionWithoutSetPrefix(): void
    {
        $options = new OptionStack(['test' => 'foo', 'test2' => 'bar']);

        $this->assertSame('foo', $options->getPrefixOption('foo*'));
        $this->assertSame('bar', $options->getPrefixOption('bar*'));
    }

    public function testReturnPrefixFromOptionWithSetPrefix(): void
    {
        $options = new OptionStack(['test' => 'foo', 'test2' => 'bar']);

        $this->assertSame('foo', $options->getPrefixOption('foo^', '^'));
        $this->assertSame('bar', $options->getPrefixOption('bar?', '?'));
    }

    public function testGetPrefixOptionWithoutStarSuffixReturnsEmpty(): void
    {
        $options = new OptionStack();

        $this->assertSame('', $options->getPrefixOption('foo'));
    }

    public function testOffsetExistsReturnsTrueForSetKey(): void
    {
        $options = new OptionStack(['key' => 'value']);

        $this->assertTrue($options->offsetExists('key'));
        $this->assertFalse($options->offsetExists('missing'));
    }

    public function testOffsetUnsetRemovesKey(): void
    {
        $options = new OptionStack(['key' => 'value', 'other' => 'val']);
        $options->offsetUnset('key');

        $this->assertFalse($options->offsetExists('key'));
        $this->assertTrue($options->offsetExists('other'));
    }

    public function testOffsetUnsetIgnoresMissingKey(): void
    {
        $options = new OptionStack(['key' => 'value']);
        $options->offsetUnset('missing');

        $this->assertSame(['key' => 'value'], $options->getOptions());
    }

    public function testGetVersionNameGeneratingMigrationWithDescr(): void
    {
        $options = new OptionStack([
            'descr'         => 'initial',
            'migrationsDir' => [],
        ]);

        $version = $options->getVersionNameGeneratingMigration();

        $this->assertInstanceOf(TimestampedItem::class, $version);
        $this->assertStringEndsWith('_initial', $version->getVersion());
        ItemCollection::setType(ItemCollection::TYPE_INCREMENTAL);
    }

    public function testGetVersionNameGeneratingMigrationWithExplicitVersion(): void
    {
        $options = new OptionStack([
            'version'       => '2.0.0',
            'migrationsDir' => [],
            'force'         => false,
        ]);

        $version = $options->getVersionNameGeneratingMigration();

        $this->assertInstanceOf(IncrementalItem::class, $version);
        $this->assertSame('2.0.0', $version->getVersion());
    }

    public function testGetVersionNameGeneratingMigrationAutoReturnsFirstVersion(): void
    {
        $options = new OptionStack([
            'migrationsDir' => [],
        ]);

        $version = $options->getVersionNameGeneratingMigration();

        $this->assertInstanceOf(IncrementalItem::class, $version);
        $this->assertSame('1.0.0', $version->getVersion());
    }
}
