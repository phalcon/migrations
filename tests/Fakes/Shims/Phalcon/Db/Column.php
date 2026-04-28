<?php

declare(strict_types=1);

namespace Phalcon\Db;

use Phalcon\Migrations\Tests\Fakes\Db\FakeColumn;

/**
 * Shim for Phalcon\Db\Column so that old-format migration files can be loaded
 * in tests without the Phalcon extension being installed. Extends FakeColumn so
 * it carries the integer TYPE_* constants and the same method signatures.
 */
class Column extends FakeColumn
{
}
