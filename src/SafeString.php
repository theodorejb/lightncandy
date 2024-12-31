<?php

namespace LightnCandy;

class SafeString extends Encoder
{
    const EXTENDED_COMMENT_SEARCH = '/{{!--.*?--}}/s';
    const IS_SUBEXP_SEARCH = '/^\(.+\)$/s';
    const IS_BLOCKPARAM_SEARCH = '/^ +\|(.+)\|$/s';

    private $string;

    public static $jsContext = array(
        'flags' => array(
        )
    );

    /**
     * Constructor
     *
     * @param string $str input string
     * @param bool|string $escape false to not escape, true to escape, 'encq' to escape as handlebars.js
     */
    public function __construct($str, $escape = false)
    {
        $this->string = $escape ? (($escape === 'encq') ? static::encq(static::$jsContext, $str) : static::enc(static::$jsContext, $str)) : $str;
    }

    public function __toString()
    {
        return $this->string;
    }

    /**
     * Strip extended comments {{!-- .... --}}
     *
     * @param string $template handlebars template string
     *
     * @return string Stripped template
     *
     * @expect 'abc' when input 'abc'
     * @expect 'abc{{!}}cde' when input 'abc{{!}}cde'
     * @expect 'abc{{! }}cde' when input 'abc{{!----}}cde'
     */
    public static function stripExtendedComments($template)
    {
        return preg_replace(static::EXTENDED_COMMENT_SEARCH, '{{! }}', $template);
    }

    /**
     * Escape template
     *
     * @param string $template handlebars template string
     *
     * @return string Escaped template
     *
     * @expect 'abc' when input 'abc'
     * @expect 'a\\\\bc' when input 'a\bc'
     * @expect 'a\\\'bc' when input 'a\'bc'
     */
    public static function escapeTemplate($template)
    {
        return addcslashes(addcslashes($template, '\\'), "'");
    }
}
