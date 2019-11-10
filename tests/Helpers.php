<?php
declare(strict_types=1);

namespace Phalcon\Migrations\Tests;

/**
 * @param string $prefix
 * @return string
 */
function root_path(string $prefix = ''): string
{
    return join(DIRECTORY_SEPARATOR, [dirname(__DIR__), ltrim($prefix, DIRECTORY_SEPARATOR)]);
}

/**
 * @param string $path
 */
function remove_dir(string $path): void
{
    $directoryIterator = new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS);
    $iterator = new \RecursiveIteratorIterator($directoryIterator, \RecursiveIteratorIterator::CHILD_FIRST);
    foreach ($iterator as $file) {
        if ($file->getFileName() === '.gitignore') {
            continue;
        }

        $realPath = $file->getRealPath();
        $file->isDir() ? rmdir($realPath) : unlink($realPath);
    }
}
