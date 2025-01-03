<?php

namespace LightnCandy;

class Validator
{
    /**
     * Verify template
     *
     * @param array<string,array|string|integer> $context Current context
     * @param string $template handlebars template
     */
    public static function verify(array &$context, string $template): void
    {
        $template = SafeString::stripExtendedComments($template);
        $context['level'] = 0;
        Token::setDelimiter($context);

        while (preg_match($context['tokens']['search'], $template, $matches)) {
            // Skip a token when it is slash escaped
            if (($matches[Token::POS_LSPACE] === '') && preg_match('/^(.*?)(\\\\+)$/s', $matches[Token::POS_LOTHER], $escmatch)) {
                if (strlen($escmatch[2]) % 4) {
                    static::pushToken($context, substr($matches[Token::POS_LOTHER], 0, -2) . $context['tokens']['startchar']);
                    $matches[Token::POS_BEGINTAG] = substr($matches[Token::POS_BEGINTAG], 1);
                    $template = implode('', array_slice($matches, Token::POS_BEGINTAG));
                    continue;
                } else {
                    $matches[Token::POS_LOTHER] = $escmatch[1] . str_repeat('\\', strlen($escmatch[2]) / 2);
                }
            }
            $context['tokens']['count']++;
            $V = static::token($matches, $context);
            static::pushLeft($context);
            if ($V) {
                if (is_array($V)) {
                    array_push($V, $matches, $context['tokens']['partialind']);
                }
                static::pushToken($context, $V);
            }
            $template = "{$matches[Token::POS_RSPACE]}{$matches[Token::POS_ROTHER]}";
        }
        static::pushToken($context, $template);

        if ($context['level'] > 0) {
            array_pop($context['stack']);
            array_pop($context['stack']);
            $token = array_pop($context['stack']);
            $context['error'][] = 'Unclosed token ' . ($context['rawblock'] ? "{{{{{$token}}}}}" : ($context['partialblock'] ? "{{#>{$token}}}" : "{{#{$token}}}")) . ' !!';
        }
    }

    /**
     * push left string of current token and clear it
     *
     * @param array<string,array|string|integer> $context Current context
     */
    protected static function pushLeft(array &$context): void
    {
        $L = $context['currentToken'][Token::POS_LOTHER] . $context['currentToken'][Token::POS_LSPACE];
        static::pushToken($context, $L);
        $context['currentToken'][Token::POS_LOTHER] = $context['currentToken'][Token::POS_LSPACE] = '';
    }

    /**
     * push a string into the partial stacks
     *
     * @param array<string,array|string|integer> $context Current context
     * @param string $append a string to be appended int partial stacks
     */
    protected static function pushPartial(array &$context, string $append): void
    {
        $appender = function (&$p) use ($append) {
            $p .= $append;
        };
        array_walk($context['inlinepartial'], $appender);
        array_walk($context['partialblock'], $appender);
    }

    /**
     * push a token into the stack when it is not empty string
     *
     * @param array<string,array|string|integer> $context Current context
     * @param array|string $token a parsed token or a string
     */
    protected static function pushToken(array &$context, array|string $token)
    {
        if ($token === '') {
            return;
        }
        if (is_string($token)) {
            static::pushPartial($context, $token);
            if (is_string(end($context['parsed'][0]))) {
                $context['parsed'][0][key($context['parsed'][0])] .= $token;
                return;
            }
        } else {
            static::pushPartial($context, Token::toString($context['currentToken']));
            switch ($context['currentToken'][Token::POS_OP]) {
            case '#*':
                array_unshift($context['inlinepartial'], '');
                break;
            case '#>':
                array_unshift($context['partialblock'], '');
                break;
            }
        }
        $context['parsed'][0][] = $token;
    }

    /**
     * push current token into the section stack
     *
     * @param array<string,array|string|integer> $context Current context
     * @param string $operation operation string
     * @param array<boolean|integer|string|array> $vars parsed arguments list
     */
    protected static function pushStack(array &$context, string $operation, array $vars): void
    {
        [$levels, $spvar, $var] = Expression::analyze($context, $vars[0]);
        $context['stack'][] = $context['currentToken'][Token::POS_INNERTAG];
        $context['stack'][] = Expression::toString($levels, $spvar, $var);
        $context['stack'][] = $operation;
        $context['level']++;
    }

    /**
     * Verify delimiters and operators
     *
     * @param string[] $token detected handlebars {{ }} token
     * @param array<string,array|string|integer> $context current compile context
     *
     * @return boolean Return true when invalid
     *
     * @expect false when input array_fill(0, 11, ''), array()
     * @expect false when input array(0, 0, 0, 0, 0, '{{', '#', '...', '}}'), array()
     * @expect true when input array(0, 0, 0, 0, 0, '{', '#', '...', '}'), array()
     */
    protected static function delimiter(array $token, array &$context): bool
    {
        // {{ }}} or {{{ }} are invalid
        if (strlen($token[Token::POS_BEGINRAW]) !== strlen($token[Token::POS_ENDRAW])) {
            $context['error'][] = 'Bad token ' . Token::toString($token) . ' ! Do you mean ' . Token::toString($token, array(Token::POS_BEGINRAW => '', Token::POS_ENDRAW => '')) . ' or ' . Token::toString($token, array(Token::POS_BEGINRAW => '{', Token::POS_ENDRAW => '}')) . '?';
            return true;
        }
        // {{{# }}} or {{{! }}} or {{{/ }}} or {{{^ }}} are invalid.
        if ((strlen($token[Token::POS_BEGINRAW]) == 1) && $token[Token::POS_OP] && ($token[Token::POS_OP] !== '&')) {
            $context['error'][] = 'Bad token ' . Token::toString($token) . ' ! Do you mean ' . Token::toString($token, array(Token::POS_BEGINRAW => '', Token::POS_ENDRAW => '')) . ' ?';
            return true;
        }

        return false;
    }

    /**
     * Verify operators
     *
     * @param string $operator the operator string
     * @param array<string,array|string|integer> $context current compile context
     * @param array<boolean|integer|string|array> $vars parsed arguments list
     *
     * @expect null when input '', array(), array()
     * @expect 2 when input '^', array('usedFeature' => array('isec' => 1), 'level' => 0, 'currentToken' => array(0,0,0,0,0,0,0,0), 'elselvl' => array(), 'flags' => array(), 'elsechain' => false), array(array('foo'))
     * @expect true when input '/', array('stack' => array('[with]', '#'), 'level' => 1, 'currentToken' => array(0,0,0,0,0,0,0,'with'), 'flags' => array()), array(array())
     * @expect 4 when input '#', array('usedFeature' => array('sec' => 3), 'level' => 0, 'currentToken' => array(0,0,0,0,0,0,0,0), 'flags' => array(), 'elsechain' => false, 'elselvl' => array()), array(array('x'))
     * @expect 5 when input '#', array('usedFeature' => array('if' => 4), 'level' => 0, 'currentToken' => array(0,0,0,0,0,0,0,0), 'flags' => array(), 'elsechain' => false, 'elselvl' => array()), array(array('if'))
     * @expect 6 when input '#', array('usedFeature' => array('with' => 5), 'level' => 0, 'flags' => array(), 'currentToken' => array(0,0,0,0,0,0,0,0), 'elsechain' => false, 'elselvl' => array()), array(array('with'))
     * @expect 7 when input '#', array('usedFeature' => array('each' => 6), 'level' => 0, 'currentToken' => array(0,0,0,0,0,0,0,0), 'flags' => array(), 'elsechain' => false, 'elselvl' => array()), array(array('each'))
     * @expect 8 when input '#', array('usedFeature' => array('unless' => 7), 'level' => 0, 'currentToken' => array(0,0,0,0,0,0,0,0), 'flags' => array(), 'elsechain' => false, 'elselvl' => array()), array(array('unless'))
     * @expect 9 when input '#', array('helpers' => array('abc' => ''), 'usedFeature' => array('helper' => 8), 'level' => 0, 'currentToken' => array(0,0,0,0,0,0,0,0), 'flags' => array(), 'elsechain' => false, 'elselvl' => array()), array(array('abc'))
     * @expect 11 when input '#', array('helpers' => array('abc' => ''), 'usedFeature' => array('helper' => 10), 'level' => 0, 'currentToken' => array(0,0,0,0,0,0,0,0), 'flags' => array(), 'elsechain' => false, 'elselvl' => array()), array(array('abc'))
     * @expect true when input '>', array('usedFeature' => array('partial' => 7), 'level' => 0, 'flags' => array(), 'currentToken' => array(0,0,0,0,0,0,0,0), 'elsechain' => false, 'elselvl' => array()), array('test')
     */
    protected static function operator(string $operator, array &$context, array &$vars): bool|int|string|null
    {
        switch ($operator) {
            case '#*':
                if (!$context['compile']) {
                    $context['stack'][] = count($context['parsed'][0]) + ($context['currentToken'][Token::POS_LOTHER] . $context['currentToken'][Token::POS_LSPACE] === '' ? 0 : 1);
                    static::pushStack($context, '#*', $vars);
                }
                return static::inline($context, $vars);

            case '#>':
                if (!$context['compile']) {
                    $context['stack'][] = count($context['parsed'][0]) + ($context['currentToken'][Token::POS_LOTHER] . $context['currentToken'][Token::POS_LSPACE] === '' ? 0 : 1);
                    $vars[Parser::PARTIALBLOCK] = ++$context['usedFeature']['pblock'];
                    static::pushStack($context, '#>', $vars);
                }
                // no break
            case '>':
                return static::partial($context, $vars);

            case '^':
                if (!isset($vars[0][0])) {
                    return static::doElse($context, $vars);
                }

                static::doElseChain($context);

                if (static::isBlockHelper($context, $vars)) {
                    static::pushStack($context, '#', $vars);
                    return static::blockCustomHelper($context, $vars, true);
                }

                static::pushStack($context, '^', $vars);
                return static::invertedSection($context, $vars);

            case '/':
                $r = static::blockEnd($context, $vars);
                if ($r !== Token::POS_BACKFILL) {
                    array_pop($context['stack']);
                    array_pop($context['stack']);
                    array_pop($context['stack']);
                }
                return $r;

            case '#':
                static::doElseChain($context);
                static::pushStack($context, '#', $vars);

                if (static::isBlockHelper($context, $vars)) {
                    return static::blockCustomHelper($context, $vars);
                }

                return static::blockBegin($context, $vars);
        }

        return null;
    }

    /**
     * validate inline partial begin token
     *
     * @param array<string,array|string|integer> $context current compile context
     * @param array<boolean|integer|string|array> $vars parsed arguments list
     *
     * @return boolean Return true when inline partial ends
     */
    protected static function inlinePartial(array &$context, array $vars): bool
    {
        $ended = false;
        if ($context['currentToken'][Token::POS_OP] === '/') {
            if (static::blockEnd($context, $vars, '#*')) {
                $context['usedFeature']['inlpartial']++;
                $tmpl = array_shift($context['inlinepartial']) . $context['currentToken'][Token::POS_LOTHER] . $context['currentToken'][Token::POS_LSPACE];
                $c = $context['stack'][count($context['stack']) - 4];
                $context['parsed'][0] = array_slice($context['parsed'][0], 0, $c + 1);
                $P = &$context['parsed'][0][$c];
                if (isset($P[1][1][0])) {
                    $context['usedPartial'][$P[1][1][0]] = $tmpl;
                    $P[1][0][0] = Partial::compileDynamic($context, $P[1][1][0]);
                }
                $ended = true;
            }
        }
        return $ended;
    }

    /**
     * validate partial block token
     *
     * @param array<string,array|string|integer> $context current compile context
     * @param array<boolean|integer|string|array> $vars parsed arguments list
     *
     * @return boolean Return true when partial block ends
     */
    protected static function partialBlock(array &$context, array $vars): bool
    {
        $ended = false;
        if ($context['currentToken'][Token::POS_OP] === '/') {
            if (static::blockEnd($context, $vars, '#>')) {
                $c = $context['stack'][count($context['stack']) - 4];
                $context['parsed'][0] = array_slice($context['parsed'][0], 0, $c + 1);
                $found = Partial::resolve($context, $vars[0][0]) !== null;
                $v = $found ? "@partial-block{$context['parsed'][0][$c][1][Parser::PARTIALBLOCK]}" : "{$vars[0][0]}";
                if (count($context['partialblock']) == 1) {
                    $tmpl = $context['partialblock'][0] . $context['currentToken'][Token::POS_LOTHER] . $context['currentToken'][Token::POS_LSPACE];
                    if ($found) {
                        $context['partials'][$v] = $tmpl;
                    }
                    $context['usedPartial'][$v] = $tmpl;
                    Partial::compileDynamic($context, $v);
                    if ($found) {
                        Partial::read($context, $vars[0][0]);
                    }
                }
                array_shift($context['partialblock']);
                $ended = true;
            }
        }
        return $ended;
    }

    /**
     * handle else chain
     *
     * @param array<string,array|string|integer> $context current compile context
     */
    protected static function doElseChain(array &$context): void
    {
        if ($context['elsechain']) {
            $context['elsechain'] = false;
        } else {
            array_unshift($context['elselvl'], array());
        }
    }

    /**
     * validate block begin token
     *
     * @param array<string,array|string|integer> $context current compile context
     * @param array<boolean|integer|string|array> $vars parsed arguments list
     *
     * @return boolean Return true always
     */
    protected static function blockBegin(array &$context, array $vars)
    {
        switch ((isset($vars[0][0]) && is_string($vars[0][0])) ? $vars[0][0] : null) {
            case 'with':
                return static::with($context, $vars);
            case 'each':
                return static::section($context, $vars, true);
            case 'unless':
            case 'if':
                return static::requireOneArgument($context, $vars);
            default:
                return static::section($context, $vars);
        }
    }

    /**
     * validate builtin helpers
     *
     * @param array<string,array|string|integer> $context current compile context
     * @param array<boolean|integer|string|array> $vars parsed arguments list
     */
    protected static function builtin(array &$context, array $vars): void
    {
        if (count($vars) < 2) {
            $context['error'][] = "No argument after {{#{$vars[0][0]}}} !";
        }
        $context['usedFeature'][$vars[0][0]]++;
    }

    /**
     * validate section token
     *
     * @param array<string,array|string|integer> $context current compile context
     * @param array<boolean|integer|string|array> $vars parsed arguments list
     * @param boolean $isEach the section is #each
     *
     * @return boolean Return true always
     */
    protected static function section(array &$context, array $vars, bool $isEach = false)
    {
        if ($isEach) {
            static::builtin($context, $vars);
        } else {
            if (count($vars) > 1) {
                $context['error'][] = "Custom helper not found: {$vars[0][0]} in " . Token::toString($context['currentToken']) . ' !';
            }
            $context['usedFeature']['sec']++;
        }
        return true;
    }

    /**
     * validate with token
     *
     * @param array<string,array|string|integer> $context current compile context
     * @param array<boolean|integer|string|array> $vars parsed arguments list
     *
     * @return boolean Return true always
     */
    protected static function with(array &$context, array $vars)
    {
        if (isset($vars[Parser::BLOCKPARAM])) {
            unset($vars[Parser::BLOCKPARAM]);
        }
        if (count($vars) !== 2) {
            $context['error'][] = "#{$vars[0][0]} requires exactly one argument";
        }
        $context['usedFeature'][$vars[0][0]]++;
        return true;
    }

    /**
     * validate if, unless, or with token
     *
     * @param array<string,array|string|integer> $context current compile context
     * @param array<boolean|integer|string|array> $vars parsed arguments list
     */
    protected static function requireOneArgument(array &$context, array $vars): true
    {
        // don't count non-int keys (hash arguments)
        $intKeys = 0;
        foreach ($vars as $key => $var) {
            if (is_int($key)) {
                $intKeys++;
            }
        }
        if ($intKeys !== 2) {
            $context['error'][] = "#{$vars[0][0]} requires exactly one argument";
        }
        $context['usedFeature'][$vars[0][0]]++;
        return true;
    }

    /**
     * validate block custom helper token
     *
     * @param array<string,array|string|integer> $context current compile context
     * @param array<boolean|integer|string|array> $vars parsed arguments list
     * @param boolean $inverted the logic will be inverted
     *
     * @return integer Return number of used custom helpers
     */
    protected static function blockCustomHelper(array &$context, array $vars, bool $inverted = false)
    {
        if (is_string($vars[0][0])) {
            if (static::resolveHelper($context, $vars)) {
                return ++$context['usedFeature']['helper'];
            }
        }
        return null;
    }

    /**
     * validate inverted section
     *
     * @param array<string,array|string|integer> $context current compile context
     * @param array<boolean|integer|string|array> $vars parsed arguments list
     *
     * @return integer Return number of inverted sections
     */
    protected static function invertedSection(array &$context, array $vars)
    {
        return ++$context['usedFeature']['isec'];
    }

    /**
     * Return compiled PHP code for a handlebars block end token
     *
     * @param array<string,array|string|integer> $context current compile context
     * @param array<boolean|integer|string|array> $vars parsed arguments list
     * @param string|null $match should also match to this operator
     *
     * @return boolean|integer Return true when required block ended, or Token::POS_BACKFILL when backfill happened.
     */
    protected static function blockEnd(array &$context, array &$vars, ?string $match = null)
    {
        $c = count($context['stack']) - 2;
        $pop = ($c >= 0) ? $context['stack'][$c + 1] : '';
        if (($match !== null) && ($match !== $pop)) {
            return false;
        }
        // if we didn't match our $pop, we didn't actually do a level, so only subtract a level here
        $context['level']--;
        $pop2 = ($c >= 0) ? $context['stack'][$c]: '';
        switch ($context['currentToken'][Token::POS_INNERTAG]) {
            case 'with':
                if ($pop2 !== '[with]') {
                    $context['error'][] = 'Unexpect token: {{/with}} !';
                    return false;
                }
                return true;
        }

        switch ($pop) {
            case '#':
            case '^':
                $elsechain = array_shift($context['elselvl']);
                if (isset($elsechain[0])) {
                    // we need to repeat a level due to else chains: {{else if}}
                    $context['level']++;
                    $context['currentToken'][Token::POS_RSPACE] = $context['currentToken'][Token::POS_BACKFILL] = '{{/' . implode('}}{{/', $elsechain) . '}}' . Token::toString($context['currentToken']) . $context['currentToken'][Token::POS_RSPACE];
                    return Token::POS_BACKFILL;
                }
                // no break
            case '#>':
            case '#*':
                [$levels, $spvar, $var] = Expression::analyze($context, $vars[0]);
                $v = Expression::toString($levels, $spvar, $var);
                if ($pop2 !== $v) {
                    $context['error'][] = 'Unexpect token ' . Token::toString($context['currentToken']) . " ! Previous token {{{$pop}$pop2}} is not closed";
                    return false;
                }
                return true;
            default:
                $context['error'][] = 'Unexpect token: ' . Token::toString($context['currentToken']) . ' !';
                return false;
        }
    }

    /**
     * handle raw block
     *
     * @param string[] $token detected handlebars {{ }} token
     * @param array<string,array|string|integer> $context current compile context
     *
     * @return boolean Return true when in rawblock mode
     */
    protected static function rawblock(array &$token, array &$context): bool
    {
        $inner = $token[Token::POS_INNERTAG];

        // skip parse when inside raw block
        if ($context['rawblock'] && !(($token[Token::POS_BEGINRAW] === '{{') && ($token[Token::POS_OP] === '/') && ($context['rawblock'] === $inner))) {
            return true;
        }

        $token[Token::POS_INNERTAG] = $inner;

        // Handle raw block
        if ($token[Token::POS_BEGINRAW] === '{{') {
            if ($token[Token::POS_ENDRAW] !== '}}') {
                $context['error'][] = 'Bad token ' . Token::toString($token) . ' ! Do you mean ' . Token::toString($token, array(Token::POS_ENDRAW => '}}')) . ' ?';
            }
            if ($context['rawblock']) {
                Token::setDelimiter($context);
                $context['rawblock'] = false;
            } else {
                if ($token[Token::POS_OP]) {
                    $context['error'][] = "Wrong raw block begin with " . Token::toString($token) . ' ! Remove "' . $token[Token::POS_OP] . '" to fix this issue.';
                }
                $context['rawblock'] = $token[Token::POS_INNERTAG];
                Token::setDelimiter($context);
                $token[Token::POS_OP] = '#';
            }
            $token[Token::POS_ENDRAW] = '}}';
        }

        return false;
    }

    /**
     * handle comment
     *
     * @param string[] $token detected handlebars {{ }} token
     * @param array<string,array|string|integer> $context current compile context
     *
     * @return boolean Return true when is comment
     */
    protected static function comment(array &$token, array &$context): bool
    {
        if ($token[Token::POS_OP] === '!') {
            $context['usedFeature']['comment']++;
            return true;
        }
        return false;
    }

    /**
     * Collect handlebars usage information, detect template error.
     *
     * @param string[] $token detected handlebars {{ }} token
     * @param array<string,array|string|integer> $context current compile context
     *
     * @return string|array<string,array|string|integer>|null $token string when rawblock; array when valid token require to be compiled, null when skip the token.
     */
    protected static function token(array &$token, array &$context): string|array|null
    {
        $context['currentToken'] = &$token;

        if (static::rawblock($token, $context)) {
            return Token::toString($token);
        }

        if (static::delimiter($token, $context)) {
            return null;
        }

        if (static::comment($token, $context)) {
            static::spacing($token, $context);
            return null;
        }

        [$raw, $vars] = Parser::parse($token, $context);

        // Handle spacing (standalone tags, partial indent)
        static::spacing($token, $context, (($token[Token::POS_OP] === '') || ($token[Token::POS_OP] === '&')) && (!isset($vars[0][0]) || ($vars[0][0] !== 'else')) || ($context['flags']['nostd'] > 0));

        $inlinepartial = static::inlinePartial($context, $vars);
        $partialblock = static::partialBlock($context, $vars);

        if ($partialblock || $inlinepartial) {
            $context['stack'] = array_slice($context['stack'], 0, -4);
            static::pushPartial($context, $context['currentToken'][Token::POS_LOTHER] . $context['currentToken'][Token::POS_LSPACE] . Token::toString($context['currentToken']));
            $context['currentToken'][Token::POS_LOTHER] = '';
            $context['currentToken'][Token::POS_LSPACE] = '';
            return null;
        }

        if (static::operator($token[Token::POS_OP], $context, $vars)) {
            return isset($token[Token::POS_BACKFILL]) ? null : array($raw, $vars);
        }

        if (count($vars) == 0) {
            return $context['error'][] = 'Wrong variable naming in ' . Token::toString($token);
        }

        if (!isset($vars[0])) {
            return $context['error'][] = 'Do not support name=value in ' . Token::toString($token) . ', you should use it after a custom helper.';
        }

        $context['usedFeature'][$raw ? 'raw' : 'enc']++;

        foreach ($vars as $var) {
            if (!isset($var[0]) || ($var[0] === 0)) {
                if ($context['level'] == 0) {
                    $context['usedFeature']['rootthis']++;
                }
                $context['usedFeature']['this']++;
            }
        }

        if (!isset($vars[0][0])) {
            return array($raw, $vars);
        }

        if ($vars[0][0] === 'else') {
            static::doElse($context, $vars);
            return array($raw, $vars);
        }

        if (!static::helper($context, $vars)) {
            static::lookup($context, $vars);
            static::log($context, $vars);
        }

        return array($raw, $vars);
    }

    /**
     * Return 1 or larger number when else token detected
     *
     * @param array<string,array|string|integer> $context current compile context
     * @param array<boolean|integer|string|array> $vars parsed arguments list
     *
     * @return integer Return 1 or larger number when else token detected
     */
    protected static function doElse(array &$context, array $vars)
    {
        if ($context['level'] == 0) {
            $context['error'][] = '{{else}} only valid in if, unless, each, and #section context';
        }

        if (isset($vars[1][0])) {
            $token = $context['currentToken'];
            $context['currentToken'][Token::POS_INNERTAG] = 'else';
            $context['currentToken'][Token::POS_RSPACE] = "{{#{$vars[1][0]} " . preg_replace('/^\\s*else\\s+' . $vars[1][0] . '\\s*/', '', $token[Token::POS_INNERTAG]) . '}}' . $context['currentToken'][Token::POS_RSPACE];
            array_unshift($context['elselvl'][0], $vars[1][0]);
            $context['elsechain'] = true;
        }

        return ++$context['usedFeature']['else'];
    }

    /**
     * Validate {{log ...}}
     *
     * @param array<string,array|string|integer> $context current compile context
     * @param array<boolean|integer|string|array> $vars parsed arguments list
     */
    public static function log(array &$context, array $vars): void
    {
        if (isset($vars[0][0]) && ($vars[0][0] === 'log')) {
            if (count($vars) < 2) {
                $context['error'][] = "No argument after {{log}} !";
            }
            $context['usedFeature']['log']++;
        }
    }

    /**
     * Validate {{lookup ...}}
     *
     * @param array<string,array|string|integer> $context current compile context
     * @param array<boolean|integer|string|array> $vars parsed arguments list
     */
    public static function lookup(array &$context, array $vars): void
    {
        if (isset($vars[0][0]) && ($vars[0][0] === 'lookup')) {
            if (count($vars) < 2) {
                $context['error'][] = "No argument after {{lookup}} !";
            } elseif (count($vars) < 3) {
                $context['error'][] = "{{lookup}} requires 2 arguments !";
            }
            $context['usedFeature']['lookup']++;
        }
    }

    /**
     * Return true when the name is listed in helper table
     *
     * @param array<string,array|string|integer> $context current compile context
     * @param array<boolean|integer|string|array> $vars parsed arguments list
     * @param boolean $checkSubexp true when check for subexpression
     *
     * @return boolean Return true when it is custom helper
     */
    public static function helper(array &$context, array $vars, bool $checkSubexp = false): bool
    {
        if (static::resolveHelper($context, $vars)) {
            $context['usedFeature']['helper']++;
            return true;
        }

        if ($checkSubexp) {
            switch ($vars[0][0]) {
                case 'if':
                case 'unless':
                case 'with':
                case 'each':
                case 'lookup':
                    return true;
            }
        }

        return false;
    }

    /**
     * use helperresolver to resolve helper, return true when helper founded
     *
     * @param array<string,array|string|integer> $context Current context of compiler progress.
     * @param array<boolean|integer|string|array> $vars parsed arguments list
     */
    public static function resolveHelper(array &$context, array &$vars): bool
    {
        if (count($vars[0]) !== 1) {
            return false;
        }
        if (isset($context['helpers'][$vars[0][0]])) {
            return true;
        }

        return false;
    }

    /**
     * Return true when this token is block custom helper
     *
     * @param array<string,array|string|integer> $context current compile context
     * @param array<boolean|integer|string|array> $vars parsed arguments list
     */
    protected static function isBlockHelper(array $context, array $vars): bool
    {
        if (!isset($vars[0][0])) {
            return false;
        }

        if (!static::resolveHelper($context, $vars)) {
            return false;
        }

        return true;
    }

    /**
     * validate inline partial
     *
     * @param array<string,array|string|integer> $context current compile context
     * @param array<boolean|integer|string|array> $vars parsed arguments list
     */
    protected static function inline(array &$context, array $vars)
    {
        if (!isset($vars[0][0]) || ($vars[0][0] !== 'inline')) {
            $context['error'][] = "Do not support {{#*{$context['currentToken'][Token::POS_INNERTAG]}}}, now we only support {{#*inline \"partialName\"}}template...{{/inline}}";
        }
        if (!isset($vars[1][0])) {
            $context['error'][] = "Error in {{#*{$context['currentToken'][Token::POS_INNERTAG]}}}: inline require 1 argument for partial name!";
        }
        return true;
    }

    /**
     * validate partial
     *
     * @param array<string,array|string|integer> $context current compile context
     * @param array<boolean|integer|string|array> $vars parsed arguments list
     *
     * @return integer|boolean Return 1 or larger number for runtime partial, return true for other case
     */
    protected static function partial(array &$context, array $vars)
    {
        if (Parser::isSubExp($vars[0])) {
            return $context['usedFeature']['dynpartial']++;
        } else {
            if ($context['currentToken'][Token::POS_OP] !== '#>') {
                Partial::read($context, $vars[0][0]);
            }
        }

        return true;
    }

    /**
     * Modify $token when spacing rules matched.
     *
     * @param array<string> $token detected handlebars {{ }} token
     * @param array<string,array|string|integer> $context current compile context
     * @param boolean $nost do not do standalone logic
     */
    protected static function spacing(array &$token, array &$context, bool $nost = false): ?string
    {
        // left line change detection
        $lsp = preg_match('/^(.*)(\\r?\\n)([ \\t]*?)$/s', $token[Token::POS_LSPACE], $lmatch);
        $ind = $lsp ? $lmatch[3] : $token[Token::POS_LSPACE];
        // right line change detection
        $rsp = preg_match('/^([ \\t]*?)(\\r?\\n)(.*)$/s', $token[Token::POS_RSPACE], $rmatch);
        $st = true;
        // setup ahead flag
        $ahead = $context['tokens']['ahead'];
        $context['tokens']['ahead'] = preg_match('/^[^\n]*{{/s', $token[Token::POS_RSPACE] . $token[Token::POS_ROTHER]);
        // reset partial indent
        $context['tokens']['partialind'] = '';
        // same tags in the same line , not standalone
        if (!$lsp && $ahead) {
            $st = false;
        }
        if ($nost) {
            $st = false;
        }
        // not standalone because other things in the same line ahead
        if ($token[Token::POS_LOTHER] && !$token[Token::POS_LSPACE]) {
            $st = false;
        }
        // not standalone because other things in the same line behind
        if ($token[Token::POS_ROTHER] && !$token[Token::POS_RSPACE]) {
            $st = false;
        }
        if ($st && (
            ($lsp && $rsp) // both side cr
                || ($rsp && !$token[Token::POS_LOTHER]) // first line without left
                || ($lsp && !$token[Token::POS_ROTHER]) // final line
            )) {
            // handle partial
            if ($token[Token::POS_OP] === '>') {
                if (!$context['flags']['noind']) {
                    $context['tokens']['partialind'] = $token[Token::POS_LSPACECTL] ? '' : $ind;
                    $token[Token::POS_LSPACE] = (isset($lmatch[2]) ? ($lmatch[1] . $lmatch[2]) : '');
                }
            } else {
                $token[Token::POS_LSPACE] = (isset($lmatch[2]) ? ($lmatch[1] . $lmatch[2]) : '');
            }
            $token[Token::POS_RSPACE] = $rmatch[3] ?? '';
        }

        // Handle space control.
        if ($token[Token::POS_LSPACECTL]) {
            $token[Token::POS_LSPACE] = '';
        }
        if ($token[Token::POS_RSPACECTL]) {
            $token[Token::POS_RSPACE] = '';
        }

        return null;
    }
}
