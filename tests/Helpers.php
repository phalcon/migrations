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

namespace Phalcon\Migrations\Tests;

use Phalcon\Db\Adapter\Pdo\AbstractPdo;

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

/**
 * @see https://gist.github.com/afischoff/9608738
 * @see https://github.com/phalcon/cphalcon/issues/14620
 *
 * @param AbstractPdo $db
 * @param string $table
 * @param array $columns
 * @param array $rows
 */
function db_batch_insert(AbstractPdo $db, string $table, array $columns, array $rows): void
{
    $str = '';
    foreach ($rows as $values) {
        foreach ($values as &$val) {
            if (is_null($val)) {
                $val = 'NULL';
                continue;
            }

            if (is_string($val)) {
                $val = $db->escapeString($val);
            }
        }

        $str .= sprintf('(%s),', implode(',', $values));
    }

    $str = rtrim($str, ',');
    $str .= ';';
    $query = sprintf(
        "INSERT INTO `%s` (%s) VALUES %s",
        $table,
        sprintf('`%s`', implode('`,`', $columns)),
        $str
    );

    $db->execute($query);
}
