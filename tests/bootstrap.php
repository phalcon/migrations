<?php
declare(strict_types=1);

use function Phalcon\Migrations\Tests\remove_dir;
use function Phalcon\Migrations\Tests\root_path;

require_once dirname(__DIR__) . '/vendor/autoload.php';

/**
 * Cleanup tests output folders
 */
remove_dir(root_path('tests/var/output'));
