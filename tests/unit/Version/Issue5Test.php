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

namespace Phalcon\Migrations\Tests\Unit\Version;

use Phalcon\Migrations\Migrations;
use Phalcon\Migrations\Tests\AbstractTestCase;
use Phalcon\Migrations\Version\ItemCollection;

use function file_get_contents;

/**
 * The option "log-path" sets the path of the file log, so that each database
 * can have its own log.
 *
 * @see https://github.com/phalcon/migrations/issues/5
 */
final class Issue5Test extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Migrations::resetStorage();
        ItemCollection::setType(ItemCollection::TYPE_INCREMENTAL);
    }

    protected function tearDown(): void
    {
        Migrations::resetStorage();
        parent::tearDown();
    }

    public function testFileLogCreatesFolderOfLogPath(): void
    {
        $directory = $this->getOutputDir('issue-5-folder');
        $logPath   = $directory . '/logs/migrations.log';

        Migrations::addCurrentVersion(['directory' => $directory, 'log-path' => $logPath], '1.0.1');

        $this->assertSame('1.0.1', file_get_contents($logPath));
    }

    public function testFileLogUsesLogPath(): void
    {
        $directory = $this->getOutputDir('issue-5-path');
        $logPath   = $directory . '/db1-migrations.log';

        Migrations::addCurrentVersion(['directory' => $directory, 'log-path' => $logPath], '1.0.1');

        $this->assertSame('1.0.1', file_get_contents($logPath));
        $this->assertDirectoryDoesNotExist($directory . '/.phalcon');
    }

    public function testTwoDatabasesHaveSeparateFileLogs(): void
    {
        $directory = $this->getOutputDir('issue-5-two-logs');
        $first     = ['directory' => $directory, 'log-path' => $directory . '/first.log'];
        $second    = ['directory' => $directory, 'log-path' => $directory . '/second.log'];

        Migrations::addCurrentVersion($first, '1.0.1');
        Migrations::resetStorage();
        Migrations::addCurrentVersion($second, '1.0.2');
        Migrations::resetStorage();

        $this->assertSame('1.0.1', Migrations::getCurrentVersion($first)->getVersion());
        Migrations::resetStorage();
        $this->assertSame('1.0.2', Migrations::getCurrentVersion($second)->getVersion());
    }
}
