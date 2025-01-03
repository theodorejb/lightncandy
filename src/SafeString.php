<?php

namespace LightnCandy;

class SafeString
{
    const EXTENDED_COMMENT_SEARCH = '/{{!--.*?--}}/s';
    const IS_SUBEXP_SEARCH = '/^\(.+\)$/s';
    const IS_BLOCKPARAM_SEARCH = '/^ +\|(.+)\|$/s';

    private string $string;

    /**
     * @param string $str input string
     * @param bool|string $escape false to not escape, true to escape, 'encq' to escape as handlebars.js
     */
    public function __construct(string $str, bool|string $escape = false)
    {
        $this->string = $escape ? (($escape === 'encq') ? Encoder::encq([], $str) : Encoder::enc([], $str)) : $str;
    }

    public function __toString()
    {
        return $this->string;
    }

    /**
     * Strip extended comments {{!-- .... --}}
     *
     * @expect 'abc' when input 'abc'
     * @expect 'abc{{!}}cde' when input 'abc{{!}}cde'
     * @expect 'abc{{! }}cde' when input 'abc{{!----}}cde'
     */
    public static function stripExtendedComments(string $template): string
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
    public static function escapeTemplate(string $template): string
    {
        return addcslashes(addcslashes($template, '\\'), "'");
    }
}
