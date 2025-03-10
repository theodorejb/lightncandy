<?php

namespace LightnCandy;

/**
 * @internal
 */
final class Expression
{
    /**
     * Returns 'true' when the value is greater than 0, otherwise 'false'.
     */
    public static function boolString(int $value): string
    {
        return $value > 0 ? 'true' : 'false';
    }

    /**
     * Get string presentation for a string list
     *
     * @param array<string> $list an array of strings.
     */
    public static function listString(array $list): string
    {
        return implode(',', (array_map(function ($v) {
            return "'$v'";
        }, $list)));
    }

    /**
     * Get string presentation for an array
     *
     * @param array<string> $list an array of variable names.
     */
    public static function arrayString(array $list): string
    {
        return implode('', (array_map(function ($v) {
            return "['$v']";
        }, $list)));
    }

    /**
     * Analyze an expression
     *
     * @param array<string,array|string|int> $context Current context
     * @param array<array|string|int> $var variable parsed path
     *
     * @return array{int, bool, array} analyzed result
     */
    public static function analyze(array $context, array $var): array
    {
        $levels = 0;
        $spvar = false;

        if (isset($var[0])) {
            // trace to parent
            if (is_int($var[0])) {
                $levels = array_shift($var);
            }
        }

        if (isset($var[0])) {
            // handle @root, @index, @key, @last, etc
            if (str_starts_with($var[0], '@')) {
                $spvar = true;
                $var[0] = substr($var[0], 1);
            }
        }

        return array($levels, $spvar, $var);
    }

    /**
     * Get normalized handlebars expression for a variable.
     *
     * @param int $levels trace N levels top parent scope
     * @param bool $spvar is the path start with @ or not
     * @param array<string|int> $var variable parsed path
     *
     * @return string normalized expression for debug display
     */
    public static function toString(int $levels, bool $spvar, array $var): string
    {
        return ($spvar ? '@' : '') . str_repeat('../', $levels) . (count($var) ? implode('.', array_map(function ($v) {
            return ($v === null) ? 'this' : "[$v]";
        }, $var)) : 'this');
    }
}
