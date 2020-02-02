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

use function Phalcon\Migrations\Tests\remove_dir;
use function Phalcon\Migrations\Tests\root_path;

require_once dirname(__DIR__) . '/vendor/autoload.php';

/**
 * Cleanup tests output folders
 */
remove_dir(root_path('tests/var/output'));
