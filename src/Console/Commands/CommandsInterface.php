<?php

/**
 * This file is part of the Phalcon Developer Tools.
 *
 * (c) Phalcon Team <team@phalcon.io>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Phalcon\Migrations\Console\Commands;

/**
 * Commands Interface
 *
 * This interface must be implemented by all commands
 */
interface CommandsInterface
{
    /**
     * Executes the command.
     */
    public function run(): void;

    /**
     * Prints help on the usage of the command.
     */
    public function getHelp(): void;

    /**
     * Gets possible command parameters.
     *
     * This method returns a list of available parameters for the current command.
     * The list must be represented as pairs key-value.
     * Where key is the parameter name and value is the short description.
     */
    public function getPossibleParams(): array;
}
