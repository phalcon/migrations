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

/**
 * Common interface to manipulate version items.
 */
interface ItemInterface
{
    /**
     * Get integer payload of the version
     */
    public function getStamp(): int;

    /**
     * Get the string representation of the version
     */
    public function getVersion(): string;

    /**
     * Get the string representation of the version
     */
    public function __toString(): string;

    /**
     * Set migrations directory of incremental item
     */
    public function setPath(string $path): void;

    /**
     * Get migrations directory of incremental item
     */
    public function getPath(): string;
}
