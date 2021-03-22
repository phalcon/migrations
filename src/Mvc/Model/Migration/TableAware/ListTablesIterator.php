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

namespace Phalcon\Migrations\Mvc\Model\Migration\TableAware;

use DirectoryIterator;
use InvalidArgumentException;

class ListTablesIterator implements ListTablesInterface
{
    /**
     * Get table names with prefix for running migration
     *
     * @param string $tablePrefix
     * @param DirectoryIterator|null $iterator
     * @return string
     */
    public function listTablesForPrefix(string $tablePrefix, DirectoryIterator $iterator = null): string
    {
        if (empty($tablePrefix) || $iterator === null) {
            throw new InvalidArgumentException("Parameters weren't defined in " . __METHOD__);
        }

        $fileNames = [];
        $length = strlen($tablePrefix);
        foreach ($iterator as $fileInfo) {
            if (substr($fileInfo->getFilename(), 0, $length) === $tablePrefix) {
                $file = explode('.', $fileInfo->getFilename());
                $fileNames[] = $file[0];
            }
        }

        return implode(',', array_unique($fileNames));
    }
}
