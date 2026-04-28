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

use function str_replace;

use const PHP_EOL;

/**
 * Displays SQL statements and their execution times during verbose migration runs.
 */
class Profiler
{
    public function start(string $sql, float $startTime): void
    {
        echo $startTime, ': ', str_replace(["\n", "\t"], ' ', $sql);
    }

    public function end(string $sql, float $startTime, float $endTime): void
    {
        echo '  => ', $endTime, ' (', ($endTime - $startTime), ')', PHP_EOL;
    }
}
