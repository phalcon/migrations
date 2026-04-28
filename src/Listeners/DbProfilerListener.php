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

namespace Phalcon\Migrations\Listeners;

use Phalcon\Migrations\Db\Connection;
use Phalcon\Migrations\Observer\Profiler;

/**
 * Attaches the query profiler to a database connection for verbose output.
 */
class DbProfilerListener
{
    protected Profiler $profiler;

    public function __construct()
    {
        $this->profiler = new Profiler();
    }

    public function attach(Connection $connection): void
    {
        $connection->setProfiler($this->profiler);
    }
}
