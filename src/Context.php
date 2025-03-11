<?php

namespace DevTheorem\Handlebars;

/**
 * @internal
 */
final class Context
{
    /**
     * Create a context from options
     *
     * @return array<string,array|string|int> Context from options
     */
    public static function create(Options $options): array
    {
        $context = array(
            'flags' => array(
                'noesc' => (int) $options->noEscape,
                'noind' => (int) $options->preventIndent,
                'debug' => (int) $options->strict,
                'partnc' => (int) $options->explicitPartialContext,
                'nostd' => (int) $options->ignoreStandalone,
                'knohlp' => (int) $options->knownHelpersOnly,
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
            'partials' => $options->partials,
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
     *
     * @return array<string,array|string|int> context with generated helper table
     */
    protected static function updateHelperTable(array &$context, Options $options): array
    {
        foreach ($options->helpers as $name => $func) {
            $tn = is_int($name) ? $func : $name;
            if (is_callable($func)) {
                $context['helpers'][$tn] = $func;
            } else {
                if (is_array($func)) {
                    $context['error'][] = "I found an array in helpers with key as $name, please fix it.";
                } else {
                    $context['error'][] = "You provide a custom helper named as '$tn' in options['helpers'], but the function $func() is not defined!";
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
