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
use Phalcon\Migrations\Utils\Config;
use Phalcon\Migrations\Version\ItemCollection;

/**
 * Completed versions must be ordered naturally (1.0.2 before 1.0.10),
 * both in the `.phalcon/migration-version` file and in the log table.
 *
 * @see https://github.com/phalcon/migrations/issues/181
 */
final class VersionLogTest extends AbstractTestCase
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

    public function testFileLogIsWrittenInNaturalOrder(): void
    {
        $directory = $this->getOutputDir('version-log-add');
        $options   = ['directory' => $directory];

        Migrations::addCurrentVersion($options, '1.0.2');
        Migrations::addCurrentVersion($options, '1.0.10');
        Migrations::addCurrentVersion($options, '1.0.1');

        $this->assertSame(
            "1.0.1\n1.0.2\n1.0.10",
            file_get_contents($directory . '/.phalcon/migration-version')
        );
    }

    public function testFileLogStaysInNaturalOrderAfterRemovingVersion(): void
    {
        $directory = $this->getOutputDir('version-log-remove');
        $options   = ['directory' => $directory];

        Migrations::addCurrentVersion($options, '1.0.9');
        Migrations::addCurrentVersion($options, '1.0.10');
        Migrations::addCurrentVersion($options, '1.0.2');
        Migrations::removeCurrentVersion($options, '1.0.2');

        $this->assertSame(
            "1.0.9\n1.0.10",
            file_get_contents($directory . '/.phalcon/migration-version')
        );
    }

    public function testDbLogCurrentVersionIsNaturalMaximum(): void
    {
        $options = [
            'migrationsInDb' => true,
            'config'         => Config::fromArray([
                'database' => [
                    'adapter' => 'sqlite',
                    'dbname'  => ':memory:',
                ],
            ]),
        ];

        Migrations::addCurrentVersion($options, '1.0.9');
        Migrations::addCurrentVersion($options, '1.0.10');

        $this->assertSame('1.0.10', Migrations::getCurrentVersion($options)->getVersion());
    }

    public function testDbLogCurrentVersionWhenEmpty(): void
    {
        $options = [
            'migrationsInDb' => true,
            'config'         => Config::fromArray([
                'database' => [
                    'adapter' => 'sqlite',
                    'dbname'  => ':memory:',
                ],
            ]),
        ];

        $this->assertSame('0.0.0', Migrations::getCurrentVersion($options)->getVersion());
    }
}
