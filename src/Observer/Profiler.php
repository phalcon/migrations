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

namespace Phalcon\Migrations\Observer;

use Phalcon\Db\Profiler as DbProfiler;
use Phalcon\Db\Profiler\Item;

use function str_replace;

use const PHP_EOL;

/**
 * Displays transactions made on the database and the times them taken to execute
 */
class Profiler extends DbProfiler
{
    public function beforeStartProfile(Item $profile): void
    {
        echo $profile->getInitialTime(), ': ', str_replace(["\n", "\t"], " ", $profile->getSqlStatement());
    }

    public function afterEndProfile(Item $profile): void
    {
        echo '  => ', $profile->getFinalTime(), ' (', ($profile->getTotalElapsedSeconds()), ')', PHP_EOL;
    }
}
