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

use Exception;

class CommandsException extends Exception
{
    public static function configBuilderNotFound(): self
    {
        return new self("Builder can't locate the configuration file.");
    }

    public static function configExtensionNotFound(): self
    {
        return new self('Config file extension not found.');
    }

    public static function configNotFound(): self
    {
        return new self("Can't locate the configuration file.");
    }

    public static function directoryNotFound(string $path): self
    {
        return new self("Directory not found: {$path}");
    }

    public static function migrationsDirectoryRequired(): self
    {
        return new self('Migrations directory is required. Use --migrations=<path>');
    }

    public static function unknownAction(): self
    {
        return new self('Unknown action. Use help, h or ? to see all available commands');
    }
}
