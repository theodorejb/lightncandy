<?php

namespace LightnCandy;

/**
 * LightnCandy class for compiled PHP runtime.
 */
class Runtime
{
    /**
     * Output debug info.
     *
     * @param string $v expression
     * @param string $f runtime function name
     * @param array<string,array|string|integer> $cx render time context
     */
    public static function debug(string $v, string $f, array $cx)
    {
        // Build array of reference for call_user_func_array
        $P = func_get_args();
        $params = array();
        for ($i=2;$i<count($P);$i++) {
            $params[] = &$P[$i];
        }
        $runtime = self::class;
        $r = call_user_func_array(($cx['funcs'][$f] ?? "{$runtime}::$f"), $params);

        return $r;
    }

    /**
     * Throw exception for missing expression. Only used in strict mode.
     *
     * @param array<string,array|string|integer> $cx render time context
     */
    public static function miss(array $cx, string $v): void
    {
        throw new \Exception("Runtime: $v does not exist");
    }

    /**
     * For {{log}} .
     *
     * @param array<string,array|string|integer> $cx render time context
     */
    public static function lo(array $cx, array $v): string
    {
        error_log(var_export($v[0], true));
        return '';
    }

    /**
     * For {{#if}} and {{#unless}}.
     *
     * @param array<string,array|string|integer> $cx render time context
     * @param array<array|string|integer>|string|integer|null $v value to be tested
     * @param boolean $zero include zero as true
     *
     * @return boolean Return true when the value is not null nor false.
     *
     * @expect false when input array(), null, false
     * @expect false when input array(), 0, false
     * @expect true when input array(), 0, true
     * @expect false when input array(), false, false
     * @expect true when input array(), true, false
     * @expect true when input array(), 1, false
     * @expect false when input array(), '', false
     * @expect true when input array(), '0', false
     * @expect false when input array(), array(), false
     * @expect true when input array(), array(''), false
     * @expect true when input array(), array(0), false
     */
    public static function ifvar(array $cx, mixed $v, bool $zero): bool
    {
        return ($v !== null) && ($v !== false) && ($zero || ($v !== 0) && ($v !== 0.0)) && ($v !== '') && (!is_array($v) || count($v) > 0);
    }

    /**
     * For {{^var}} .
     *
     * @param array<string,array|string|integer> $cx render time context
     * @param array<array|string|integer>|string|integer|null $v value to be tested
     *
     * @return boolean Return true when the value is not null nor false.
     *
     * @expect true when input array(), null
     * @expect false when input array(), 0
     * @expect true when input array(), false
     * @expect false when input array(), 'false'
     * @expect true when input array(), array()
     * @expect false when input array(), array('1')
     */
    public static function isec(array $cx, mixed $v): bool
    {
        return ($v === null) || ($v === false) || (is_array($v) && (count($v) === 0));
    }

    /**
     * For {{var}} .
     *
     * @param array $cx render time context
     * @param array<array|string|integer>|string|integer|null $var value to be htmlencoded
     *
     * @expect 'a' when input array('flags' => array()), 'a'
     * @expect 'a&amp;b' when input array('flags' => array()), 'a&b'
     * @expect 'a&#039;b' when input array('flags' => array()), 'a\'b'
     * @expect 'a&b' when input [], new \LightnCandy\SafeString('a&b')
     */
    public static function enc(array $cx, $var): string
    {
        if ($var instanceof SafeString) {
            return (string)$var;
        }

        return Encoder::enc($cx, $var);
    }

    /**
     * For {{var}} , do html encode just like handlebars.js .
     *
     * @param array $cx render time context for lightncandy
     * @param array<array|string|integer>|string|integer|null $var value to be htmlencoded
     *
     * @return string The htmlencoded value of the specified variable
     *
     * @expect 'a' when input array('flags' => array()), 'a'
     * @expect 'a&amp;b' when input array('flags' => array()), 'a&b'
     * @expect 'a&#x27;b' when input array('flags' => array()), 'a\'b'
     * @expect '&#x60;a&#x27;b' when input array('flags' => array()), '`a\'b'
     */
    public static function encq(array $cx, $var): string
    {
        if ($var instanceof SafeString) {
            return (string)$var;
        }

        return Encoder::encq($cx, $var);
    }

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
    public static function raw(array $cx, $v, int $ex = 0)
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
     * For {{#var}} or {{#each}} .
     *
     * @param array<string,array|string|integer> $cx render time context
     * @param array<array|string|integer>|string|integer|null $v value for the section
     * @param array<string>|null $bp block parameters
     * @param array<array|string|integer>|string|integer|null $in input data with current scope
     * @param boolean $each true when rendering #each
     * @param \Closure $cb callback function to render child context
     * @param \Closure|null $else callback function to render child context when {{else}}
     *
     * @expect '' when input array('flags' => array()), false, null, false, false, function () {return 'A';}
     * @expect '' when input array('flags' => array()), null, null, null, false, function () {return 'A';}
     * @expect 'A' when input array('flags' => array()), true, null, true, false, function () {return 'A';}
     * @expect 'A' when input array('flags' => array()), 0, null, 0, false, function () {return 'A';}
     * @expect '-a=' when input array('scopes' => array(), 'flags' => array()), array('a'), null, array('a'), false, function ($c, $i) {return "-$i=";}
     * @expect '-a=-b=' when input array('scopes' => array(), 'flags' => array()), array('a','b'), null, array('a','b'), false, function ($c, $i) {return "-$i=";}
     * @expect '' when input array('scopes' => array(), 'flags' => array()), 'abc', null, 'abc', true, function ($c, $i) {return "-$i=";}
     * @expect '-b=' when input array('scopes' => array(), 'flags' => array()), array('a' => 'b'), null, array('a' => 'b'), true, function ($c, $i) {return "-$i=";}
     * @expect 'b' when input array('flags' => array()), 'b', null, 'b', false, function ($c, $i) {return print_r($i, true);}
     * @expect '1' when input array('flags' => array()), 1, null, 1, false, function ($c, $i) {return print_r($i, true);}
     * @expect '0' when input array('flags' => array()), 0, null, 0, false, function ($c, $i) {return print_r($i, true);}
     * @expect '{"b":"c"}' when input array('flags' => array()), array('b' => 'c'), null, array('b' => 'c'), false, function ($c, $i) {return json_encode($i);}
     * @expect 'inv' when input array('flags' => array()), array(), null, 0, true, function ($c, $i) {return 'cb';}, function ($c, $i) {return 'inv';}
     * @expect 'inv' when input array('flags' => array()), array(), null, 0, false, function ($c, $i) {return 'cb';}, function ($c, $i) {return 'inv';}
     * @expect 'inv' when input array('flags' => array()), false, null, 0, true, function ($c, $i) {return 'cb';}, function ($c, $i) {return 'inv';}
     * @expect 'inv' when input array('flags' => array()), false, null, 0, false, function ($c, $i) {return 'cb';}, function ($c, $i) {return 'inv';}
     * @expect 'inv' when input array('flags' => array()), '', null, 0, true, function ($c, $i) {return 'cb';}, function ($c, $i) {return 'inv';}
     * @expect 'cb' when input array('flags' => array()), '', null, 0, false, function ($c, $i) {return 'cb';}, function ($c, $i) {return 'inv';}
     * @expect 'inv' when input array('flags' => array()), 0, null, 0, true, function ($c, $i) {return 'cb';}, function ($c, $i) {return 'inv';}
     * @expect 'cb' when input array('flags' => array()), 0, null, 0, false, function ($c, $i) {return 'cb';}, function ($c, $i) {return 'inv';}
     * @expect 'inv' when input array('flags' => array()), new stdClass, null, 0, true, function ($c, $i) {return 'cb';}, function ($c, $i) {return 'inv';}
     * @expect 'cb' when input array('flags' => array()), new stdClass, null, 0, false, function ($c, $i) {return 'cb';}, function ($c, $i) {return 'inv';}
     * @expect '268' when input array('scopes' => array(), 'flags' => array(), 'sp_vars'=>array('root' => 0)), array(1,3,4), null, 0, false, function ($c, $i) {return $i * 2;}
     * @expect '038' when input array('scopes' => array(), 'flags' => array(), 'sp_vars'=>array('root' => 0)), array(1,3,'a'=>4), null, 0, true, function ($c, $i) {return $i * $c['sp_vars']['index'];}
     */
    public static function sec(array $cx, mixed $v, ?array $bp, mixed $in, bool $each, \Closure $cb, ?\Closure $else = null): string
    {
        $push = ($in !== $v) || $each;

        $isAry = is_array($v) || ($v instanceof \ArrayObject);
        $isTrav = $v instanceof \Traversable;
        $loop = $each;
        $keys = null;
        $last = null;
        $isObj = false;

        if ($isAry && $else !== null && count($v) === 0) {
            return $else($cx, $in);
        }

        // #var, detect input type is object or not
        if (!$loop && $isAry) {
            $keys = array_keys($v);
            $loop = (count(array_diff_key($v, array_keys($keys))) == 0);
            $isObj = !$loop;
        }

        if (($loop && $isAry) || $isTrav) {
            if ($each && !$isTrav) {
                // Detect input type is object or not when never done once
                if ($keys == null) {
                    $keys = array_keys($v);
                    $isObj = (count(array_diff_key($v, array_keys($keys))) > 0);
                }
            }
            $ret = array();
            if ($push) {
                $cx['scopes'][] = $in;
            }
            $i = 0;
            $old_spvar = $cx['sp_vars'] ?? [];
            $cx['sp_vars'] = array_merge(array('root' => $old_spvar['root'] ?? null), $old_spvar, array('_parent' => $old_spvar));
            if (!$isTrav) {
                $last = count($keys) - 1;
            }

            $isSparceArray = $isObj && (count(array_filter(array_keys($v), 'is_string')) == 0);
            foreach ($v as $index => $raw) {
                $cx['sp_vars']['first'] = ($i === 0);
                $cx['sp_vars']['last'] = ($i == $last);
                $cx['sp_vars']['key'] = $index;
                $cx['sp_vars']['index'] = $isSparceArray ? $index : $i;
                $i++;
                if (isset($bp[0])) {
                    $raw = static::m($cx, $raw, array($bp[0] => $raw));
                }
                if (isset($bp[1])) {
                    $raw = static::m($cx, $raw, array($bp[1] => $index));
                }
                $ret[] = $cb($cx, $raw);
            }
            if ($isObj) {
                unset($cx['sp_vars']['key']);
            } else {
                unset($cx['sp_vars']['last']);
            }
            unset($cx['sp_vars']['index']);
            unset($cx['sp_vars']['first']);
            if ($push) {
                array_pop($cx['scopes']);
            }
            return join('', $ret);
        }
        if ($each) {
            if ($else !== null) {
                return $else($cx, $in);
            }
            return '';
        }
        if ($isAry) {
            if ($push) {
                $cx['scopes'][] = $in;
            }
            $ret = $cb($cx, $v);
            if ($push) {
                array_pop($cx['scopes']);
            }
            return $ret;
        }

        if ($v === true) {
            return $cb($cx, $in);
        }

        if (($v !== null) && ($v !== false)) {
            return $cb($cx, $v);
        }

        if ($else !== null) {
            return $else($cx, $in);
        }

        return '';
    }

    /**
     * For {{#with}} .
     *
     * @param array<string,array|string|integer> $cx render time context
     * @param array<array|string|integer>|string|integer|null $v value to be the new context
     * @param array<array|string|integer>|\stdClass|null $in input data with current scope
     * @param array<string>|null $bp block parameters
     * @param \Closure $cb callback function to render child context
     * @param \Closure|null $else callback function to render child context when {{else}}
     *
     * @expect '' when input array(), false, null, new \stdClass(), function () {return 'A';}
     * @expect '' when input array(), null, null, null, function () {return 'A';}
     * @expect '{"a":"b"}' when input array(), array('a'=>'b'), null, array('a'=>'c'), function ($c, $i) {return json_encode($i);}
     * @expect '-b=' when input array(), 'b', null, array('a'=>'b'), function ($c, $i) {return "-$i=";}
     */
    public static function wi(array $cx, mixed $v, ?array $bp, array|\stdClass|null $in, \Closure $cb, ?\Closure $else = null): string
    {
        if (isset($bp[0])) {
            $v = static::m($cx, $v, array($bp[0] => $v));
        }
        if (($v === false) || ($v === null) || (is_array($v) && (count($v) === 0))) {
            return $else ? $else($cx, $in) : '';
        }
        if ($v === $in) {
            $ret = $cb($cx, $v);
        } else {
            $cx['scopes'][] = $in;
            $ret = $cb($cx, $v);
            array_pop($cx['scopes']);
        }
        return $ret;
    }

    /**
     * Get merged context.
     *
     * @param array<string,array|string|integer> $cx render time context
     * @param array<array|string|integer>|string|integer|null $a the context to be merged
     * @param array<array|string|integer>|string|integer|null $b the new context to overwrite
     *
     * @return array<array|string|integer>|string|integer the merged context object
     *
     */
    public static function m(array $cx, $a, $b)
    {
        if (is_array($b)) {
            if ($a === null) {
                return $b;
            } elseif (is_array($a)) {
                return array_merge($a, $b);
            } else {
                if (!is_object($a)) {
                    $a = new StringObject($a);
                }
                foreach ($b as $i => $v) {
                    $a->$i = $v;
                }
            }
        }
        return $a;
    }

    /**
     * For {{> partial}} .
     *
     * @param array<string,array|string|integer> $cx render time context
     * @param string $p partial name
     * @param array<array|string|integer>|string|integer|null $v value to be the new context
     *
     */
    public static function p(array $cx, string $p, $v, int $pid, $sp = ''): string
    {
        $pp = ($p === '@partial-block') ? "$p" . ($pid > 0 ? $pid : $cx['partialid']) : $p;

        if (!isset($cx['partials'][$pp])) {
            throw new \Exception("The partial $p could not be found");
        }

        $cx['partialid'] = ($p === '@partial-block') ? (($pid > 0) ? $pid : (($cx['partialid'] > 0) ? $cx['partialid'] - 1 : 0)) : $pid;

        return call_user_func($cx['partials'][$pp], $cx, static::m($cx, $v[0][0], $v[1]), $sp);
    }

    /**
     * For {{#* inlinepartial}} .
     *
     * @param array<string,array|string|integer> $cx render time context
     * @param string $p partial name
     * @param \Closure $code the compiled partial code
     *
     */
    public static function in(array &$cx, string $p, \Closure $code)
    {
        $cx['partials'][$p] = $code;
    }

    /**
     * For single custom helpers.
     *
     * @param array<string,array|string|int> $cx render time context
     * @param string $ch the name of custom helper to be executed
     * @param array<array|string|int> $vars variables for the helper
     * @param string $op the name of variable resolver. should be one of: 'raw', 'enc', or 'encq'.
     * @param array<string,array|string|integer> $_this current rendering context for the helper
     */
    public static function hbch(array &$cx, string $ch, array $vars, string $op, mixed &$_this): mixed
    {
        if (isset($cx['blparam'][0][$ch])) {
            return $cx['blparam'][0][$ch];
        }

        $options = array(
            'name' => $ch,
            'hash' => $vars[1],
            'contexts' => count($cx['scopes']) ? $cx['scopes'] : array(null),
            'fn.blockParams' => 0,
            '_this' => &$_this
        );

        $options['data'] = &$cx['sp_vars'];

        return static::exch($cx, $ch, $vars, $options);
    }

    /**
     * For block custom helpers.
     *
     * @param array<string,array|string|integer> $cx render time context
     * @param string $ch the name of custom helper to be executed
     * @param array<array|string|integer> $vars variables for the helper
     * @param array<string,array|string|integer> $_this current rendering context for the helper
     * @param boolean $inverted the logic will be inverted
     * @param \Closure|null $cb callback function to render child context
     * @param \Closure|null $else callback function to render child context when {{else}}
     */
    public static function hbbch(array &$cx, string $ch, array $vars, mixed &$_this, bool $inverted, ?\Closure $cb, ?\Closure $else = null): mixed
    {
        $options = array(
            'name' => $ch,
            'hash' => $vars[1],
            'contexts' => count($cx['scopes']) ? $cx['scopes'] : array(null),
            'fn.blockParams' => 0,
            '_this' => &$_this,
        );

        $options['data'] = &$cx['sp_vars'];

        if (isset($vars[2])) {
            $options['fn.blockParams'] = count($vars[2]);
        }

        // $invert the logic
        if ($inverted) {
            $tmp = $else;
            $else = $cb;
            $cb = $tmp;
        }

        $options['fn'] = function ($context = '_NO_INPUT_HERE_', $data = null) use ($cx, &$_this, $cb, $vars) {
            $old_spvar = $cx['sp_vars'];
            if (isset($data['data'])) {
                $cx['sp_vars'] = array_merge(array('root' => $old_spvar['root']), $data['data'], array('_parent' => $old_spvar));
            }
            $ex = false;
            if (isset($data['blockParams']) && isset($vars[2])) {
                $ex = array_combine($vars[2], array_slice($data['blockParams'], 0, count($vars[2])));
                array_unshift($cx['blparam'], $ex);
            } elseif (isset($cx['blparam'][0])) {
                $ex = $cx['blparam'][0];
            }
            if (($context === '_NO_INPUT_HERE_') || ($context === $_this)) {
                $ret = $cb($cx, is_array($ex) ? static::m($cx, $_this, $ex) : $_this);
            } else {
                $cx['scopes'][] = $_this;
                $ret = $cb($cx, is_array($ex) ? static::m($cx, $context, $ex) : $context);
                array_pop($cx['scopes']);
            }
            if (isset($data['data'])) {
                $cx['sp_vars'] = $old_spvar;
            }
            return $ret;
        };

        if ($else) {
            $options['inverse'] = function ($context = '_NO_INPUT_HERE_') use ($cx, $_this, $else) {
                if ($context === '_NO_INPUT_HERE_') {
                    $ret = $else($cx, $_this);
                } else {
                    $cx['scopes'][] = $_this;
                    $ret = $else($cx, $context);
                    array_pop($cx['scopes']);
                }
                return $ret;
            };
        } else {
            $options['inverse'] = function () {
                return '';
            };
        }

        return static::exch($cx, $ch, $vars, $options);
    }

    /**
     * Execute custom helper with prepared options
     *
     * @param array<string,array|string|integer> $cx render time context
     * @param string $ch the name of custom helper to be executed
     * @param array<array|string|int> $vars variables for the helper
     * @param array<string,array|string|integer> $options the options object
     */
    public static function exch(array $cx, string $ch, array $vars, array &$options): mixed
    {
        $args = $vars[0];
        $args[] = &$options;

        try {
            return call_user_func_array($cx['helpers'][$ch], $args);
        } catch (\Throwable $e) {
            throw new \Exception("Runtime: call custom helper '$ch' error: " . $e->getMessage());
        }
    }
}
