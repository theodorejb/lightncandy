<?php

namespace DevTheorem\Handlebars;

class Partial
{
    public static string $TMP_JS_FUNCTION_STR = "!!\aFuNcTiOn\a!!";

    /**
     * Include all partials when using dynamic partials
     */
    public static function handleDynamic(Context $context): void
    {
        if ($context->usedDynPartial == 0) {
            return;
        }

        foreach ($context->partials as $name => $code) {
            static::read($context, $name);
        }
    }

    /**
     * Read partial file content as string and store in context
     */
    public static function read(Context $context, string $name): void
    {
        $isPB = ($name === '@partial-block');

        if (isset($context->usedPartial[$name])) {
            return;
        }

        $cnt = static::resolve($context, $name);

        if ($cnt !== null) {
            $context->usedPartial[$name] = SafeString::escapeTemplate($cnt);
            static::compileDynamic($context, $name);
            return;
        }

        if (!$isPB) {
            $context->error[] = "The partial $name could not be found";
        }
    }

    /**
     * resolve partial, return the partial content
     *
     * @return string|null $content partial content
     */
    public static function resolve(Context $context, string &$name): ?string
    {
        if ($name === '@partial-block') {
            $name = "@partial-block{$context->usedPBlock}";
        }
        if (isset($context->partials[$name])) {
            return $context->partials[$name];
        }
        return null;
    }

    /**
     * compile partial as closure, stored in context
     *
     * @return string|null $code compiled PHP code when success
     */
    public static function compileDynamic(Context $context, string $name): ?string
    {
        $func = static::compile($context, $context->usedPartial[$name], $name);

        if (!isset($context->partialCode[$name]) && $func) {
            $context->partialCode[$name] = "'$name' => $func";
        }

        return $func;
    }

    /**
     * compile a template into a closure function
     */
    public static function compile(Context $context, string $template, string $name): ?string
    {
        if (end($context->partialStack) === $name && str_starts_with($name, '@partial-block')) {
            return null;
        }

        $tmpContext = clone $context;
        $tmpContext->inlinePartial = [];
        $tmpContext->partialBlock = [];
        $tmpContext->partialStack[] = $name;

        $code = Compiler::compileTemplate($tmpContext, str_replace('function', static::$TMP_JS_FUNCTION_STR, $template));
        $context->merge($tmpContext);

        if (!$context->options->preventIndent) {
            $sp = ', $sp';
            $code = preg_replace('/^/m', "'{$context->ops['separator']}\$sp{$context->ops['separator']}'", $code);
            // callbacks inside partial should be aware of $sp
            $code = preg_replace('/\bfunction\s*\(([^\(]*?)\)\s*{/', 'function(\\1)use($sp){', $code);
            $code = preg_replace('/function\(\$cx, \$in, \$sp\)use\(\$sp\){/', 'function($cx, $in)use($sp){', $code);
        } else {
            $sp = '';
        }
        $code = str_replace(static::$TMP_JS_FUNCTION_STR, 'function', $code);
        return "function (\$cx, \$in{$sp}) {{$context->ops['op_start']}'$code'{$context->ops['op_end']}}";
    }
}
