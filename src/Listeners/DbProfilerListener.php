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

use Phalcon\Events\Event;
use Phalcon\Migrations\Observer\Profiler;

/**
 * Db event listener
 */
class DbProfilerListener
{
    protected $profiler;

    public function __construct()
    {
        $this->profiler = new Profiler();
    }

    public function beforeQuery(Event $event, $connection)
    {
        $this->profiler->startProfile($connection->getSQLStatement());
    }

    public function afterQuery()
    {
        $this->profiler->stopProfile();
    }
}
