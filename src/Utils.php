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

namespace Phalcon\Migrations;

use InvalidArgumentException;
use Phalcon\Config;

class Utils
{
    public const DB_ADAPTER_MYSQL = 'mysql';

    public const DB_ADAPTER_POSTGRESQL = 'postgresql';

    public const DB_ADAPTER_SQLITE = 'sqlite';

    /**
     * Converts the underscore_notation to the UpperCamelCase
     *
     * @param string $string
     * @param string $delimiter
     * @return string
     */
    public static function camelize(string $string, string $delimiter = '_'): string
    {
        if (empty($delimiter)) {
            throw new InvalidArgumentException('Please, specify the delimiter');
        }

        $delimiterArray = str_split($delimiter);
        foreach ($delimiterArray as $delimiter) {
            $stringParts = explode($delimiter, $string);
            $stringParts = array_map('ucfirst', $stringParts);

            $string = implode('', $stringParts);
        }

        return $string;
    }

    /**
     * Convert string foo_bar to FooBar or fooBar
     *
     * <code>
     * echo Phalcon\Utils::lowerCamelizeWithDelimiter('coco_bongo'); // coco_bongo
     * echo Phalcon\Utils::lowerCamelizeWithDelimiter('coco_bongo', '_'); // CocoBongo
     * echo Phalcon\Utils::lowerCamelizeWithDelimiter('coco_bongo', '_', true); // cocoBongo
     * </code>
     *
     * @param string $string
     * @param string $delimiter
     * @param boolean $useLow
     * @return string
     */
    public static function lowerCamelizeWithDelimiter(
        string $string,
        string $delimiter = '',
        bool $useLow = false
    ): string {
        if (empty($string)) {
            throw new InvalidArgumentException('Please, specify the string');
        }

        if (!empty($delimiter)) {
            $delimiterArray = str_split($delimiter);

            foreach ($delimiterArray as $delimiter) {
                $stringParts = explode($delimiter, $string);
                $stringParts = array_map('ucfirst', $stringParts);

                $string = implode('', $stringParts);
            }
        }

        if ($useLow) {
            $string = lcfirst($string);
        }

        return $string;
    }

    /**
     * Converts the underscore_notation to the lowerCamelCase
     *
     * @param string $string
     * @return string
     */
    public static function lowerCamelize(string $string): string
    {
        return lcfirst(self::camelize($string));
    }

    /**
     * Resolves the DB Schema
     *
     * @param Config $config
     * @return null|string
     */
    public static function resolveDbSchema(Config $config): ?string
    {
        if ($config->offsetExists('schema')) {
            return $config->get('schema');
        }

        $adapter = strtolower($config->get('adapter'));
        if (self::DB_ADAPTER_POSTGRESQL == $adapter) {
            return 'public';
        }

        if (self::DB_ADAPTER_SQLITE == $adapter) {
            // SQLite only supports the current database, unless one is
            // attached. This is not the case, so don't return a schema.
            return null;
        }

        if ($config->offsetExists('dbname')) {
            return $config->get('dbname');
        }

        return null;
    }
}
