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
 * @property string $version
 */
trait VersionAwareTrait
{
    /**
     * Get the string representation of the version
     *
     * @return string
     */
    public function getVersion(): string
    {
        return $this->version;
    }

    /**
     * Get the string representation of the version
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getVersion();
    }
}
