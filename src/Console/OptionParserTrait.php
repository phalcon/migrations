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

namespace Phalcon\Migrations\Console;

use InvalidArgumentException;
use Phalcon\Migrations\Mvc\Model\Migration as ModelMigration;
use Phalcon\Migrations\Version\IncrementalItem as IncrementalVersion;
use Phalcon\Migrations\Version\ItemCollection as VersionCollection;
use Phalcon\Migrations\Version\ItemInterface;

/**
 * Parsing CLI options
 *
 * @property array $options
 */
trait OptionParserTrait
{
    /**
     * Get prefix from the option
     *
     * @param string $prefix
     * @param mixed $prefixEnd
     * @return mixed
     */
    public function getPrefixOption(string $prefix, $prefixEnd = '*')
    {
        if (substr($prefix, -1) != $prefixEnd) {
            return '';
        }

        return substr($prefix, 0, -1);
    }

    /**
     * Get version name to generate migration
     */
    public function getVersionNameGeneratingMigration(): ItemInterface
    {
        if (empty($this->options)) {
            throw new InvalidArgumentException('Options were not defined yet');
        }

        // Use timestamped version if description is provided
        if ($this->options['descr']) {
            $this->options['version'] = (string)(int)(microtime(true) * pow(10, 6));
            VersionCollection::setType(VersionCollection::TYPE_TIMESTAMPED);
            $versionItem = VersionCollection::createItem($this->options['version'] . '_' . $this->options['descr']);

            // Elsewhere use old-style incremental versioning
            // The version is specified
        } elseif ($this->options['version']) {
            VersionCollection::setType(VersionCollection::TYPE_INCREMENTAL);
            $versionItem = VersionCollection::createItem($this->options['version']);
            //check version is exist
            $migrationsDirList = $this->options['migrationsDir'];
            if (is_array($migrationsDirList)) {
                foreach ($migrationsDirList as $migrationsDir) {
                    $migrationsSubDirList = ModelMigration::scanForVersions($migrationsDir);
                    if (is_array($migrationsSubDirList)) {
                        foreach ($migrationsSubDirList as $item) {
                            if ($item->getVersion() != $versionItem->getVersion()) {
                                continue;
                            }
                            if (!$this->options['force']) {
                                throw new \LogicException('Version ' . $item->getVersion() . ' already exists');
                            } else {
                                rmdir(rtrim($migrationsDir, '\\/') . DIRECTORY_SEPARATOR . $versionItem->getVersion());
                            }
                        }
                    }
                }
            }
            // The version is guessed automatically
        } else {
            VersionCollection::setType(VersionCollection::TYPE_INCREMENTAL);
            $versionItems = [];
            $migrationsDirList = $this->options['migrationsDir'];

            if (is_array($migrationsDirList)) {
                foreach ($migrationsDirList as $migrationsDir) {
                    $versionItems = $versionItems + ModelMigration::scanForVersions($migrationsDir);
                }
            }

            if (!isset($versionItems[0])) {
                $versionItem = VersionCollection::createItem('1.0.0');
            } else {
                /** @var IncrementalVersion $versionItem */
                $versionItem = VersionCollection::maximum($versionItems);
                $versionItem = $versionItem->addMinor(1);
            }
        }

        return $versionItem;
    }
}
