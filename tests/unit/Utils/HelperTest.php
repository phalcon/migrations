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

namespace Phalcon\Migrations\Tests\Unit\Utils;

use Phalcon\Migrations\Exception\RuntimeException;
use Phalcon\Migrations\Tests\AbstractTestCase;
use Phalcon\Migrations\Utils\Helper;
use Phalcon\Migrations\Version\IncrementalItem;

use const DIRECTORY_SEPARATOR;

final class HelperTest extends AbstractTestCase
{
    private Helper $helper;

    protected function setUp(): void
    {
        parent::setUp();
        $this->helper = new Helper();
    }

    public function testGetMigrationsDirWithString(): void
    {
        $dir = $this->getOutputDir('helper-string');

        $result = $this->helper->getMigrationsDir($dir);

        $this->assertSame($dir, $result);
    }

    public function testGetMigrationsDirWithSingleElementArray(): void
    {
        $dir = $this->getOutputDir('helper-array');

        $result = $this->helper->getMigrationsDir([$dir]);

        $this->assertSame($dir, $result);
    }

    public function testGetMigrationsDirCreatesNonExistentDirectory(): void
    {
        $baseDir = $this->getOutputDir('helper-create');
        $newDir  = $baseDir . DIRECTORY_SEPARATOR . 'new-subdir';

        $result = $this->helper->getMigrationsDir($newDir);

        $this->assertSame($newDir, $result);
        $this->assertTrue(is_dir($newDir));
    }

    public function testGetMigrationsDirThrowsOnEmptyString(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Migrations directory is not defined. Cannot proceed');

        $this->helper->getMigrationsDir('');
    }

    public function testGetMigrationsPathCreatesVersionDirectory(): void
    {
        $baseDir = $this->getOutputDir('helper-path-create');
        $version = new IncrementalItem('1.0.0');

        $result = $this->helper->getMigrationsPath($version, $baseDir, false, false);

        $expected = rtrim($baseDir, '\\/') . DIRECTORY_SEPARATOR . '1.0.0';
        $this->assertSame($expected, $result);
        $this->assertTrue(is_dir($result));
    }

    public function testGetMigrationsPathThrowsWhenVersionExists(): void
    {
        $baseDir    = $this->getOutputDir('helper-path-exists');
        $version    = new IncrementalItem('1.0.0');
        $versionDir = $baseDir . DIRECTORY_SEPARATOR . '1.0.0';

        mkdir($versionDir);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Version 1.0.0 already exists');

        $this->helper->getMigrationsPath($version, $baseDir, false, false);
    }

    public function testGetMigrationsPathWithForceAllowsExistingVersion(): void
    {
        $baseDir    = $this->getOutputDir('helper-path-force');
        $version    = new IncrementalItem('1.0.0');
        $versionDir = $baseDir . DIRECTORY_SEPARATOR . '1.0.0';

        mkdir($versionDir);

        $result = $this->helper->getMigrationsPath($version, $baseDir, false, true);

        $this->assertSame($versionDir, $result);
    }

    public function testGetMigrationsPathVerboseSkipsDirectoryCreation(): void
    {
        $baseDir  = $this->getOutputDir('helper-path-verbose');
        $version  = new IncrementalItem('1.0.0');
        $expected = rtrim($baseDir, '\\/') . DIRECTORY_SEPARATOR . '1.0.0';

        $result = $this->helper->getMigrationsPath($version, $baseDir, true, false);

        $this->assertSame($expected, $result);
        $this->assertFalse(is_dir($result));
    }
}
