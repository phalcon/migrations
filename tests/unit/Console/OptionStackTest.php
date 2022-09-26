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

use Codeception\Test\Unit;
use Phalcon\Migrations\Console\OptionStack;

final class OptionStackTest extends Unit
{
    /**
     * @see testSetOptionAndGetOption11
     */
    public function setOptionAndGetOption11DataProvider(): array
    {
        return [
            ['foo-bar', 'bar-foo', 'foo-bar'],
            [null, 'bar-foo', 'bar-foo'],
        ];
    }

    /**
     * @see testSetDefaultOptionIfOptionDidntExist
     */
    public function setDefaultOptionIfOptionDidntExistDataProvider(): array
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

    /**
     * @dataProvider setOptionAndGetOption11DataProvider
     *
     * @param $option
     * @param $defaultValue
     * @param $expected
     */
    public function testSetOptionAndGetOption11($option, $defaultValue, $expected): void
    {
        $key = 'set-test';
        $options = new OptionStack();
        $options->offsetSetOrDefault($key, $option, $defaultValue);

        $this->assertSame($expected, $options->offsetGet($key));
    }

    /**
     * @dataProvider setDefaultOptionIfOptionDidntExistDataProvider
     *
     * @param $key
     * @param $defaultValue
     * @param $expected
     */
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
