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

use InvalidArgumentException;

/**
 * The version prefixed by timestamp value
 */
class TimestampedItem implements ItemInterface
{
    use VersionAwareTrait;

    /**
     * @var string
     */
    protected $version;
    
    /**
     * @var boolean
     */
    protected $isFullVersion;
    
    /**
     * @var array
     */
    protected $parts = [];
    
    /**
     * @var string
     */
    private $path;

    /**
     * @param string $version String representation of the version
     *
     * @throws InvalidArgumentException
     */
    public function __construct(string $version)
    {
        if ((1 !== preg_match('#^[\d]{7,}(?:\_[a-z0-9]+)*$#', $version)) && $version != '000') {
            throw new InvalidArgumentException('Wrong version number provided');
        }

        $this->version = $version;
        $this->parts = explode('_', $version);
        $this->isFullVersion = isset($this->parts[1]);
    }

    /**
     * Get integer payload of the version
     *
     * @return int
     */
    public function getStamp(): int
    {
        return (int)$this->parts[0];
    }

    /**
     * Get version description
     *
     * @return string
     */
    public function getDescription(): string
    {
        return $this->isFullVersion() ? $this->parts[1] : '';
    }

    /**
     * Full version has both parts: number and description
     *
     * @return bool
     */
    public function isFullVersion(): bool
    {
        return !!$this->isFullVersion;
    }

    /**
     * Get migrations directory of incremental item
     *
     * @return string
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Set migrations directory of incremental item
     *
     * @param string $path
     */
    public function setPath(string $path): void
    {
        $this->path = $path;
    }
}
