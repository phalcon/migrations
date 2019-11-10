<?php
declare(strict_types=1);

namespace Phalcon\Migrations\Tests\Integration;

use PHPUnit\Framework\TestCase;
use function Phalcon\Migrations\Tests\remove_dir;
use function Phalcon\Migrations\Tests\root_path;

class IntegrationTestCase extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        ob_start();
    }

    public static function tearDownAfterClass(): void
    {
        $output = ob_get_clean();

        /**
         * Cleanup tests output folders
         */
        remove_dir(root_path('tests/var/output/'));
    }
}
