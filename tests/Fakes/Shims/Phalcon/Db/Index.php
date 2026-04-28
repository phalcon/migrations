<?php

declare(strict_types=1);

namespace Phalcon\Db;

use Phalcon\Migrations\Tests\Fakes\Db\FakeIndex;

/**
 * Shim for Phalcon\Db\Index so that old-format migration files can be loaded
 * in tests without the Phalcon extension being installed. Extends FakeIndex so
 * it carries the same method signatures.
 */
class Index extends FakeIndex
{
}
