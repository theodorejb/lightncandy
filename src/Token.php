<?php

namespace LightnCandy;

class Token
{
    // RegExps
    const VARNAME_SEARCH = '/(\\[[^\\]]+\\]|[^\\[\\]\\.]+)/';

    // Positions of matched token
    const POS_LOTHER = 1;
    const POS_LSPACE = 2;
    const POS_BEGINTAG = 3;
    const POS_LSPACECTL = 4;
    const POS_BEGINRAW = 5;
    const POS_OP = 6;
    const POS_INNERTAG = 7;
    const POS_ENDRAW = 8;
    const POS_RSPACECTL = 9;
    const POS_ENDTAG = 10;
    const POS_RSPACE = 11;
    const POS_ROTHER = 12;
    const POS_BACKFILL = 13;

    /**
     * Setup delimiter by default or provided string
     *
     * @param array<string,array|string|integer> $context Current context
     */
    public static function setDelimiter(array &$context): void
    {
        $left = '{{';
        $right = '}}';
        $context['tokens']['startchar'] = substr($left, 0, 1);
        $context['tokens']['left'] = $left;
        $context['tokens']['right'] = $right;
        $rawcount = $context['rawblock'] ? '{2}' : '{0,2}';
        $left = preg_quote($left);
        $right = preg_quote($right);

        $context['tokens']['search'] = "/^(.*?)(\\s*)($left)(~?)(\\{{$rawcount})\\s*([\\^#\\/!&>\\*]{0,2})(.*?)\\s*(\\}{$rawcount})(~?)($right)(\\s*)(.*)\$/s";
    }

    /**
     * return token string
     *
     * @param string[] $token detected handlebars {{ }} token
     * @param string[]|null $merge list of token strings to be merged
     *
     * @return string Return whole token
     *
     * @expect 'c' when input array(0, 'a', 'b', 'c', 'd', 'e')
     * @expect 'cd' when input array(0, 'a', 'b', 'c', 'd', 'e', 'f')
     * @expect 'qd' when input array(0, 'a', 'b', 'c', 'd', 'e', 'f'), array(3 => 'q')
     */
    public static function toString(array $token, ?array $merge = null): string
    {
        if (is_array($merge)) {
            $token = array_replace($token, $merge);
        }
        return implode('', array_slice($token, 3, -2));
    }
}
