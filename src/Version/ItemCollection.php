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

namespace Phalcon\Migrations\Version;

use LogicException;

use function array_values;
use function krsort;
use function ksort;
use function preg_match;

/**
 * The item collection lets you work with an abstract ItemInterface.
 */
class ItemCollection
{
    /**
     * Incremental version item
     */
    public const TYPE_INCREMENTAL = 1;

    /**
     * Timestamp prefixed version item
     */
    public const TYPE_TIMESTAMPED = 2;

    public static int $type = self::TYPE_INCREMENTAL;

    /**
     * Set collection type
     */
    public static function setType(int $type): void
    {
        self::$type = $type;
    }

    /**
     * Create new version item
     */
    public static function createItem(string $version = null): TimestampedItem|IncrementalItem
    {
        if (self::TYPE_INCREMENTAL === self::$type) {
            $version = $version ?: '0.0.0';

            return new IncrementalItem($version);
        } elseif (self::TYPE_TIMESTAMPED === self::$type) {
            $version = $version ?: '0000000_0';

            return new TimestampedItem($version);
        }

        throw new LogicException('Could not create an item of unknown type.');
    }

    /**
     * Check if provided version is correct
     */
    public static function isCorrectVersion(string $version): bool
    {
        if (self::TYPE_INCREMENTAL === self::$type) {
            return 1 === preg_match('#[0-9]+(\.[z0-9]+)+#', $version);
        } elseif (self::TYPE_TIMESTAMPED === self::$type) {
            return 1 === preg_match('#^[\d]{7,}(?:\_[a-z0-9]+)*$#', $version);
        }

        return false;
    }

    /**
     * Get the maximum value from the list of version items
     */
    public static function maximum(array $versions): ?ItemInterface
    {
        if (count($versions) === 0) {
            return null;
        }

        $versions = self::sortDesc($versions);

        return $versions[0] ?? null;
    }

    /**
     * Sort items in the descending order
     *
     * @param ItemInterface[] $versions
     *
     * @return ItemInterface[]
     */
    public static function sortDesc(array $versions): array
    {
        $sortData = [];
        foreach ($versions as $version) {
            $sortData[$version->getStamp()] = $version;
        }
        krsort($sortData);

        return array_values($sortData);
    }

    /**
     * Get all the versions between two limitary version items
     *
     * @param ItemInterface   $initialVersion
     * @param ItemInterface   $finalVersion
     * @param ItemInterface[] $versions
     *
     * @return ItemInterface[]|array
     */
    public static function between(ItemInterface $initialVersion, ItemInterface $finalVersion, array $versions): array
    {
        $versions = self::sortAsc($versions);

        $betweenVersions = [];
        if ($initialVersion->getStamp() == $finalVersion->getStamp()) {
            return $betweenVersions; // nothing to do
        }

        if ($initialVersion->getStamp() < $finalVersion->getStamp()) {
            $versions = self::sortAsc($versions);
        } else {
            $versions = self::sortDesc($versions);
            list($initialVersion, $finalVersion) = [$finalVersion, $initialVersion];
        }

        foreach ($versions as $version) {
            if (
                $version->getStamp() >= $initialVersion->getStamp() &&
                $version->getStamp() <= $finalVersion->getStamp()
            ) {
                $betweenVersions[] = $version;
            }
        }

        return $betweenVersions;
    }

    /**
     * Sort items in the ascending order
     *
     * @param ItemInterface[] $versions
     *
     * @return ItemInterface[]
     */
    public static function sortAsc(array $versions): array
    {
        $sortData = [];
        foreach ($versions as $version) {
            $sortData[$version->getStamp()] = $version;
        }
        ksort($sortData);

        return array_values($sortData);
    }
}
