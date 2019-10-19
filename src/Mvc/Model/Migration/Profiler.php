<?php

/**
 * This file is part of the Phalcon Migrations.
 *
 * (c) Phalcon Team <team@phalcon.io>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

namespace Phalcon\Mvc\Model\Migration;

use Phalcon\Db\Profiler as DbProfiler;
use Phalcon\Db\Profiler\Item;

/**
 * Displays transactions made on the database and the times them taken to execute
 */
class Profiler extends DbProfiler
{
    /**
     * @param Item $profile
     */
    public function beforeStartProfile($profile)
    {
        echo $profile->getInitialTime() , ': ' , str_replace([ "\n", "\t" ], " ", $profile->getSqlStatement());
    }

    /**
     * @param Item $profile
     */
    public function afterEndProfile($profile)
    {
        echo '  => ' , $profile->getFinalTime() , ' (' , ($profile->getTotalElapsedSeconds()) , ')' , PHP_EOL;
    }
}
