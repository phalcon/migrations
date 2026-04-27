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

namespace Phalcon\Migrations\Db;

final class Index
{
    public const TYPE_PRIMARY     = 'PRIMARY KEY';
    public const TYPE_PRIMARY_ALT = 'PRIMARY';
    public const TYPE_UNIQUE      = 'UNIQUE';

    public function __construct(
        private readonly string $name,
        private readonly array $columns,
        private readonly string $type = '',
    ) {
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
