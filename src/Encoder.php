<?php

namespace LightnCandy;

class Encoder
{
    /**
     * Get string value
     *
     * @param array<string,array|string|integer> $cx render time context
     * @param array<array|string|integer>|string|integer|null $v value to be output
     * @param integer $ex 1 to return untouched value, default is 0
     *
     * @return array<array|string|integer>|string|integer|null The raw value of the specified variable
     *
     * @expect 'true' when input array('flags' => array()), true
     * @expect 'false' when input array('flags' => array()), false
     * @expect false when input array('flags' => array()), false, true
     * @expect 'a,b' when input array('flags' => array()), array('a', 'b')
     * @expect '[object Object]' when input array('flags' => array()), array('a', 'c' => 'b')
     * @expect '[object Object]' when input array('flags' => array()), array('c' => 'b')
     * @expect 'a,true' when input array('flags' => array()), array('a', true)
     * @expect 'a,false' when input array('flags' => array()), array('a',false)
     */
    public static function raw($cx, $v, $ex = 0)
    {
        if ($ex) {
            return $v;
        }

        if ($v === true) {
            return 'true';
        }

        if (($v === false)) {
            return 'false';
        }

        if (is_array($v)) {
            if (count(array_diff_key($v, array_keys(array_keys($v)))) > 0) {
                return '[object Object]';
            } else {
                $ret = array();
                foreach ($v as $vv) {
                    $ret[] = static::raw($cx, $vv);
                }
                return join(',', $ret);
            }
        }

        return "$v";
    }

    /**
     * Get html encoded string
     *
     * @param array<string,array|string|integer> $cx render time context
     * @param array<array|string|integer>|string|integer|null $var value to be htmlencoded
     *
     * @return string The htmlencoded value of the specified variable
     *
     * @expect 'a' when input array('flags' => array()), 'a'
     * @expect 'a&amp;b' when input array('flags' => array()), 'a&b'
     * @expect 'a&#039;b' when input array('flags' => array()), 'a\'b'
     */
    public static function enc($cx, $var)
    {
        return htmlspecialchars(static::raw($cx, $var), ENT_QUOTES, 'UTF-8');
    }

    /**
     * LightnCandy runtime method for {{var}} , and deal with single quote to same as handlebars.js .
     *
     * @param array<string,array|string|integer> $cx render time context
     * @param array<array|string|integer>|string|integer|null $var value to be htmlencoded
     *
     * @return string The htmlencoded value of the specified variable
     *
     * @expect 'a' when input array('flags' => array()), 'a'
     * @expect 'a&amp;b' when input array('flags' => array()), 'a&b'
     * @expect 'a&#x27;b' when input array('flags' => array()), 'a\'b'
     * @expect '&#x60;a&#x27;b' when input array('flags' => array()), '`a\'b'
     */
    public static function encq($cx, $var)
    {
        return str_replace(array('=', '`', '&#039;'), array('&#x3D;', '&#x60;', '&#x27;'), htmlspecialchars(static::raw($cx, $var), ENT_QUOTES, 'UTF-8'));
    }
}
