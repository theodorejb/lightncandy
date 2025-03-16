<?php

namespace DevTheorem\Handlebars;

/**
 * @internal
 */
final class Runtime
{
    /**
     * Output debug info.
     *
     * @param string $v expression
     * @param string $f runtime function name
     * @param array<string,array|string|int> $cx render time context
     */
    public static function debug(string $v, string $f, array $cx)
    {
        // Build array of reference for call_user_func_array
        $P = func_get_args();
        $params = [];
        for ($i = 2; $i < count($P); $i++) {
            $params[] = &$P[$i];
        }
        $runtime = self::class;
        return call_user_func_array("$runtime::$f", $params);
    }

    /**
     * Throw exception for missing expression. Only used in strict mode.
     *
     * @param array<string,array|string|int> $cx render time context
     */
    public static function miss(array $cx, string $v): void
    {
        throw new \Exception("Runtime: $v does not exist");
    }

    /**
     * For {{log}} .
     *
     * @param array<string,array|string|int> $cx render time context
     */
    public static function lo(array $cx, array $v): string
    {
        error_log(var_export($v[0], true));
        return '';
    }

    /**
     * For {{#if}} and {{#unless}}.
     *
     * @param array<string,array|string|int> $cx render time context
     * @param array<array|string|int>|string|int|null $v value to be tested
     * @param bool $zero include zero as true
     *
     * @return bool Return true when the value is not null nor false.
     */
    public static function ifvar(array $cx, mixed $v, bool $zero): bool
    {
        return ($v !== null) && ($v !== false) && ($zero || ($v !== 0) && ($v !== 0.0)) && ($v !== '') && (!is_array($v) || count($v) > 0);
    }

    /**
     * For {{^var}} .
     *
     * @param array<string,array|string|int> $cx render time context
     * @param array<array|string|int>|string|int|null $v value to be tested
     *
     * @return bool Return true when the value is not null nor false.
     */
    public static function isec(array $cx, mixed $v): bool
    {
        return $v === null || $v === false || (is_array($v) && count($v) === 0);
    }

    /**
     * For {{var}} .
     *
     * @param array $cx render time context
     * @param array<array|string|int>|string|int|null $var value to be htmlencoded
     */
    public static function enc(array $cx, $var): string
    {
        if ($var instanceof SafeString) {
            return (string) $var;
        }

        return Encoder::enc($cx, $var);
    }

    /**
     * For {{var}} , do html encode just like handlebars.js .
     *
     * @param array $cx render time context
     * @param array<array|string|int>|string|int|null $var value to be htmlencoded
     *
     * @return string The htmlencoded value of the specified variable
     */
    public static function encq(array $cx, $var): string
    {
        if ($var instanceof SafeString) {
            return (string) $var;
        }

        return Encoder::encq($cx, $var);
    }

    /**
     * Get string value
     *
     * @param array<string,array|string|int> $cx render time context
     * @param array<array|string|int>|string|int|null $v value to be output
     * @param int $ex 1 to return untouched value, default is 0
     *
     * @return array<array|string|int>|string|int|null The raw value of the specified variable
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
                $ret = [];
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
     * @param array<string,array|string|int> $cx render time context
     * @param array<array|string|int>|string|int|null $v value for the section
     * @param array<string>|null $bp block parameters
     * @param array<array|string|int>|string|int|null $in input data with current scope
     * @param bool $each true when rendering #each
     * @param \Closure $cb callback function to render child context
     * @param \Closure|null $else callback function to render child context when {{else}}
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
            $ret = [];
            if ($push) {
                $cx['scopes'][] = $in;
            }
            $i = 0;
            $old_spvar = $cx['sp_vars'] ?? [];
            $cx['sp_vars'] = array_merge(['root' => $old_spvar['root'] ?? null], $old_spvar, ['_parent' => $old_spvar]);
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
                    $raw = static::m($cx, $raw, [$bp[0] => $raw]);
                }
                if (isset($bp[1])) {
                    $raw = static::m($cx, $raw, [$bp[1] => $index]);
                }
                $ret[] = $cb($cx, $raw);
            }
            if ($isObj) {
                unset($cx['sp_vars']['key']);
            } else {
                unset($cx['sp_vars']['last']);
            }
            unset($cx['sp_vars']['index'], $cx['sp_vars']['first']);

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
     * @param array<string,array|string|int> $cx render time context
     * @param array<array|string|int>|string|int|null $v value to be the new context
     * @param array<array|string|int>|\stdClass|null $in input data with current scope
     * @param array<string>|null $bp block parameters
     * @param \Closure $cb callback function to render child context
     * @param \Closure|null $else callback function to render child context when {{else}}
     */
    public static function wi(array $cx, mixed $v, ?array $bp, array|\stdClass|null $in, \Closure $cb, ?\Closure $else = null): string
    {
        if (isset($bp[0])) {
            $v = static::m($cx, $v, [$bp[0] => $v]);
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
     * @param array<string,array|string|int> $cx render time context
     * @param array<array|string|int>|string|int|null $a the context to be merged
     * @param array<array|string|int>|string|int|null $b the new context to overwrite
     *
     * @return array<array|string|int>|string|int the merged context object
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
     * @param array<string,array|string|int> $cx render time context
     * @param string $p partial name
     * @param array<array|string|int>|string|int|null $v value to be the new context
     *
     */
    public static function p(array $cx, string $p, $v, int $pid, $sp = ''): string
    {
        $pp = ($p === '@partial-block') ? $p . ($pid > 0 ? $pid : $cx['partialid']) : $p;

        if (!isset($cx['partials'][$pp])) {
            throw new \Exception("Runtime: the partial $p could not be found");
        }

        $cx['partialid'] = ($p === '@partial-block') ? ($pid > 0 ? $pid : ($cx['partialid'] > 0 ? $cx['partialid'] - 1 : 0)) : $pid;

        return $cx['partials'][$pp]($cx, static::m($cx, $v[0][0], $v[1]), $sp);
    }

    /**
     * For {{#* inlinepartial}} .
     *
     * @param array<string,array|string|int> $cx render time context
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
     * @param array<string,array|string|int> $_this current rendering context for the helper
     */
    public static function hbch(array &$cx, string $ch, array $vars, mixed &$_this): mixed
    {
        if (isset($cx['blparam'][0][$ch])) {
            return $cx['blparam'][0][$ch];
        }

        $options = new HelperOptions(
            name: $ch,
            hash: $vars[1],
            fn: function () { return ''; },
            inverse: function () { return ''; },
            blockParams: 0,
            scope: $_this,
            data: $cx['sp_vars'],
        );

        return static::exch($cx, $ch, $vars, $options);
    }

    /**
     * For block custom helpers.
     *
     * @param array<string,array|string|int> $cx render time context
     * @param string $ch the name of custom helper to be executed
     * @param array<array|string|int> $vars variables for the helper
     * @param array<string,array|string|int> $_this current rendering context for the helper
     * @param bool $inverted the logic will be inverted
     * @param \Closure|null $cb callback function to render child context
     * @param \Closure|null $else callback function to render child context when {{else}}
     */
    public static function hbbch(array &$cx, string $ch, array $vars, mixed &$_this, bool $inverted, ?\Closure $cb, ?\Closure $else = null): mixed
    {
        $blockParams = 0;
        $data = &$cx['sp_vars'];

        if (isset($vars[2])) {
            $blockParams = count($vars[2]);
        }

        // invert the logic
        if ($inverted) {
            $tmp = $else;
            $else = $cb;
            $cb = $tmp;
        }

        $fn = function ($context = null, $data = null) use ($cx, $_this, $cb, $vars) {
            $old_spvar = $cx['sp_vars'];
            if (isset($data['data'])) {
                $cx['sp_vars'] = array_merge(['root' => $old_spvar['root']], $data['data'], ['_parent' => $old_spvar]);
            }

            $ex = false;
            if (isset($data['blockParams']) && isset($vars[2])) {
                $ex = array_combine($vars[2], array_slice($data['blockParams'], 0, count($vars[2])));
                array_unshift($cx['blparam'], $ex);
            } elseif (isset($cx['blparam'][0])) {
                $ex = $cx['blparam'][0];
            }

            if ($context === null) {
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
            $inverse = function ($context = null) use ($cx, $_this, $else) {
                if ($context === null) {
                    $ret = $else($cx, $_this);
                } else {
                    $cx['scopes'][] = $_this;
                    $ret = $else($cx, $context);
                    array_pop($cx['scopes']);
                }
                return $ret;
            };
        } else {
            $inverse = function () {
                return '';
            };
        }

        $options = new HelperOptions(
            name: $ch,
            hash: $vars[1],
            fn: $fn,
            inverse: $inverse,
            blockParams: $blockParams,
            scope: $_this,
            data: $data,
        );

        return static::exch($cx, $ch, $vars, $options);
    }

    /**
     * Execute custom helper with prepared options
     *
     * @param array<string,array|string|int> $cx render time context
     * @param string $ch the name of custom helper to be executed
     * @param array<array|string|int> $vars variables for the helper
     */
    public static function exch(array $cx, string $ch, array $vars, HelperOptions $options): mixed
    {
        $args = $vars[0];
        $args[] = $options;

        try {
            return ($cx['helpers'][$ch])(...$args);
        } catch (\Throwable $e) {
            throw new \Exception("Runtime: call custom helper '$ch' error: " . $e->getMessage());
        }
    }
}
