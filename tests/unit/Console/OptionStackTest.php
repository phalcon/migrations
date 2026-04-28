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
}
