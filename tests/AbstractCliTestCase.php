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

namespace Phalcon\Migrations\Tests;

use FilesystemIterator;

abstract class AbstractCliTestCase extends AbstractMysqlTestCase
{
    private string $lastOutput  = '';
    private int    $lastExitCode = 0;

    protected function setUp(): void
    {
        parent::setUp();

        $outputDir = $this->getOutputDir();
        if (is_dir($outputDir)) {
            foreach (new FilesystemIterator($outputDir, FilesystemIterator::SKIP_DOTS) as $item) {
                if ($item->getFileName() === '.gitignore') {
                    continue;
                }
                $item->isDir() ? $this->removeDir($item->getRealPath()) : unlink($item->getRealPath());
            }
        }
    }

    protected function assertInOutput(string $text): void
    {
        $this->assertStringContainsString($text, $this->lastOutput);
    }

    protected function assertExitCode(int $expected): void
    {
        $this->assertSame($expected, $this->lastExitCode);
    }

    protected function runCommand(string $command): void
    {
        $output = [];
        exec($command . ' 2>&1', $output, $exitCode);
        $this->lastOutput   = implode("\n", $output);
        $this->lastExitCode = $exitCode;
    }
}
