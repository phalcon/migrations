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

namespace Phalcon\Migrations\Exception;

class InvalidArgumentException extends \InvalidArgumentException
{
    public static function parametersNotDefined(string $method): self
    {
        return new self("Parameters weren't defined in " . $method);
    }

    public static function tablePrefixNoMatch(): self
    {
        return new self("Specified table prefix doesn't match with any table name");
    }

    public static function unsupportedDatabaseAdapter(string $adapter): self
    {
        return new self('Unsupported database adapter: ' . $adapter);
    }

    public static function unsupportedDatabaseDriver(string $driver): self
    {
        return new self("Unsupported database driver: " . $driver);
    }

    public static function wrongVersionNumber(): self
    {
        return new self('Wrong version number provided');
    }
}
