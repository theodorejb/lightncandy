<?php

namespace LightnCandy;

class Compiler extends Validator
{
    public static array $lastParsed;

    /**
     * Compile template into PHP code
     *
     * @param array<string,array|string|integer> $context Current context
     * @param string $template handlebars template
     */
    public static function compileTemplate(array &$context, string $template): string
    {
        array_unshift($context['parsed'], array());
        Validator::verify($context, $template);
        static::$lastParsed = $context['parsed'];

        if (count($context['error'])) {
            return '';
        }

        Parser::setDelimiter($context);

        $context['compile'] = true;

        // Handle dynamic partials
        Partial::handleDynamic($context);

        // Do PHP code generation.
        $code = '';
        foreach ($context['parsed'][0] as $info) {
            if (is_array($info)) {
                $context['tokens']['current']++;
                $code .= "'" . static::compileToken($context, $info) . "'";
            } else {
                $code .= $info;
            }
        }

        array_shift($context['parsed']);

        return $code;
    }

    /**
     * Compose LightnCandy render codes for include()
     *
     * @param array<string,array|string|integer> $context Current context
     * @param string $code generated PHP code
     */
    public static function composePHPRender(array $context, string $code): string
    {
        $flagPartNC = Expression::boolString($context['flags']['partnc']);
        $flagKnownHlp = Expression::boolString($context['flags']['knohlp']);
        $runtime = Runtime::class;

        $constants = Exporter::constants($context);
        $helpers = Exporter::helpers($context);
        $partials = implode(",\n", $context['partialCode']);
        $use = "use {$runtime} as LR;";
        $stringObject = 'use \\LightnCandy\\StringObject as StringObject;';
        $safeString = ($context['usedFeature']['enc'] > 0) ? "use {$context['safestring']} as SafeString;" : '';
        // Return generated PHP code string.
        return <<<VAREND
$stringObject{$safeString}{$use}return function (\$in = null, \$options = null) {
    \$helpers = $helpers;
    \$partials = array($partials);
    \$cx = array(
        'flags' => array(
            'partnc' => $flagPartNC,
            'knohlp' => $flagKnownHlp,
            'debug' => 1,
        ),
        'constants' => $constants,
        'helpers' => isset(\$options['helpers']) ? array_merge(\$helpers, \$options['helpers']) : \$helpers,
        'partials' => isset(\$options['partials']) ? array_merge(\$partials, \$options['partials']) : \$partials,
        'scopes' => array(),
        'sp_vars' => isset(\$options['data']) ? array_merge(array('root' => \$in), \$options['data']) : array('root' => \$in),
        'blparam' => array(),
        'partialid' => 0,
    );
    {$context['ops']['array_check']}
    {$context['ops']['op_start']}'$code'{$context['ops']['op_end']}
};
VAREND
        ;
    }

    /**
     * Get function name for standalone or none standalone template.
     *
     * @param array<string,array|string|integer> $context Current context of compiler progress.
     * @param string $name base function name
     * @param string $tag original handlabars tag for debug
     *
     * @return string compiled Function name
     *
     * @expect 'LR::test(' when input array('flags' => array('debug' => 0)), 'test', ''
     * @expect 'LR::test2(' when input array('flags' => array('debug' => 0)), 'test2', ''
     * @expect 'LR::debug(\'abc\', \'test\', ' when input array('flags' => array('debug' => 1), 'funcprefix' => 'haha456'), 'test', 'abc'
     */
    protected static function getFuncName(array &$context, string $name, $tag): string
    {
        static::addUsageCount($context, 'runtime', $name);

        if ($context['flags']['debug'] && ($name != 'miss')) {
            $dbg = "'$tag', '$name', ";
            $name = 'debug';
            static::addUsageCount($context, 'runtime', 'debug');
        } else {
            $dbg = '';
        }

        return "LR::$name($dbg";
    }

    /**
     * Get string presentation of variables
     *
     * @param array<string,array|string|integer> $context current compile context
     * @param array<array> $vn variable name array.
     * @param array<string>|null $blockParams block param list
     *
     * @return array<string|array> variable names
     *
     * @expect array('array(array($in),array())', array('this')) when input array('flags'=>array()), array(null)
     * @expect array('array(array($in,$in),array())', array('this', 'this')) when input array('flags'=>array()), array(null, null)
     * @expect array('array(array(),array(\'a\'=>$in))', array('this')) when input array('flags'=>array()), array('a' => null)
     */
    protected static function getVariableNames(array &$context, array $vn, array $blockParams = []): array
    {
        $vars = array(array(), array());
        $exps = array();
        foreach ($vn as $i => $v) {
            $V = static::getVariableNameOrSubExpression($context, $v);
            if (is_string($i)) {
                $vars[1][] = "'$i'=>{$V[0]}";
            } else {
                $vars[0][] = $V[0];
            }
            $exps[] = $V[1];
        }
        $bp = $blockParams ? (',array(' . Expression::listString($blockParams) . ')') : '';
        return array('array(array(' . implode(',', $vars[0]) . '),array(' . implode(',', $vars[1]) . ")$bp)", $exps);
    }

    /**
     * Get string presentation of a sub expression
     *
     * @param array<string,array|string|integer> $context current compile context
     * @param array<boolean|integer|string|array> $vars parsed arguments list
     *
     * @return array<string> code representing passed expression
     */
    public static function compileSubExpression(array &$context, array $vars): array
    {
        $ret = static::customHelper($context, $vars, true, true, true);

        return array($ret, 'FIXME: $subExpression');
    }

    /**
     * Get string presentation of a subexpression or a variable
     *
     * @param array<array|string|integer> $context current compile context
     * @param array<array|string|integer> $var variable parsed path
     *
     * @return array<string> variable names
     */
    protected static function getVariableNameOrSubExpression(&$context, $var): array
    {
        return Parser::isSubExp($var) ? static::compileSubExpression($context, $var[1]) : static::getVariableName($context, $var);
    }

    /**
     * Get string presentation of a variable
     *
     * @param array<array|string|integer> $var variable parsed path
     * @param array<array|string|integer> $context current compile context
     * @param array<string>|null $lookup extra lookup string as valid PHP variable name
     *
     * @return array<string> variable names
     *
     * @expect array('$in', 'this') when input array('flags'=>array('debug'=>0)), array(null)
     * @expect array('(isset($in[\'true\']) ? $in[\'true\'] : null)', '[true]') when input array('flags'=>array('debug'=>0)), array('true')
     * @expect array('(isset($in[\'false\']) ? $in[\'false\'] : null)', '[false]') when input array('flags'=>array('debug'=>0)), array('false')
     * @expect array('true', 'true') when input array('flags'=>array('debug'=>0)), array(-1, 'true')
     * @expect array('false', 'false') when input array('flags'=>array('debug'=>0)), array(-1, 'false')
     * @expect array('(isset($in[\'2\']) ? $in[\'2\'] : null)', '[2]') when input array('flags'=>array('debug'=>0)), array('2')
     * @expect array('2', '2') when input array('flags'=>array('debug'=>0)), array(-1, '2')
     * @expect array("(isset(\$cx['sp_vars']['index']) ? \$cx['sp_vars']['index'] : null)", '@[index]') when input array('flags'=>array('debug'=>0)), array('@index')
     * @expect array("(isset(\$cx['sp_vars']['key']) ? \$cx['sp_vars']['key'] : null)", '@[key]') when input array('flags'=>array('debug'=>0)), array('@key')
     * @expect array("(isset(\$cx['sp_vars']['first']) ? \$cx['sp_vars']['first'] : null)", '@[first]') when input array('flags'=>array('debug'=>0)), array('@first')
     * @expect array("(isset(\$cx['sp_vars']['last']) ? \$cx['sp_vars']['last'] : null)", '@[last]') when input array('flags'=>array('debug'=>0)), array('@last')
     * @expect array('(isset($in[\'"a"\']) ? $in[\'"a"\'] : null)', '["a"]') when input array('flags'=>array('debug'=>0)), array('"a"')
     * @expect array('"a"', '"a"') when input array('flags'=>array('debug'=>0)), array(-1, '"a"')
     * @expect array('(isset($in[\'a\']) ? $in[\'a\'] : null)', '[a]') when input array('flags'=>array('debug'=>0)), array('a')
     * @expect array('(isset($cx[\'scopes\'][count($cx[\'scopes\'])-1][\'a\']) ? $cx[\'scopes\'][count($cx[\'scopes\'])-1][\'a\'] : null)', '../[a]') when input array('flags'=>array('debug'=>0)), array(1,'a')
     * @expect array('(isset($cx[\'scopes\'][count($cx[\'scopes\'])-3][\'a\']) ? $cx[\'scopes\'][count($cx[\'scopes\'])-3][\'a\'] : null)', '../../../[a]') when input array('flags'=>array('debug'=>0)), array(3,'a')
     * @expect array('(isset($in[\'id\']) ? $in[\'id\'] : null)', 'this.[id]') when input array('flags'=>array('debug'=>0)), array(null, 'id')
     */
    protected static function getVariableName(array &$context, ?array $var, ?array $lookup = null): array
    {
        if (isset($var[0]) && ($var[0] === Parser::LITERAL)) {
            if ($var[1] === "undefined") {
                $var[1] = "null";
            }
            return array($var[1], preg_replace('/\'(.*)\'/', '$1', $var[1]));
        }

        [$levels, $spvar, $var] = Expression::analyze($context, $var);
        $exp = Expression::toString($levels, $spvar, $var);
        $base = $spvar ? "\$cx['sp_vars']" : '$in';

        // change base when trace to parent
        if ($levels > 0) {
            if ($spvar) {
                $base .= str_repeat("['_parent']", $levels);
            } else {
                $base = "\$cx['scopes'][count(\$cx['scopes'])-$levels]";
            }
        }

        if ((empty($var) || (count($var) == 0) || (($var[0] === null) && (count($var) == 1))) && ($lookup === null)) {
            return array($base, $exp);
        }

        if ((count($var) > 0) && ($var[0] === null)) {
            array_shift($var);
        }

        $n = Expression::arrayString($var);
        $k = array_pop($var);
        $L = $lookup ? "[{$lookup[0]}]" : '';

        $check = "isset($base$n$L)";
        $lenStart = '';
        $lenEnd = '';

        if ($lookup === null && $k === 'length') {
            $checks = [];
            if ($levels > 0) {
                $checks[] = "isset($base)";
            }
            if (!$spvar) {
                $p = count($var) ? Expression::arrayString($var) : '';
                if ($levels === 0 && $p !== '') {
                    $checks[] = "isset($base$p)";
                }
                $checks[] = ("$base$p" == '$in') ? '$inary' : "is_array($base$p)";
            }
            $lenStart = '(' . ((count($checks) > 1) ? '(' : '') . implode(' && ', $checks) . ((count($checks) > 1) ? ')' : '') . " ? count($base" . Expression::arrayString($var) . ') : ';
            $lenEnd = ')';
        }

        return array("($check ? $base$n$L : $lenStart" . ($context['flags']['debug'] ? (static::getFuncName($context, 'miss', '') . "\$cx, '$exp')") : 'null') . ")$lenEnd", $lookup ? "lookup $exp $lookup[1]" : $exp);
    }

    /**
     * Return compiled PHP code for a handlebars token
     *
     * @param array<string,array|string|integer> $context current compile context
     * @param array<string,array|boolean> $info parsed information
     */
    protected static function compileToken(array &$context, array $info): string
    {
        [$raw, $vars, $token, $indent] = $info;

        $context['tokens']['partialind'] = $indent;
        $context['currentToken'] = $token;

        if ($ret = static::operator($token[Token::POS_OP], $context, $vars)) {
            return $ret;
        }

        if (isset($vars[0][0])) {
            if ($ret = static::customHelper($context, $vars, $raw, true)) {
                return static::compileOutput($context, $ret, 'FIXME: helper', $raw, false);
            }
            if ($vars[0][0] === 'else') {
                return static::doElse($context, $vars);
            }
            if ($vars[0][0] === 'lookup') {
                return static::compileLookup($context, $vars, $raw);
            }
            if ($vars[0][0] === 'log') {
                return static::compileLog($context, $vars, $raw);
            }
        }

        return static::compileVariable($context, $vars, $raw, false);
    }

    /**
     * handle partial
     *
     * @param array $context current compile context
     * @param array $vars parsed arguments list
     */
    public static function partial(array &$context, array $vars): string
    {
        Parser::getBlockParams($vars);
        $pid = Parser::getPartialBlock($vars);
        $p = array_shift($vars);
        if ($context['flags']['runpart']) {
            if (!isset($vars[0])) {
                $vars[0] = $context['flags']['partnc'] ? array(0, 'null') : array();
            }
            $v = static::getVariableNames($context, $vars);
            $tag = ">$p[0] " .implode(' ', $v[1]);
            if (Parser::isSubExp($p)) {
                [$p] = static::compileSubExpression($context, $p[1]);
            } else {
                $p = "'$p[0]'";
            }
            $sp = $context['tokens']['partialind'] ? ", '{$context['tokens']['partialind']}'" : '';
            return $context['ops']['seperator'] . static::getFuncName($context, 'p', $tag) . "\$cx, $p, $v[0],$pid$sp){$context['ops']['seperator']}";
        }
        return isset($context['usedPartial'][$p[0]]) ? "{$context['ops']['seperator']}'" . Partial::compileStatic($context, $p[0]) . "'{$context['ops']['seperator']}" : $context['ops']['seperator'];
    }

    /**
     * handle inline partial
     *
     * @param array $context current compile context
     * @param array $vars parsed arguments list
     */
    public static function inline(array &$context, array $vars): string
    {
        Parser::getBlockParams($vars);
        [$code] = array_shift($vars);
        $p = array_shift($vars);
        if (!isset($vars[0])) {
            $vars[0] = $context['flags']['partnc'] ? array(0, 'null') : array();
        }
        $v = static::getVariableNames($context, $vars);
        $tag = ">*inline $p[0]" .implode(' ', $v[1]);
        return $context['ops']['seperator'] . static::getFuncName($context, 'in', $tag) . "\$cx, '{$p[0]}', $code){$context['ops']['seperator']}";
    }

    /**
     * Return compiled PHP code for a handlebars inverted section begin token
     *
     * @param array $context current compile context
     * @param array $vars parsed arguments list
     */
    protected static function invertedSection(array &$context, array $vars): string
    {
        $v = static::getVariableName($context, $vars[0]);
        return "{$context['ops']['cnd_start']}(" . static::getFuncName($context, 'isec', '^' . $v[1]) . "\$cx, {$v[0]})){$context['ops']['cnd_then']}";
    }

    /**
     * Return compiled PHP code for a handlebars block custom helper begin token
     *
     * @param array $context current compile context
     * @param array $vars parsed arguments list
     * @param boolean $inverted the logic will be inverted
     */
    protected static function blockCustomHelper(array &$context, array $vars, bool $inverted = false): string
    {
        $bp = Parser::getBlockParams($vars);
        $ch = array_shift($vars);
        $inverted = $inverted ? 'true' : 'false';
        static::addUsageCount($context, 'helpers', $ch[0]);
        $v = static::getVariableNames($context, $vars, $bp);

        return $context['ops']['seperator'] . static::getFuncName($context, 'hbbch', ($inverted ? '^' : '#') . implode(' ', $v[1])) . "\$cx, '$ch[0]', {$v[0]}, \$in, $inverted, function(\$cx, \$in) {{$context['ops']['array_check']}{$context['ops']['f_start']}";
    }

    /**
     * Return compiled PHP code for a handlebars block end token
     *
     * @param array $context current compile context
     * @param array $vars parsed arguments list
     * @param string|null $match should also match to this operator
     *
     * @return string Return compiled code segment for the token
     */
    protected static function blockEnd(array &$context, array &$vars, ?string $match = null): string
    {
        $pop = $context['stack'][count($context['stack']) - 1];

        switch (isset($context['helpers'][$context['currentToken'][Token::POS_INNERTAG]]) ? 'skip' : $context['currentToken'][Token::POS_INNERTAG]) {
            case 'if':
            case 'unless':
                if ($pop === ':') {
                    array_pop($context['stack']);
                    return "{$context['ops']['cnd_end']}";
                }
                return "{$context['ops']['cnd_else']}''{$context['ops']['cnd_end']}";
            case 'with':
                return "{$context['ops']['f_end']}}){$context['ops']['seperator']}";
        }

        if ($pop === ':') {
            array_pop($context['stack']);
            return "{$context['ops']['f_end']}}){$context['ops']['seperator']}";
        }

        switch ($pop) {
            case '#':
                return "{$context['ops']['f_end']}}){$context['ops']['seperator']}";
            case '^':
                return "{$context['ops']['cnd_else']}''{$context['ops']['cnd_end']}";
        }

        throw new \Exception('Failed to match case for blockEnd');
    }

    /**
     * Return compiled PHP code for a handlebars block begin token
     *
     * @param array $context current compile context
     * @param array $vars parsed arguments list
     *
     * @return string Return compiled code segment for the token
     */
    protected static function blockBegin(array &$context, array $vars): string
    {
        $v = isset($vars[1]) ? static::getVariableNameOrSubExpression($context, $vars[1]) : array(null, array());
        switch ($vars[0][0] ?? null) {
            case 'if':
                $includeZero = (isset($vars['includeZero'][1]) && $vars['includeZero'][1]) ? 'true' : 'false';
                return "{$context['ops']['cnd_start']}(" . static::getFuncName($context, 'ifvar', $v[1]) . "\$cx, {$v[0]}, {$includeZero})){$context['ops']['cnd_then']}";
            case 'unless':
                $includeZero = (isset($vars['includeZero'][1]) && $vars['includeZero'][1]) ? 'true' : 'false';
                return "{$context['ops']['cnd_start']}(!" . static::getFuncName($context, 'ifvar', $v[1]) . "\$cx, {$v[0]}, {$includeZero})){$context['ops']['cnd_then']}";
            case 'each':
                return static::section($context, $vars, true);
            case 'with':
                if ($r = static::with($context, $vars)) {
                    return $r;
                }
        }

        return static::section($context, $vars);
    }

    /**
     * compile {{#foo}} token
     *
     * @param array $context current compile context
     * @param array $vars parsed arguments list
     * @param boolean $isEach the section is #each
     *
     * @return string Return compiled code segment for the token
     */
    protected static function section(array &$context, array $vars, bool $isEach = false): string
    {
        $bs = 'null';
        $be = '';
        if ($isEach) {
            $bp = Parser::getBlockParams($vars);
            $bs = $bp ? ('array(' . Expression::listString($bp) . ')') : 'null';
            $be = $bp ? (' as |' . implode(' ', $bp) . '|') : '';
            array_shift($vars);
        }
        $v = static::getVariableNameOrSubExpression($context, $vars[0]);
        $each = $isEach ? 'true' : 'false';
        return $context['ops']['seperator'] . static::getFuncName($context, 'sec', ($isEach ? 'each ' : '') . $v[1] . $be) . "\$cx, {$v[0]}, $bs, \$in, $each, function(\$cx, \$in) {{$context['ops']['array_check']}{$context['ops']['f_start']}";
    }

    /**
     * compile {{with}} token
     *
     * @param array $context current compile context
     * @param array $vars parsed arguments list
     *
     * @return string Return compiled code segment for the token
     */
    protected static function with(array &$context, array $vars)
    {
        $v = isset($vars[1]) ? static::getVariableNameOrSubExpression($context, $vars[1]) : array(null, array());
        $bp = Parser::getBlockParams($vars);
        $bs = $bp ? ('array(' . Expression::listString($bp) . ')') : 'null';
        $be = $bp ? " as |$bp[0]|" : '';
        return $context['ops']['seperator'] . static::getFuncName($context, 'wi', 'with ' . $v[1] . $be) . "\$cx, {$v[0]}, $bs, \$in, function(\$cx, \$in) {{$context['ops']['array_check']}{$context['ops']['f_start']}";
    }

    /**
     * Return compiled PHP code for a handlebars custom helper token
     *
     * @param array<string,array|string|integer> $context current compile context
     * @param array<boolean|integer|string|array> $vars parsed arguments list
     * @param boolean $raw is this {{{ token or not
     * @param boolean $nosep true to compile without seperator
     * @param boolean $subExp true when compile for subexpression
     *
     * @return string Return compiled code segment for the token when the token is custom helper
     */
    protected static function customHelper(array &$context, array $vars, bool $raw, bool $nosep, bool $subExp = false): string
    {
        if (count($vars[0]) > 1) {
            return '';
        }

        if (!isset($context['helpers'][$vars[0][0]])) {
            if ($subExp) {
                if ($vars[0][0] == 'lookup') {
                    return static::compileLookup($context, $vars, $raw, true);
                }
            }
            return '';
        }

        $fn = $raw ? 'raw' : $context['ops']['enc'];
        $ch = array_shift($vars);
        $v = static::getVariableNames($context, $vars);
        static::addUsageCount($context, 'helpers', $ch[0]);
        $sep = $nosep ? '' : $context['ops']['seperator'];

        return $sep . static::getFuncName($context, 'hbch', "$ch[0] " . implode(' ', $v[1])) . "\$cx, '$ch[0]', {$v[0]}, '$fn', \$in)$sep";
    }

    /**
     * Return compiled PHP code for a handlebars else token
     *
     * @param array $context current compile context
     * @param array $vars parsed arguments list
     *
     * @return string Return compiled code segment for the token when the token is else
     */
    protected static function doElse(array &$context, array $vars)
    {
        $v = $context['stack'][count($context['stack']) - 2];

        if ((($v === '[if]') && !isset($context['helpers']['if'])) ||
           (($v === '[unless]') && !isset($context['helpers']['unless']))) {
            $context['stack'][] = ':';
            return "{$context['ops']['cnd_else']}";
        }

        return "{$context['ops']['f_end']}}, function(\$cx, \$in) {{$context['ops']['array_check']}{$context['ops']['f_start']}";
    }

    /**
     * Return compiled PHP code for a handlebars log token
     *
     * @param array<string,array|string|integer> $context current compile context
     * @param array<boolean|integer|string|array> $vars parsed arguments list
     * @param boolean $raw is this {{{ token or not
     *
     * @return string Return compiled code segment for the token
     */
    protected static function compileLog(&$context, &$vars, $raw)
    {
        array_shift($vars);
        $v = static::getVariableNames($context, $vars);
        return $context['ops']['seperator'] . static::getFuncName($context, 'lo', $v[1]) . "\$cx, {$v[0]}){$context['ops']['seperator']}";
    }

    /**
     * Return compiled PHP code for a handlebars lookup token
     *
     * @param array<string,array|string|integer> $context current compile context
     * @param array<boolean|integer|string|array> $vars parsed arguments list
     * @param boolean $raw is this {{{ token or not
     * @param boolean $nosep true to compile without seperator
     *
     * @return string Return compiled code segment for the token
     */
    protected static function compileLookup(&$context, &$vars, $raw, $nosep = false)
    {
        $v2 = static::getVariableName($context, $vars[2]);
        $v = static::getVariableName($context, $vars[1], $v2);
        $sep = $nosep ? '' : $context['ops']['seperator'];
        $ex = $nosep ? ', 1' : '';

        return $sep . static::getFuncName($context, $raw ? 'raw' : $context['ops']['enc'], $v[1]) . "\$cx, {$v[0]}$ex){$sep}";
    }

    /**
     * Return compiled PHP code for template output
     *
     * @param array<string,array|string|integer> $context current compile context
     * @param string $variable PHP code for the variable
     * @param string $expression normalized handlebars expression
     * @param boolean $raw is this {{{ token or not
     * @param boolean $nosep true to compile without seperator
     *
     * @return string Return compiled code segment for the token
     */
    protected static function compileOutput(&$context, $variable, $expression, $raw, $nosep)
    {
        $sep = $nosep ? '' : $context['ops']['seperator'];
        return $sep . static::getFuncName($context, $raw ? 'raw' : $context['ops']['enc'], $expression) . "\$cx, $variable)$sep";
    }

    /**
     * Return compiled PHP code for a handlebars variable token
     *
     * @param array<string,array|string|integer> $context current compile context
     * @param array<boolean|integer|string|array> $vars parsed arguments list
     * @param boolean $raw is this {{{ token or not
     * @param boolean $nosep true to compile without seperator
     *
     * @return string Return compiled code segment for the token
     */
    protected static function compileVariable(&$context, &$vars, $raw, $nosep)
    {
        $v = static::getVariableName($context, $vars[0]);
        return static::compileOutput($context, $v[0], $v[1], $raw, $nosep);
    }

    /**
     * Add usage count to context
     *
     * @param array<string,array|string|integer> $context current context
     * @param string $category category name, can be one of: 'var', 'helpers', 'runtime'
     * @param string $name used name
     * @param integer $count increment
     *
     * @expect 1 when input array('usedCount' => array('test' => array())), 'test', 'testname'
     * @expect 3 when input array('usedCount' => array('test' => array('testname' => 2))), 'test', 'testname'
     * @expect 5 when input array('usedCount' => array('test' => array('testname' => 2))), 'test', 'testname', 3
     */
    protected static function addUsageCount(&$context, $category, $name, $count = 1)
    {
        if (!isset($context['usedCount'][$category][$name])) {
            $context['usedCount'][$category][$name] = 0;
        }
        return ($context['usedCount'][$category][$name] += $count);
    }
}
