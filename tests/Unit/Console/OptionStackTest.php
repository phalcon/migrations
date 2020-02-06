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

use Phalcon\Migrations\Console\OptionParserTrait;
use Phalcon\Migrations\Console\OptionStack;
use PHPUnit\Framework\TestCase;

final class OptionStackTest extends TestCase
{
    use OptionParserTrait;

    /**
     * @var OptionStack
     */
    public $options;
    
    public function setUp(): void
    {
        $this->options = new OptionStack();
    }

    /**
     * @see testSetOptionAndGetOption11
     */
    public function setOptionAndGetOption11DataProvider(): array
    {
        return [
            ['foo-bar', 'bar-foo', 'foo-bar'],
            ['', 'bar-foo', 'bar-foo'],
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
        $options = [
            'test' => 'foo',
            'test2' => 'bar',
        ];
        
        $this->options->setOptions($options);
        
        $this->assertSame($options, $this->options->getOptions());
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
        $this->options->setOption($key, $option, $defaultValue);

        $this->assertSame($expected, $this->options->getOption($key));
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
        $this->options->setOption('test', 'bar');
        $this->options->setDefaultOption($key, $defaultValue);

        $this->assertSame($expected, $this->options->getOption($key));
    }

    public function testCheckingReceivedOption(): void
    {
        $this->options->setOption('true-option', 'foo-bar');

        $option1 = $this->options->isReceivedOption('true-option');
        $option2 = $this->options->isReceivedOption('false-option');

        $this->assertTrue($option1);
        $this->assertFalse($option2);
    }

    public function testReturnValidOptionOrSetDefault(): void
    {
        $this->options->setOptions(['test' => 'foo', 'test2' => 'bar']);

        $this->assertSame('foo', $this->options->getValidOption('test', 'bar'));
        $this->assertSame('bar', $this->options->getValidOption('false-option', 'bar'));
    }

    public function testReturnPrefixFromOptionWithoutSetPrefix(): void
    {
        $this->options->setOptions(['test' => 'foo', 'test2' => 'bar']);

        $this->assertSame('foo', $this->options->getPrefixOption('foo*'));
        $this->assertSame('bar', $this->options->getPrefixOption('bar*'));
    }

    public function testReturnPrefixFromOptionWithSetPrefix(): void
    {
        $this->options->setOptions(['test' => 'foo', 'test2' => 'bar']);

        $this->assertSame('foo', $this->getPrefixOption('foo^', '^'));
        $this->assertSame('bar', $this->getPrefixOption('bar?', '?'));
    }
}
