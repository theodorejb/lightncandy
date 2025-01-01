<?php

namespace LightnCandy;

class Encoder
{
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
    public static function enc(array $cx, $var): string
    {
        return htmlspecialchars(Runtime::raw($cx, $var), ENT_QUOTES, 'UTF-8');
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
    public static function encq(array $cx, $var)
    {
        return str_replace(array('=', '`', '&#039;'), array('&#x3D;', '&#x60;', '&#x27;'), htmlspecialchars(Runtime::raw($cx, $var), ENT_QUOTES, 'UTF-8'));
    }
}
