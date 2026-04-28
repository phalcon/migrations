<?php

declare(strict_types=1);

namespace Phalcon\Migrations\Utils;

use function array_map;
use function implode;
use function lcfirst;
use function preg_split;
use function str_replace;
use function ucfirst;

use const PREG_SPLIT_DELIM_CAPTURE;
use const PREG_SPLIT_NO_EMPTY;

class Camelize
{
    public function __invoke(
        string $text,
        string|null $delimiters = null,
        bool $lowerFirst = false
    ): string {
        $delimiters = $delimiters ?: '\-_';
        $delimiters = str_replace(['\-', '-'], ['-', '\-'], $delimiters);

        $result = preg_split(
            '/[' . $delimiters . ']+/',
            $text,
            -1,
            PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY
        );

        $parts = (false === $result) ? [] : $result;

        $output = array_map(
            static function ($element) {
                return ucfirst(mb_strtolower($element));
            },
            $parts
        );

        $output = implode('', $output);

        if (true === $lowerFirst) {
            $output = lcfirst($output);
        }

        return $output;
    }
}