<?php

namespace DevTheorem\Handlebars;

/**
 * @internal
 */
final class Encoder
{
    /**
     * Get the HTML encoded value of the specified variable.
     *
     * @param array<string,array|string|int> $cx render time context
     * @param array<array|string|int>|string|int|bool|null $var value to be htmlencoded
     */
    public static function enc(array $cx, array|string|int|bool|null $var): string
    {
        return htmlspecialchars(Runtime::raw($cx, $var), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Runtime method for {{var}}, and deal with single quote the same as Handlebars.js.
     *
     * @param array<string,array|string|int> $cx render time context
     * @param array<array|string|int>|string|int|bool|null $var value to be htmlencoded
     *
     * @return string The htmlencoded value of the specified variable
     */
    public static function encq(array $cx, array|string|int|bool|null $var)
    {
        return str_replace(array('=', '`', '&#039;'), array('&#x3D;', '&#x60;', '&#x27;'), self::enc($cx, $var));
    }
}
