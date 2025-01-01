<?php

namespace LightnCandy;

class LightnCandy extends Flags
{
    protected static array $lastContext;
    public static array $lastParsed;

    /**
     * Compile handlebars template into PHP code.
     *
     * @param string $template handlebars template string
     * @param array<string,array|string|integer> $options LightnCandy compile time and run time options
     */
    public static function compile(string $template, array $options = []): string
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
     * Handle exists error and return error status.
     *
     * @param array<string,array|string|integer> $context Current context of compiler progress.
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
     * @return array<string,array|string|integer> Context data
     */
    public static function getContext(): array
    {
        return static::$lastContext;
    }

    /**
     * Get a working render function by a string of PHP code. This method may require php setting allow_url_include=1 and allow_url_fopen=1 , or access right to tmp file system.
     *
     * @deprecated
     */
    public static function prepare(string $php): \Closure
    {
        $php = "<?php $php ?>";

        if (!ini_get('allow_url_include') || !ini_get('allow_url_fopen')) {
            $tmpDir = sys_get_temp_dir();
            $fn = tempnam($tmpDir, 'lci_');
            if (!$fn) {
                throw new \Exception("Can not generate tmp file under $tmpDir");
            }
            if (!file_put_contents($fn, $php)) {
                throw new \Exception("Can not include saved temp php code from $fn, you should add $tmpDir into open_basedir");
            }

            $phpfunc = include($fn);
            unlink($fn);
            return $phpfunc;
        }

        return include('data://text/plain,' . urlencode($php));
    }
}
