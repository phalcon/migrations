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

namespace Phalcon\Migrations\Tests\Fakes\Console\Commands;

use Phalcon\Migrations\Console\Commands\Migration;
use Phalcon\Migrations\Utils\Config;

class MigrationFake extends Migration
{
    public function publicLoadConfig(string $fileName): Config
    {
        return $this->loadConfig($fileName);
    }

    public function publicGetConfig(string $path): Config
    {
        return $this->getConfig($path);
    }

    public function publicIsAbsolutePath(string $path): bool
    {
        return $this->isAbsolutePath($path);
    }

    public function publicPrintParameters(array $parameters): void
    {
        $this->printParameters($parameters);
    }

    public function publicExportFromTables(Config $config): array
    {
        return $this->exportFromTables($config);
    }
}
