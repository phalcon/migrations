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

namespace Phalcon\Migrations\Console;

use ArrayAccess;

/**
 * CLI options
 */
class OptionStack implements ArrayAccess
{
    use OptionParserTrait;

    /**
     * Parameters received by the script.
     *
     * @var array
     */
    protected $options = [];

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    /**
     * Get received options
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return isset($this->options[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed|string
     */
    public function offsetGet($offset)
    {
        return $this->options[$offset] ?? '';
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        $this->options[$offset] = $value;
    }

    /**
     * @param $offset
     * @param null $default
     */
    public function offsetSetDefault($offset, $default = null): void
    {
        if (!array_key_exists($offset, $this->options)) {
            $this->options[$offset] = $default;
        }
    }

    /**
     * @param $offset
     * @param null $value
     * @param null $default
     */
    public function offsetSetOrDefault($offset, $value = null, $default = null): void
    {
        $this->options[$offset] = $value !== null ? $value : $default;
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset): void
    {
        if (array_key_exists($offset, $this->options)) {
            unset($this->options[$offset]);
        }
    }
}
