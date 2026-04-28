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

namespace Phalcon\Migrations\Tests\Fakes\Mvc\Model;

use Phalcon\Migrations\Mvc\Model\Migration;

class MigrationFake extends Migration
{
    public function setVersion(?string $version): void
    {
        $this->version = $version;
    }
}
