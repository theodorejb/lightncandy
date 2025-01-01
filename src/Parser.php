<?php

namespace LightnCandy;

class Parser
{
    // Compile time error handling flags
    const BLOCKPARAM = 9999;
    const PARTIALBLOCK = 9998;
    const LITERAL = -1;
    const SUBEXP = -2;

    /**
     * Get partial block id and fix the variable list
     *
     * @param array<boolean|integer|string|array> $vars parsed token
     */
    public static function getPartialBlock(array &$vars): int
    {
        if (isset($vars[static::PARTIALBLOCK])) {
            $id = $vars[static::PARTIALBLOCK];
            unset($vars[static::PARTIALBLOCK]);
            return $id;
        }
        return 0;
    }

    /**
     * Get block params and fix the variable list
     *
     * @param array<boolean|integer|string|array> $vars parsed token
     */
    public static function getBlockParams(array &$vars): array
    {
        if (isset($vars[static::BLOCKPARAM])) {
            $list = $vars[static::BLOCKPARAM];
            unset($vars[static::BLOCKPARAM]);
            return $list;
        }
        return [];
    }

    /**
     * Return array presentation for a literal
     *
     * @param string $name variable name.
     * @param boolean $asis keep the name as is or not
     * @param boolean $quote add single quote or not
     *
     * @return array<integer|string> Return variable name array
     */
    protected static function getLiteral(string $name, bool $asis, bool $quote = false)
    {
        return $asis ? array($name) : array(static::LITERAL, $quote ? "'$name'" : $name);
    }

    /**
     * Return array presentation for an expression
     *
     * @param string $v analyzed expression names.
     * @param array<string,array|string|integer> $context Current compile content.
     *
     * @return array<integer,string> Return variable name array
     *
     * @expect array() when input 'this', array('flags' => array()), 0
     * @expect array(1) when input '..', array('flags' => array(), 'usedFeature' => array('parent' => 0)), 0
     * @expect array(1) when input '../', array('flags' => array(), 'usedFeature' => array('parent' => 0)), 0
     * @expect array(1) when input '../.', array('flags' => array(), 'usedFeature' => array('parent' => 0)), 0
     * @expect array(1) when input '../this', array('flags' => array(), 'usedFeature' => array('parent' => 0)), 0
     * @expect array(1, 'a') when input '../a', array('flags' => array(), 'usedFeature' => array('parent' => 0)), 0
     * @expect array(2, 'a', 'b') when input '../../a.b', array('flags' => array(), 'usedFeature' => array('parent' => 0)), 0
     * @expect array(2, 'a', 'b') when input '../../[a].b', array('flags' => array(), 'usedFeature' => array('parent' => 0)), 0
     * @expect array(0, 'id') when input 'this.id', array('flags' => array(), 'usedFeature' => array('parent' => 0)), 0
     * @expect array(0, 'id') when input './id', array('flags' => array(), 'usedFeature' => array('parent' => 0)), 0
     * @expect array(\LightnCandy\Parser::LITERAL, '\'a.b\'') when input '"a.b"', array('flags' => array(), 'usedFeature' => array('parent' => 0)), 1
     * @expect array(\LightnCandy\Parser::LITERAL, '123') when input '123', array('flags' => array(), 'usedFeature' => array('parent' => 0)), 1
     * @expect array(\LightnCandy\Parser::LITERAL, 'null') when input 'null', array('flags' => array(), 'usedFeature' => array('parent' => 0)), 1
     */
    protected static function getExpression(string $v, array &$context, int|string $pos): array
    {
        $asis = ($pos === 0);

        // handle number
        if (is_numeric($v)) {
            return static::getLiteral(strval(1 * $v), $asis);
        }

        // handle double-quoted string
        if (preg_match('/^"(.*)"$/', $v, $matched)) {
            return static::getLiteral(preg_replace('/([^\\\\])\\\\\\\\"/', '$1"', preg_replace('/^\\\\\\\\"/', '"', $matched[1])), $asis, true);
        }

        // handle single quoted string
        if (preg_match('/^\\\\\'(.*)\\\\\'$/', $v, $matched)) {
            return static::getLiteral($matched[1], $asis, true);
        }

        // handle boolean, null and undefined
        if (preg_match('/^(true|false|null|undefined)$/', $v)) {
            return static::getLiteral($v, $asis);
        }

        $ret = array();
        $levels = 0;

        // handle ..
        if ($v === '..') {
            $v = '../';
        }

        // Trace to parent for ../ N times
        $v = preg_replace_callback('/\\.\\.\\//', function () use (&$levels) {
            $levels++;
            return '';
        }, trim($v));

        // remove ./ in path
        $v = preg_replace('/\\.\\//', '', $v, -1, $scoped);

        if ($levels) {
            $ret[] = $levels;
            $context['usedFeature']['parent'] ++;
        }

        if (preg_match('/\\]/', $v)) {
            preg_match_all(Token::VARNAME_SEARCH, $v, $matchedall);
        } else {
            preg_match_all('/([^\\.\\/]+)/', $v, $matchedall);
        }

        if ($v !== '.') {
            $vv = implode('.', $matchedall[1]);
            if (strlen($v) !== strlen($vv)) {
                $context['error'][] = "Unexpected character in '$v' (should it be '$vv' ?)";
            }
        }

        foreach ($matchedall[1] as $m) {
            if (substr($m, 0, 1) === '[') {
                $ret[] = substr($m, 1, -1);
            } elseif ($m !== 'this' && ($m !== '.')) {
                $ret[] = $m;
            } else {
                $scoped++;
            }
        }

        if (($scoped > 0) && ($levels === 0) && (count($ret) > 0)) {
            array_unshift($ret, 0);
        }

        return $ret;
    }

    /**
     * Parse the token and return parsed result.
     *
     * @param array<string> $token preg_match results
     * @param array<string,array|string|integer> $context current compile context
     *
     * @return array<boolean|integer|array> Return parsed result
     *
     * @expect array(false, array(array())) when input array(0,0,0,0,0,0,0,''), array('flags' => array('noesc' => 0), 'rawblock' => false)
     * @expect array(true, array(array())) when input array(0,0,0,'{{',0,'{',0,''), array('flags' => array('noesc' => 0), 'rawblock' => false)
     * @expect array(true, array(array())) when input array(0,0,0,0,0,0,0,''), array('flags' => array('noesc' => 1), 'rawblock' => false)
     * @expect array(false, array(array('a'))) when input array(0,0,0,0,0,0,0,'a'), array('flags' => array('noesc' => 0), 'rawblock' => false)
     * @expect array(false, array(array('a'), array('b'))) when input array(0,0,0,0,0,0,0,'a  b'), array('flags' => array('noesc' => 0), 'rawblock' => false)
     * @expect array(false, array(array('a'), array(-1, '\'b c\''))) when input array(0,0,0,0,0,0,0,'a "b c"'), array('flags' => array('noesc' => 0), 'rawblock' => false)
     * @expect array(false, array(array('a'), array('b c'))) when input array(0,0,0,0,0,0,0,'a [b c]'), array('flags' => array('noesc' => 0), 'rawblock' => false)
     * @expect array(false, array(array('a'), array('b c'))) when input array(0,0,0,0,0,0,0,'a [b c]'), array('flags' => array('noesc' => 0), 'rawblock' => false)
     * @expect array(false, array(array('a'), 'q' => array('b c'))) when input array(0,0,0,0,0,0,0,'a q=[b c]'), array('flags' => array('noesc' => 0), 'rawblock' => false)
     * @expect array(false, array(array('a'), array('q=[b c'))) when input array(0,0,0,0,0,0,0,'a [q=[b c]'), array('flags' => array('noesc' => 0), 'rawblock' => false)
     * @expect array(false, array(array('a'), 'q' => array('b'), array('c'))) when input array(0,0,0,0,0,0,0,'a [q]=b c'), array('flags' => array('noesc' => 0), 'rawblock' => false)
     * @expect array(false, array(array('a'), 'q' => array(-1, '\'b c\''))) when input array(0,0,0,0,0,0,0,'a q="b c"'), array('flags' => array('noesc' => 0), 'rawblock' => false)
     * @expect array(false, array(array(-2, array(array('foo'), array('bar')), '(foo bar)'))) when input array(0,0,0,0,0,0,0,'(foo bar)'), array('flags' => array('noesc' => 0), 'ops' => array('separator' => ''), 'usedFeature' => array('subexp' => 0), 'rawblock' => false)
     * @expect array(false, array(array('foo'), array("'=='"), array('bar'))) when input array(0,0,0,0,0,0,0,"foo '==' bar"), array('flags' => array('noesc' => 0), 'rawblock' => false)
     * @expect array(false, array(array(-2, array(array('foo'), array('bar')), '( foo bar)'))) when input array(0,0,0,0,0,0,0,'( foo bar)'), array('flags' => array('noesc' => 0), 'ops' => array('separator' => ''), 'usedFeature' => array('subexp' => 0), 'rawblock' => false)
     * @expect array(false, array(array('a'), array(-1, '\' b c\''))) when input array(0,0,0,0,0,0,0,'a " b c"'), array('flags' => array('noesc' => 0), 'rawblock' => false)
     * @expect array(false, array(array('a'), 'q' => array(-1, '\' b c\''))) when input array(0,0,0,0,0,0,0,'a q=" b c"'), array('flags' => array('noesc' => 0), 'rawblock' => false)
     * @expect array(false, array(array('foo'), array(-1, "' =='"), array('bar'))) when input array(0,0,0,0,0,0,0,"foo \' ==\' bar"), array('flags' => array('noesc' => 0), 'rawblock' => false)
     * @expect array(false, array(array('a'), array(' b c'))) when input array(0,0,0,0,0,0,0,'a [ b c]'), array('flags' => array('noesc' => 0), 'rawblock' => false)
     * @expect array(false, array(array('a'), 'q' => array(-1, "' d e'"))) when input array(0,0,0,0,0,0,0,"a q=\' d e\'"), array('flags' => array('noesc' => 0), 'rawblock' => false)
     * @expect array(false, array('q' => array(-2, array(array('foo'), array('bar')), '( foo bar)'))) when input array(0,0,0,0,0,0,0,'q=( foo bar)'), array('flags' => array('noesc' => 0), 'usedFeature' => array('subexp' => 0), 'ops' => array('separator' => 0), 'rawblock' => false)
     * @expect array(false, array(array('foo'))) when input array(0,0,0,0,0,0,'>','foo'), array('flags' => array('noesc' => 0), 'usedFeature' => array('subexp' => 0), 'ops' => array('separator' => 0), 'rawblock' => false)
     * @expect array(false, array(array('foo'))) when input array(0,0,0,0,0,0,'>','"foo"'), array('flags' => array('noesc' => 0), 'usedFeature' => array('subexp' => 0), 'ops' => array('separator' => 0), 'rawblock' => false)
     * @expect array(false, array(array('foo'))) when input array(0,0,0,0,0,0,'>','[foo] '), array('flags' => array('noesc' => 0), 'usedFeature' => array('subexp' => 0), 'ops' => array('separator' => 0), 'rawblock' => false)
     * @expect array(false, array(array('foo'))) when input array(0,0,0,0,0,0,'>','\\\'foo\\\''), array('flags' => array('noesc' => 0), 'usedFeature' => array('subexp' => 0), 'ops' => array('separator' => 0), 'rawblock' => false)
     */
    public static function parse(array &$token, array &$context): array
    {
        $vars = static::analyze($token[Token::POS_INNERTAG], $context);
        if ($token[Token::POS_OP] === '>') {
            $fn = static::getPartialName($vars);
        } elseif ($token[Token::POS_OP] === '#*') {
            $fn = static::getPartialName($vars, 1);
        }

        $avars = static::advancedVariable($vars, $context, Token::toString($token));

        if (isset($fn)) {
            if ($token[Token::POS_OP] === '>') {
                $avars[0] = $fn;
            } elseif ($token[Token::POS_OP] === '#*') {
                $avars[1] = $fn;
            }
        }

        return array(($token[Token::POS_BEGINRAW] === '{') || ($token[Token::POS_OP] === '&') || $context['flags']['noesc'] || $context['rawblock'], $avars);
    }

    /**
     * Get partial name from "foo" or [foo] or \'foo\'
     *
     * @param array<boolean|integer|array> $vars parsed token
     * @param integer $pos position of partial name
     *
     * @return array<string>|null Return one element partial name array
     *
     * @expect null when input array()
     * @expect array('foo') when input array('foo')
     * @expect array('foo') when input array('"foo"')
     * @expect array('foo') when input array('[foo]')
     * @expect array('foo') when input array("\\'foo\\'")
     * @expect array('foo') when input array(0, 'foo'), 1
     */
    public static function getPartialName(array &$vars, int $pos = 0): ?array
    {
        if (!isset($vars[$pos])) {
            return null;
        }
        return preg_match(SafeString::IS_SUBEXP_SEARCH, $vars[$pos]) ? null : array(preg_replace('/^("(.+)")|(\\[(.+)\\])|(\\\\\'(.+)\\\\\')$/', '$2$4$6', $vars[$pos]));
    }

    /**
     * Parse a subexpression then return parsed result.
     *
     * @param string $expression the full string of a sub expression
     * @param array<string,array|string|integer> $context current compile context
     *
     * @return array<boolean|integer|array> Return parsed result
     *
     * @expect array(\LightnCandy\Parser::SUBEXP, array(array('a'), array('b')), '(a b)') when input '(a b)', array('usedFeature' => array('subexp' => 0), 'flags' => array())
     */
    public static function subexpression(string $expression, array &$context): array
    {
        $context['usedFeature']['subexp']++;
        $vars = static::analyze(substr($expression, 1, -1), $context);
        $avars = static::advancedVariable($vars, $context, $expression);
        if (isset($avars[0][0])) {
            if (!Validator::helper($context, $avars, true)) {
                $context['error'][] = 'Missing helper: "' . $avars[0][0] . '"';
            }
        }
        return array(static::SUBEXP, $avars, $expression);
    }

    /**
     * Check a parsed result is a subexpression or not
     *
     * @param array<string|integer|array>|string $var
     *
     * @return boolean return true when input is a subexpression
     *
     * @expect false when input 0
     * @expect false when input array()
     * @expect false when input array(\LightnCandy\Parser::SUBEXP, 0)
     * @expect false when input array(\LightnCandy\Parser::SUBEXP, 0, 0)
     * @expect false when input array(\LightnCandy\Parser::SUBEXP, 0, '', 0)
     * @expect true when input array(\LightnCandy\Parser::SUBEXP, 0, '')
     */
    public static function isSubExp(array|string|null $var): bool
    {
        return is_array($var) && (count($var) === 3) && ($var[0] === static::SUBEXP) && is_string($var[2]);
    }

    /**
     * Analyze parsed token for advanced variables.
     *
     * @param array<boolean|integer|array> $vars parsed token
     * @param array<string,array|string|integer> $context current compile context
     * @param string $token original token
     *
     * @return array<boolean|integer|array> Return parsed result
     *
     * @expect array(array()) when input array('this'), array('flags' => array()), 0
     * @expect array(array('a')) when input array('a'), array('flags' => array()), 0
     * @expect array(array('a'), array('b')) when input array('a', 'b'), array('flags' => array()), 0
     * @expect array('a' => array('b')) when input array('a=b'), array('flags' => array()), 0
     * @expect array('fo o' => array(\LightnCandy\Parser::LITERAL, '123')) when input array('[fo o]=123'), array('flags' => array()), 0
     * @expect array('fo o' => array(\LightnCandy\Parser::LITERAL, '\'bar\'')) when input array('[fo o]="bar"'), array('flags' => array()), 0
     */
    protected static function advancedVariable(array $vars, array &$context, string $token): array
    {
        $ret = array();
        $i = 0;
        foreach ($vars as $idx => $var) {
            // handle (...)
            if (preg_match(SafeString::IS_SUBEXP_SEARCH, $var)) {
                $ret[$i] = static::subexpression($var, $context);
                $i++;
                continue;
            }

            // handle |...|
            if (preg_match(SafeString::IS_BLOCKPARAM_SEARCH, $var, $matched)) {
                $ret[static::BLOCKPARAM] = explode(' ', $matched[1]);
                continue;
            }

            if (preg_match('/^((\\[([^\\]]+)\\])|([^=^["\']+))=(.+)$/', $var, $m)) {
                $idx = $m[3] ? $m[3] : $m[4];
                $var = $m[5];
                // handle foo=(...)
                if (preg_match(SafeString::IS_SUBEXP_SEARCH, $var)) {
                    $ret[$idx] = static::subexpression($var, $context);
                    continue;
                }
            }

            if (!preg_match("/^(\"|\\\\')(.*)(\"|\\\\')$/", $var)) {
                // foo]  Rule 1: no starting [ or [ not start from head
                if (preg_match('/^[^\\[\\.]+[\\]\\[]/', $var)
                    // [bar  Rule 2: no ending ] or ] not in the end
                    || preg_match('/[\\[\\]][^\\]\\.]+$/', $var)
                    // ]bar. Rule 3: middle ] not before .
                    || preg_match('/\\][^\\]\\[\\.]+\\./', $var)
                    // .foo[ Rule 4: middle [ not after .
                    || preg_match('/\\.[^\\]\\[\\.]+\\[/', preg_replace('/^(..\\/)+/', '', preg_replace('/\\[[^\\]]+\\]/', '[XXX]', $var)))
                ) {
                    $context['error'][] = "Wrong variable naming as '$var' in $token !";
                } else {
                    $name = preg_replace('/(\\[.+?\\])/', '', $var);
                    // Scan for invalid charactors which not be protected by [ ]
                    // now make ( and ) pass, later fix
                    if (preg_match('/[!"#%\'*+,;<=>{|}~]/', $name)) {
                        $context['error'][] = "Wrong variable naming as '$var' in $token ! You should wrap ! \" # % & ' * + , ; < = > { | } ~ into [ ]";
                    }
                }
            }

            $var = static::getExpression($var, $context, $idx);

            if (is_string($idx)) {
                $ret[$idx] = $var;
            } else {
                $ret[$i] = $var;
                $i++;
            }
        }
        return $ret;
    }

    /**
     * Detect quote characters in a string
     *
     * @return array<string,integer>|null Expected ending string when there is a quote character
     */
    protected static function detectQuote(string $string): ?array
    {
        // begin with '(' without ending ')'
        if (preg_match('/^\([^\)]*$/', $string)) {
            return array(')', 1);
        }

        // begin with '"' without ending '"'
        if (preg_match('/^"[^"]*$/', $string)) {
            return array('"', 0);
        }

        // begin with \' without ending '
        if (preg_match('/^\\\\\'[^\']*$/', $string)) {
            return array('\'', 0);
        }

        // '="' exists without ending '"'
        if (preg_match('/^[^"]*="[^"]*$/', $string)) {
            return array('"', 0);
        }

        // '[' exists without ending ']'
        if (preg_match('/^([^"\'].+)?\\[[^\\]]*$/', $string)) {
            return array(']', 0);
        }

        // =\' exists without ending '
        if (preg_match('/^[^\']*=\\\\\'[^\']*$/', $string)) {
            return array('\'', 0);
        }

        // continue to next match when =( exists without ending )
        if (preg_match('/.+(\(+)[^\)]*$/', $string, $m)) {
            return array(')', strlen($m[1]));
        }

        return null;
    }

    /**
     * Analyze a token string and return parsed result.
     *
     * @param string $token preg_match results
     * @param array<string,array|string|integer> $context current compile context
     *
     * @return array<boolean|integer|array> Return parsed result
     *
     * @expect array('foo', 'bar') when input 'foo bar', array('flags' => array())
     * @expect array('foo', "'bar'") when input "foo 'bar'", array('flags' => array())
     * @expect array('[fo o]', '"bar"') when input '[fo o] "bar"', array('flags' => array())
     * @expect array('fo=123', 'bar="45 6"') when input 'fo=123 bar="45 6"', array('flags' => array())
     * @expect array('[fo o]=123') when input '[fo o]=123', array('flags' => array())
     * @expect array('[fo o]=123', 'bar="456"') when input '[fo o]=123 bar="456"', array('flags' => array())
     * @expect array('[fo o]="1 2 3"') when input '[fo o]="1 2 3"', array('flags' => array())
     * @expect array('foo', 'a=(foo a=(foo a="ok"))') when input 'foo a=(foo a=(foo a="ok"))', array('flags' => array())
     */
    protected static function analyze(string $token, array &$context): array
    {
        $count = preg_match_all('/(\s*)([^\s]+)/', $token, $matchedall);
        // Parse arguments and deal with "..." or [...] or (...) or \'...\' or |...|
        if ($count > 0) {
            $vars = array();
            $prev = '';
            $expect = 0;
            $quote = 0;
            $stack = 0;

            foreach ($matchedall[2] as $index => $t) {
                $detected = static::detectQuote($t);

                if ($expect === ')') {
                    if ($detected && ($detected[0] !== ')')) {
                        $quote = $detected[0];
                    }
                    if (substr($t, -1, 1) === $quote) {
                        $quote = 0;
                    }
                }

                // continue from previous match when expect something
                if ($expect) {
                    $prev .= "{$matchedall[1][$index]}$t";
                    if (($quote === 0) && ($stack > 0) && preg_match('/(.+=)*(\\(+)/', $t, $m)) {
                        $stack += strlen($m[2]);
                    }
                    // end an argument when end with expected charactor
                    if (substr($t, -1, 1) === $expect) {
                        if ($stack > 0) {
                            preg_match('/(\\)+)$/', $t, $matchedq);
                            $stack -= isset($matchedq[0]) ? strlen($matchedq[0]) : 1;
                            if ($stack > 0) {
                                continue;
                            }
                            if ($stack < 0) {
                                $context['error'][] = "Unexpected ')' in expression '$token' !!";
                                $expect = 0;
                                break;
                            }
                        }
                        $vars[] = $prev;
                        $prev = '';
                        $expect = 0;
                        continue;
                    } elseif (($expect == ']') && (strpos($t, $expect) !== false)) {
                        $t = $prev;
                        $detected = static::detectQuote($t);
                        $expect = 0;
                    } else {
                        continue;
                    }
                }


                if ($detected) {
                    $prev = $t;
                    $expect = $detected[0];
                    $stack = $detected[1];
                    continue;
                }

                // continue to next match when 'as' without ending '|'
                if (($t === 'as') && (count($vars) > 0)) {
                    $prev = '';
                    $expect = '|';
                    $stack=1;
                    continue;
                }

                $vars[] = $t;
            }

            if ($expect) {
                $context['error'][] = "Error in '$token': expect '$expect' but the token ended!!";
            }

            return $vars;
        }
        return explode(' ', $token);
    }
}
