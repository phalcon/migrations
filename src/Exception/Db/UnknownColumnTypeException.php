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

namespace Phalcon\Migrations\Exception\Db;

use Phalcon\Migrations\Db\Column;
use Phalcon\Migrations\Exception\RuntimeException;

use function sprintf;

class UnknownColumnTypeException extends RuntimeException
{
    public function __construct(protected Column $column)
    {
        parent::__construct(
            sprintf(
                'Unrecognized data type "%s" for column "%s".',
                $column->getType(),
                $column->getName()
            )
        );
    }

    public static function forColumn(Column $column): self
    {
        return new self($column);
    }

    public function getColumn(): Column
    {
        return $this->column;
    }
}
