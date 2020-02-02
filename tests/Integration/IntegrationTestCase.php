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

namespace Phalcon\Migrations\Tests\Integration;

use Phalcon\Db\Adapter\Pdo\AbstractPdo;
use PHPUnit\Framework\TestCase;

use function Phalcon\Migrations\Tests\remove_dir;
use function Phalcon\Migrations\Tests\root_path;

class IntegrationTestCase extends TestCase
{
    /**
     * @var array
     */
    protected static $generateConfig;

    /**
     * @var AbstractPdo
     */
    protected $db;

    public static function tearDownAfterClass(): void
    {
        ob_get_clean();

        /**
         * Cleanup tests output folders
         */
        remove_dir(root_path('tests/var/output/'));
    }
}
