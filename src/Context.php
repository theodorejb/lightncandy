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
        $context = [
            'flags' => [
                'noesc' => (int) $options->noEscape,
                'noind' => (int) $options->preventIndent,
                'debug' => (int) $options->strict,
                'partnc' => (int) $options->explicitPartialContext,
                'nostd' => (int) $options->ignoreStandalone,
                'knohlp' => (int) $options->knownHelpersOnly,
            ],
            'level' => 0,
            'stack' => [],
            'currentToken' => null,
            'error' => [],
            'elselvl' => [],
            'elsechain' => false,
            'tokens' => [
                'ahead' => false,
                'current' => 0,
                'count' => 0,
                'partialind' => '',
            ],
            'usedPartial' => [],
            'partialStack' => [],
            'partialCode' => [],
            'usedFeature' => [
                'dynpartial' => 0,
                'pblock' => 0,
            ],
            'usedHelpers' => [],
            'compile' => false,
            'parsed' => [],
            'partials' => $options->partials,
            'partialblock' => [],
            'inlinepartial' => [],
            'helpers' => [],
            'rawblock' => false,
        ];

        $context['ops'] = [
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
        ];

        foreach ($options->helpers as $name => $func) {
            $tn = is_int($name) ? $func : $name;
            if (is_callable($func)) {
                $context['helpers'][$tn] = $func;
            } else {
                if (is_array($func)) {
                    $context['error'][] = "Custom helper $name must be a function, not an array.";
                } else {
                    $context['error'][] = "Custom helper '$tn' must be a function.";
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
