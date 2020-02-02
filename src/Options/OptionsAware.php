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

namespace Phalcon\Migrations\Options;

use Phalcon\Migrations\Exception\InvalidArgumentException;
use Phalcon\Migrations\FactoryOptions;

/**
 * Class that has option container and processing with it
 */
class OptionsAware implements FactoryOptions
{
    /**
     * Option container
     *
     * @var array
     */
    protected $options = [];

    /**
     * @param array $options
     */
    public function __construct(array $options = null)
    {
        if (!empty($options)) {
            $this->options = $options;
        }
    }

    /**
     * Set all options to option container
     *
     * @param array $options
     */
    public function setOptions(array $options): void
    {
        $this->options = $options;
    }

    /**
     * Set one option to option container
     *
     * @param mixed $key
     * @param mixed $option
     */
    public function setOption($key, $option): void
    {
        $this->options[$key] = $option;
    }

    /**
     * Get all options from the option container
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Get valid option or throw exception
     *
     * @param mixed $key
     * @throw InvalidArgumentException
     * @return mixed
     */
    public function getOption($key)
    {
        if (!isset($this->options[$key])) {
            throw new InvalidArgumentException("Option " . $key . " has't been defined");
        }

        return $this->options[$key];
    }

    /**
     * Check whether option container has value with this key
     *
     * @param mixed $key
     * @return bool
     */
    public function hasOption($key): bool
    {
        return isset($this->options[$key]);
    }

    /**
     * Return valid option value or default value
     *
     * @param mixed $key
     * @param mixed $defaultOption
     * @return mixed
     */
    public function getValidOptionOrDefault($key, $defaultOption = '')
    {
        if (isset($this->options[$key])) {
            return $this->options[$key];
        }

        return $defaultOption;
    }
}
