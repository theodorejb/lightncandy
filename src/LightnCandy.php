<?php

namespace LightnCandy;

class LightnCandy extends Flags
{
    protected static array $lastContext;
    public static array $lastParsed;

    /**
     * Compiles a template so it can be executed immediately.
     */
    public static function compile(string $template, array $options = []): \Closure
    {
        return self::template(self::precompile($template, $options));
    }

    /**
     * Precompiles a handlebars template into PHP code which can be executed later.
     */
    public static function precompile(string $template, array $options = []): string
    {
        $context = Context::create($options);
        static::handleError($context);

        $code = Compiler::compileTemplate($context, SafeString::escapeTemplate($template));
        static::$lastParsed = Compiler::$lastParsed;
        static::handleError($context);

        // return full PHP render code as string
        return Compiler::composePHPRender($context, $code);
    }

    /**
     * Sets up a template that was precompiled with precompile().
     */
    public static function template(string $templateSpec): \Closure
    {
        return eval($templateSpec);
    }

    /**
     * Handle exists error and return error status.
     *
     * @param array<string,array|string|int> $context Current context of compiler progress.
     *
     * @throws \Exception
     */
    protected static function handleError(array &$context): void
    {
        static::$lastContext = $context;

        if (count($context['error'])) {
            throw new \Exception(implode("\n", $context['error']));
        }
    }

    /**
     * Get last compiler context.
     *
     * @return array<string,array|string|int> Context data
     */
    public static function getContext(): array
    {
        return static::$lastContext;
    }
}
