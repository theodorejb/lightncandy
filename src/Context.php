<?php

namespace LightnCandy;

/**
 * @internal
 */
final class Context
{
    /**
     * Create a context from options
     *
     * @param array<string,array|string|int> $options input options
     *
     * @return array<string,array|string|int> Context from options
     */
    public static function create(array $options): array
    {
        $flags = $options['flags'] ?? 0;

        $context = array(
            'flags' => array(
                'noesc' => $flags & Flags::FLAG_NOESCAPE,
                'noind' => $flags & Flags::FLAG_PREVENTINDENT,
                'debug' => $flags & Flags::FLAG_STRICT,
                'partnc' => $flags & Flags::FLAG_PARTIALNEWCONTEXT,
                'nostd' => $flags & Flags::FLAG_IGNORESTANDALONE,
                'knohlp' => $flags & Flags::FLAG_KNOWNHELPERSONLY,
            ),
            'level' => 0,
            'stack' => array(),
            'currentToken' => null,
            'error' => array(),
            'elselvl' => array(),
            'elsechain' => false,
            'tokens' => array(
                'standalone' => true,
                'ahead' => false,
                'current' => 0,
                'count' => 0,
                'partialind' => '',
            ),
            'usedPartial' => array(),
            'partialStack' => array(),
            'partialCode' => array(),
            'usedFeature' => array(
                'dynpartial' => 0,
                'pblock' => 0,
            ),
            'usedHelpers' => [],
            'compile' => false,
            'parsed' => array(),
            'partials' => (isset($options['partials']) && is_array($options['partials'])) ? $options['partials'] : array(),
            'partialblock' => array(),
            'inlinepartial' => array(),
            'helpers' => array(),
            'rawblock' => false,
        );

        $context['ops'] = array(
            'separator' => '.',
            'f_start' => 'return ',
            'f_end' => ';',
            'op_start' => 'return ',
            'op_end' => ';',
            'cnd_start' => '.(',
            'cnd_then' => ' ? ',
            'cnd_else' => ' : ',
            'cnd_end' => ').',
            'cnd_nend' => ')',
        );

        $context['ops']['enc'] = 'encq';
        static::updateHelperTable($context, $options);

        return $context;
    }

    /**
     * update specific custom helper table from options
     *
     * @param array<string,array|string|int> $context prepared context
     * @param array<string,array|string|int> $options input options
     * @param string $tname helper table name
     *
     * @return array<string,array|string|int> context with generated helper table
     */
    protected static function updateHelperTable(array &$context, array $options, string $tname = 'helpers'): array
    {
        if (isset($options[$tname]) && is_array($options[$tname])) {
            foreach ($options[$tname] as $name => $func) {
                $tn = is_int($name) ? $func : $name;
                if (is_callable($func)) {
                    $context[$tname][$tn] = $func;
                } else {
                    if (is_array($func)) {
                        $context['error'][] = "I found an array in $tname with key as $name, please fix it.";
                    } else {
                        $context['error'][] = "You provide a custom helper named as '$tn' in options['$tname'], but the function $func() is not defined!";
                    }
                }
            }
        }
        return $context;
    }

    /**
     * Merge a context into another
     *
     * @param array<string,array|string|int> $context master context
     * @param array<string,array|string|int> $tmp another context will be overwritten into master context
     */
    public static function merge(array &$context, array $tmp): void
    {
        $context['error'] = $tmp['error'];
        $context['helpers'] = $tmp['helpers'];
        $context['partials'] = $tmp['partials'];
        $context['partialCode'] = $tmp['partialCode'];
        $context['partialStack'] = $tmp['partialStack'];
        $context['usedHelpers'] = $tmp['usedHelpers'];
        $context['usedFeature'] = $tmp['usedFeature'];
        $context['usedPartial'] = $tmp['usedPartial'];
    }
}
