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

use ArrayAccess;
use LogicException;
use Phalcon\Migrations\Mvc\Model\Migration as ModelMigration;
use Phalcon\Migrations\Version\IncrementalItem as IncrementalVersion;
use Phalcon\Migrations\Version\ItemCollection as VersionCollection;
use Phalcon\Migrations\Version\ItemInterface;

/**
 * CLI options
 */
class OptionStack implements ArrayAccess
{
    /**
     * Parameters received by the script.
     *
     * @var array
     */
    protected array $options = [];

    /**
     * @param array $options
     */
    public function __construct(array $options = [])
    {
        $this->options = $options;
    }

    /**
     * Get received options
     *
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }

    /**
     * @param mixed $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return isset($this->options[$offset]);
    }

    /**
     * @param mixed $offset
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->options[$offset] ?? '';
    }

    /**
     * @param mixed $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        $this->options[$offset] = $value;
    }

    /**
     * @param mixed       $offset
     * @param mixed|null  $default
     */
    public function offsetSetDefault($offset, $default = null): void
    {
        if (!array_key_exists($offset, $this->options)) {
            $this->options[$offset] = $default;
        }
    }

    /**
     * @param mixed       $offset
     * @param mixed|null  $value
     * @param mixed|null  $default
     */
    public function offsetSetOrDefault($offset, $value = null, $default = null): void
    {
        $this->options[$offset] = $value ?: $default;
    }

    /**
     * @param mixed $offset
     */
    public function offsetUnset($offset): void
    {
        if (array_key_exists($offset, $this->options)) {
            unset($this->options[$offset]);
        }
    }

    /**
     * Get prefix from the option
     *
     * @param string $prefix
     * @param mixed  $prefixEnd
     *
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
     *
     * @return ItemInterface
     */
    public function getVersionNameGeneratingMigration(): ItemInterface
    {
        /**
         * Use timestamped version if description is provided.
         */
        if (isset($this->options['descr'])) {
            $this->options['version'] = (string) (int) (microtime(true) * pow(10, 6));
            VersionCollection::setType(VersionCollection::TYPE_TIMESTAMPED);

            return VersionCollection::createItem($this->options['version'] . '_' . $this->options['descr']);
        }

        VersionCollection::setType(VersionCollection::TYPE_INCREMENTAL);
        $migrationsDirList = is_array($this->options['migrationsDir']) ? $this->options['migrationsDir'] : [];

        /**
         * Elsewhere, use old-style incremental versioning.
         * The version is specified.
         */
        if (isset($this->options['version'])) {
            $versionItem = VersionCollection::createItem($this->options['version']);
            // Check version if exists.
            foreach ($migrationsDirList as $migrationsDir) {
                $migrationsSubDirList = ModelMigration::scanForVersions($migrationsDir);

                foreach ($migrationsSubDirList as $item) {
                    if ($item->getVersion() != $versionItem->getVersion()) {
                        continue;
                    }

                    if (!$this->options['force']) {
                        throw new LogicException('Version ' . $item->getVersion() . ' already exists');
                    } else {
                        rmdir(rtrim($migrationsDir, '\\/') . DIRECTORY_SEPARATOR . $versionItem->getVersion());
                    }
                }
            }

            return $versionItem;
        }

        /**
         * The version is guessed automatically
         */
        $versionItems = [];
        foreach ($migrationsDirList as $migrationsDir) {
            $versionItems = $versionItems + ModelMigration::scanForVersions($migrationsDir);
        }

        if (!isset($versionItems[0])) {
            $versionItem = VersionCollection::createItem('1.0.0');
        } else {
            /** @var IncrementalVersion $versionItem */
            $versionItem = VersionCollection::maximum($versionItems);
            $versionItem = $versionItem->addMinor(1);
        }

        return $versionItem;
    }
}
