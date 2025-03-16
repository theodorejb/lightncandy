<?php

namespace DevTheorem\Handlebars;

/**
 * @internal
 */
final class Exporter
{
    /**
     * Get PHP code string from a closure of function as string
     */
    public static function closure(\Closure $closure): string
    {
        $ref = new \ReflectionFunction($closure);
        $code = static::getFunctionCode($ref);

        return preg_replace('/^.*?function(\s+[^\s\\(]+?)?\s*\\((.+)\\}.*?\s*$/s', 'function($2}', $code);
    }

    /**
     * Export required custom helper functions
     */
    public static function helpers(Context $context): string
    {
        $ret = '';
        foreach ($context->helpers as $name => $func) {
            if (!isset($context->usedHelpers[$name])) {
                continue;
            }
            if ($func instanceof \Closure) {
                $ret .= ("            '$name' => " . static::closure($func) . ",\n");
                continue;
            }
            $ret .= "            '$name' => '$func',\n";
        }

        return "[$ret]";
    }

    public static function getFunctionCode(\ReflectionFunction $refobj): string
    {
        $fname = $refobj->getFileName();
        $lines = file_get_contents($fname);
        $file = new \SplFileObject($fname);

        $start = $refobj->getStartLine() - 1;
        $end = $refobj->getEndLine();

        $file->seek($start);
        $spos = $file->ftell();
        $file->seek($end);
        $epos = $file->ftell();

        return substr($lines, $spos, $epos - $spos);
    }
}
