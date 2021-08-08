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

class Snippet
{
    public function getMorphTemplate(): string
    {
        return "\$this->morphTable('%s', [\n%s]);";
    }

    public function getColumnTemplate(): string
    {
        return "new Column(
            '%s',
            [
                %s
            ]
        )";
    }

    public function getIndexTemplate(): string
    {
        return "new Index('%s', [%s], %s)";
    }

    public function getReferenceTemplate(): string
    {
        return "new Reference(
            '%s',
            [
                %s
            ]
        )";
    }

    public function getOptionTemplate(): string
    {
        return "%s";
    }

    public function definitionToString(string $key, array $items): string
    {
        if (empty($items)) {
            return '';
        }

        $template = "    '%s' => [
        %s,
    ],\n";

        return sprintf($template, $key, implode(",\n        ", $items));
    }
}
