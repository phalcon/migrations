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

use Phalcon\Db\ColumnInterface;
use Phalcon\Db\Exception;

class UnknownColumnTypeException extends Exception
{
    public function __construct(protected ColumnInterface $column)
    {
        $message = sprintf(
            'Unrecognized data type "%s" for column "%s".',
            $column->getType(),
            $column->getName()
        );

        parent::__construct($message, 0);
    }

    public function getColumn(): ColumnInterface
    {
        return $this->column;
    }
}
