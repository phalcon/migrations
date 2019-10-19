<?php
declare(strict_types=1);

/**
 * This file is part of the Phalcon Migrations.
 *
 * (c) Phalcon Team <team@phalcon.io>
 *
 * For the full copyright and license information, please view
 * the LICENSE file that was distributed with this source code.
 */

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
    public function getStamp();

    /**
     * Get the string representation of the version
     *
     * @return string
     */
    public function getVersion();

    /**
     * Get the string representation of the version
     *
     * @return string
     */
    public function __toString();

    /**
     * Set migrations directory of incremental item
     *
     * @param string $path
     */
    public function setPath($path);

    /**
     * Get migrations directory of incremental item
     *
     * @return string
     */
    public function getPath();
}
