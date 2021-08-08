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

namespace Phalcon\Migrations\Utils;

use Phalcon\Migrations\Console\Color;
use Phalcon\Migrations\Exception\RuntimeException;
use Phalcon\Migrations\Version\ItemInterface;

class Helper
{
    public function getMigrationsDir($migrationsDirs)
    {
        if (is_array($migrationsDirs)) {
            if (count($migrationsDirs) > 1) {
                $question = 'Which migrations path would you like to use?' . PHP_EOL;
                foreach ($migrationsDirs as $id => $dir) {
                    $question .= " [{$id}] $dir" . PHP_EOL;
                }

                fwrite(STDOUT, Color::info($question));
                $handle = fopen('php://stdin', 'r');
                $line = (int)fgets($handle);
                if (!isset($migrationsDirs[$line])) {
                    echo "ABORTING!\n";
                    return false;
                }

                fclose($handle);
                $migrationsDir = $migrationsDirs[$line];
            } else {
                $migrationsDir = $migrationsDirs[0];
            }
        } else {
            $migrationsDir = $migrationsDirs;
        }

        if (!$migrationsDir) {
            throw new RuntimeException('Migrations directory is not defined. Cannot proceed');
        }

        if (!file_exists($migrationsDir)) {
            mkdir($migrationsDir, 0755, true);
        }

        return $migrationsDir;
    }

    public function getMigrationsPath(ItemInterface $versionItem, $migrationsDir, $verbose, $force): string
    {
        // Path to migration dir
        $migrationPath = rtrim($migrationsDir, '\\/') . DIRECTORY_SEPARATOR . $versionItem->getVersion();
        if (!file_exists($migrationPath)) {
            $dirIsWritable = is_writable(dirname($migrationPath));
            if (!$verbose && $dirIsWritable) {
                mkdir($migrationPath);
            } elseif (!$dirIsWritable) {
                throw new RuntimeException("Unable to write '{$migrationPath}' directory. Permission denied");
            }
        } elseif (!$force) {
            throw new RuntimeException('Version ' . $versionItem->getVersion() . ' already exists');
        }

        return $migrationPath;
    }
}
