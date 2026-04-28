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

namespace Phalcon\Migrations\Tests\Unit\Observer;

use Phalcon\Migrations\Observer\Profiler;
use Phalcon\Migrations\Tests\AbstractTestCase;

final class ProfilerTest extends AbstractTestCase
{
    public function testStartOutputsTimestampAndSql(): void
    {
        $profiler  = new Profiler();
        $sql       = 'SELECT 1';
        $startTime = microtime(true);

        ob_start();
        $profiler->start($sql, $startTime);
        $output = ob_get_clean();

        $this->assertStringContainsString($sql, $output);
        $this->assertStringContainsString((string) $startTime, $output);
    }

    public function testStartNormalizesWhitespaceInSql(): void
    {
        $profiler = new Profiler();

        ob_start();
        $profiler->start("SELECT\n\t1", 1.0);
        $output = ob_get_clean();

        $this->assertStringContainsString('SELECT  1', $output);
    }

    public function testEndOutputsDuration(): void
    {
        $profiler  = new Profiler();
        $startTime = 1000.0;
        $endTime   = 1000.5;

        ob_start();
        $profiler->end('SELECT 1', $startTime, $endTime);
        $output = ob_get_clean();

        $this->assertStringContainsString((string) $endTime, $output);
        $this->assertStringContainsString('0.5', $output);
    }
}
