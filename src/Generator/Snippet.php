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

namespace Phalcon\Migrations\Generator;

use function implode;
use function sprintf;

class Snippet
{
    /**
     * With $keepEmpty, an empty list gives an empty array for the key, not
     * an empty string.
     */
    public function definitionToString(string $key, array $items, bool $keepEmpty = false): string
    {
        if (empty($items)) {
            return $keepEmpty ? sprintf("    '%s' => [],\n", $key) : '';
        }

        $template = "    '%s' => [
        %s,
    ],\n";

        return sprintf($template, $key, implode(",\n        ", $items));
    }

    public function getColumnTemplate(): string
    {
        return "new Column(
            %s,
            [
                %s
            ]
        )";
    }

    public function getIndexTemplate(): string
    {
        return "new Index(%s, [%s], %s)";
    }
    public function getMorphTemplate(): string
    {
        return "\$this->morphTable(%s, [\n%s]);";
    }

    public function getOptionTemplate(): string
    {
        return "%s";
    }

    public function getReferenceTemplate(): string
    {
        return "new Reference(
            %s,
            [
                %s
            ]
        )";
    }
}
