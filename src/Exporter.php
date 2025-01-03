<?php

namespace LightnCandy;

class Exporter
{
    /**
     * Get PHP code string from a closure of function as string
     *
     * @param array<string,array|string|integer> $context current compile context
     *
     * @expect 'function($a) {return;}' when input array('flags' => array()),  function ($a) {return;}
     * @expect 'function($a) {return;}' when input array('flags' => array()),   function ($a) {return;}
     */
    protected static function closure(array $context, \Closure $closure): string
    {
        $ref = new \ReflectionFunction($closure);
        $meta = static::getMeta($ref);

        return preg_replace('/^.*?function(\s+[^\s\\(]+?)?\s*\\((.+)\\}.*?\s*$/s', 'function($2}', $meta['code']);
    }

    /**
     * Export required custom helper functions
     *
     * @param array<string,array|string|integer> $context current compile context
     */
    public static function helpers(array $context): string
    {
        $ret = '';
        foreach ($context['helpers'] as $name => $func) {
            if (!isset($context['usedCount']['helpers'][$name])) {
                continue;
            }
            if ($func instanceof \Closure) {
                $ret .= ("            '$name' => " . static::closure($context, $func) . ",\n");
                continue;
            }
            $ret .= "            '$name' => '$func',\n";
        }

        return "[$ret]";
    }

    public static function getMeta(\ReflectionFunction $refobj): array
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
        unset($file);

        return array(
            'name' => $refobj->getName(),
            'code' => substr($lines, $spos, $epos - $spos)
        );
    }
}
