<?php

namespace LightnCandy;

class Expression
{
    /**
     * return 'true' or 'false' string.
     *
     * @param integer $v value
     *
     * @return string 'true' when the value larger then 0
     *
     * @expect 'true' when input 1
     * @expect 'true' when input 999
     * @expect 'false' when input 0
     * @expect 'false' when input -1
     */
    public static function boolString(int $v): string
    {
        return ($v > 0) ? 'true' : 'false';
    }

    /**
     * Get string presentation for a string list
     *
     * @param array<string> $list an array of strings.
     *
     * @return string PHP list string
     *
     * @expect '' when input array()
     * @expect "'a'" when input array('a')
     * @expect "'a','b','c'" when input array('a', 'b', 'c')
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
     *
     * @return string PHP array names string
     *
     * @expect '' when input array()
     * @expect "['a']" when input array('a')
     * @expect "['a']['b']['c']" when input array('a', 'b', 'c')
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
     * @param array<string,array|string|integer> $context Current context
     * @param array<array|string|integer> $var variable parsed path
     *
     * @return array<integer|boolean|array> analyzed result
     *
     * @expect array(0, false, array('foo')) when input array('flags' => array()), array(0, 'foo')
     * @expect array(1, false, array('foo')) when input array('flags' => array()), array(1, 'foo')
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
     * get normalized handlebars expression for a variable
     *
     * @param integer $levels trace N levels top parent scope
     * @param boolean $spvar is the path start with @ or not
     * @param array<string|integer> $var variable parsed path
     *
     * @return string normalized expression for debug display
     *
     * @expect '[a].[b]' when input 0, false, array('a', 'b')
     * @expect '@[root]' when input 0, true, array('root')
     * @expect 'this.[id]' when input 0, false, array(null, 'id')
     * @expect '@[root].[a].[b]' when input 0, true, array('root', 'a', 'b')
     * @expect '../../[a].[b]' when input 2, false, array('a', 'b')
     * @expect '../[a\'b]' when input 1, false, array('a\'b')
     */
    public static function toString(int $levels, bool $spvar, array $var): string
    {
        return ($spvar ? '@' : '') . str_repeat('../', $levels) . (count($var) ? implode('.', array_map(function ($v) {
            return ($v === null) ? 'this' : "[$v]";
        }, $var)) : 'this');
    }
}
