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

use function mkdir;
use function ob_get_clean;
use function ob_start;

/**
 * @see https://github.com/phalcon/migrations/issues/243
 */
final class Issue243Test extends AbstractTestCase
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

    public function testListAllShowsVersionsOfAllFolders(): void
    {
        $first  = $this->getOutputDir('issue-243-first');
        $second = $this->getOutputDir('issue-243-second');
        mkdir($first . '/1.0.0');
        mkdir($second . '/2.0.0');

        ob_start();
        try {
            Migrations::listAll([
                'config'         => Config::fromArray([
                    'database' => [
                        'adapter' => 'sqlite',
                        'dbname'  => ':memory:',
                    ],
                ]),
                'migrationsDir'  => [$first, $second],
                'migrationsInDb' => true,
                'tsBased'        => false,
            ]);
        } finally {
            $output = (string) ob_get_clean();
        }

        $this->assertStringContainsString('1.0.0', $output);
        $this->assertStringContainsString('2.0.0', $output);
    }
}
