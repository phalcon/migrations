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
     *
     * @return integer
     */
    public function getStamp(): int;

    /**
     * Get the string representation of the version
     *
     * @return string
     */
    public function getVersion(): string;

    /**
     * Get the string representation of the version
     *
     * @return string
     */
    public function __toString(): string;

    /**
     * Set migrations directory of incremental item
     *
     * @param string $path
     */
    public function setPath(string $path): void;

    /**
     * Get migrations directory of incremental item
     *
     * @return string
     */
    public function getPath(): string;
}
