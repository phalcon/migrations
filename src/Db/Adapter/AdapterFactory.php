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

namespace Phalcon\Migrations\Db\Adapter;

use InvalidArgumentException;
use Phalcon\Migrations\Db\Connection;

use function strtolower;

final class AdapterFactory
{
    private static array $map = [
        'mysql'      => Mysql::class,
        'postgresql' => Postgresql::class,
        'sqlite'     => Sqlite::class,
    ];

    public static function create(Connection $connection): AdapterInterface
    {
        $driver = strtolower($connection->getDriverName());

        $adapterClass = match ($driver) {
            'mysql'  => Mysql::class,
            'pgsql'  => Postgresql::class,
            'sqlite' => Sqlite::class,
            default  => throw new InvalidArgumentException("Unsupported database driver: {$driver}"),
        };

        return new $adapterClass($connection);
    }
}
