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

/**
 * CLI options
 */
class OptionStack
{
    use OptionParserTrait;

    /**
     * Parameters received by the script.
     *
     * @var array
     */
    protected $options = [];

    /**
     * Add option to array
     *
     * @param mixed $key
     * @param mixed $option
     * @param mixed $defaultValue
     */
    public function setOption($key, $option, $defaultValue = ''): void
    {
        if (!empty($option)) {
            $this->options[$key] = $option;

            return;
        }

        $this->options[$key] = $defaultValue;
    }

    /**
     * Set option if value isn't exist
     *
     * @param string $key
     * @param mixed $defaultValue
     */
    public function setDefaultOption(string $key, $defaultValue): void
    {
        if (!isset($this->options[$key])) {
            $this->options[$key] = $defaultValue;
        }
    }

    /**
     * Get received options
     *
     * @return mixed
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Set received options
     *
     * @param array $options
     */
    public function setOptions(array $options): void
    {
        $this->options = array_merge($this->options, $options);
    }

    /**
     * Get option
     * @param string $key
     * @return mixed
     */
    public function getOption($key)
    {
        if (!isset($this->options[$key])) {
            return '';
        }

        return $this->options[$key];
    }

    /**
     * Get option if existence or get default option
     *
     * @param string $key
     * @param mixed $defaultOption
     * @return mixed
     */
    public function getValidOption($key, $defaultOption = '')
    {
        if (isset($this->options[$key])) {
            return $this->options[$key];
        }

        return $defaultOption;
    }

    /**
     * Count options
     */
    public function countOptions(): int
    {
        return count($this->options);
    }

    /**
     * Indicates whether the script was a particular option.
     *
     * @param string $key
     * @return bool
     */
    public function isReceivedOption($key): bool
    {
        return isset($this->options[$key]);
    }
}
