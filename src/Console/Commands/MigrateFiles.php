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

namespace Phalcon\Migrations\Console\Commands;

use Phalcon\Cop\Parser;
use Phalcon\Migrations\Console\Color;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function file_get_contents;
use function file_put_contents;
use function realpath;
use function sprintf;
use function str_replace;
use function strlen;
use function substr;

use const PHP_EOL;

/**
 * Updates migration files generated before the Phalcon\Db dependency was
 * removed, replacing Phalcon\Db namespace imports with the library-local ones.
 */
class MigrateFiles implements CommandsInterface
{
    private const REPLACEMENTS = [
        'use Phalcon\Db\Column;'    => 'use Phalcon\Migrations\Db\Column;',
        'use Phalcon\Db\Index;'     => 'use Phalcon\Migrations\Db\Index;',
        'use Phalcon\Db\Reference;' => 'use Phalcon\Migrations\Db\Reference;',
    ];

    public function __construct(protected Parser $parser)
    {
    }

    public function getPossibleParams(): array
    {
        return [
            'migrations=s' => 'Path to migrations directory (required)',
            'dry-run'      => 'Preview changes without writing files',
        ];
    }

    public function run(): void
    {
        $path = $this->parser->get('migrations');
        if (!$path) {
            $path = $this->parser->get(1);
        }

        if (!$path) {
            throw new CommandsException(
                'Migrations directory is required. Use --migrations=<path>'
            );
        }

        $resolved = realpath($path);
        if ($resolved === false) {
            throw new CommandsException("Directory not found: {$path}");
        }

        $dryRun   = $this->parser->has('dry-run');
        $updated  = 0;
        $scanned  = 0;

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($resolved)
        );

        /** @var SplFileInfo $file */
        foreach ($iterator as $file) {
            if (!$file->isFile() || $file->getExtension() !== 'php') {
                continue;
            }

            $scanned++;
            $original = file_get_contents($file->getPathname());
            $modified = str_replace(
                array_keys(self::REPLACEMENTS),
                array_values(self::REPLACEMENTS),
                $original,
                $count
            );

            if ($count === 0) {
                continue;
            }

            $relative = substr($file->getPathname(), strlen($resolved) + 1);
            if ($dryRun) {
                print Color::info("[dry-run] Would update: {$relative}") . PHP_EOL;
            } else {
                file_put_contents($file->getPathname(), $modified);
                print Color::success("Updated: {$relative}") . PHP_EOL;
            }

            $updated++;
        }

        $action = $dryRun ? 'Would update' : 'Updated';
        print PHP_EOL . Color::colorize(
            sprintf('%s %d file(s) out of %d scanned.', $action, $updated, $scanned)
        ) . PHP_EOL;
    }

    public function getHelp(): void
    {
        print Color::head('Help:') . PHP_EOL;
        print Color::colorize(
            '  Updates migration files to use Phalcon\Migrations\Db classes'
            . ' instead of the deprecated Phalcon\Db ones.'
        ) . PHP_EOL . PHP_EOL;

        print Color::head('Usage:') . PHP_EOL;
        print Color::colorize(
            '  migration migrate-files --migrations=<path>',
            Color::FG_GREEN
        ) . PHP_EOL . PHP_EOL;

        print Color::head('Options:') . PHP_EOL;
        print Color::colorize('  --migrations=<path>', Color::FG_GREEN);
        print Color::colorize('    Path to migrations directory') . PHP_EOL;
        print Color::colorize('  --dry-run', Color::FG_GREEN);
        print Color::colorize('             Preview changes without writing files') . PHP_EOL;
    }
}
