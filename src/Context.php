<?php

namespace DevTheorem\Handlebars;

/**
 * @internal
 */
final class Context
{
    public function __construct(
        public readonly Options $options,
        public int $level = 0,
        public array $stack = [],
        public ?array $currentToken = null,
        public array $error = [],
        public array $elseLvl = [],
        public bool $elseChain = false,
        public array $tokens = [
            'ahead' => false,
            'current' => 0,
            'count' => 0,
            'partialind' => '',
        ],
        public array $usedPartial = [],
        public array $partialStack = [],
        public array $partialCode = [],
        public int $usedDynPartial = 0,
        public int $usedPBlock = 0,
        public array $usedHelpers = [],
        public bool $compile = false,
        public array $parsed = [],
        public array $partials = [],
        public array $partialBlock = [],
        public array $inlinePartial = [],
        public array $helpers = [],
        public bool|string $rawBlock = false,
        public readonly array $ops = [
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
        ],
    ) {
        $this->partials = $options->partials;

        foreach ($options->helpers as $name => $func) {
            $tn = is_int($name) ? $func : $name;
            if (is_callable($func)) {
                $this->helpers[$tn] = $func;
            } else {
                if (is_array($func)) {
                    $this->error[] = "Custom helper $name must be a function, not an array.";
                } else {
                    $this->error[] = "Custom helper '$tn' must be a function.";
                }
            }
        }
    }

    /**
     * Update from another context.
     */
    public function merge(Context $context): void
    {
        $this->error = $context->error;
        $this->helpers = $context->helpers;
        $this->partials = $context->partials;
        $this->partialCode = $context->partialCode;
        $this->partialStack = $context->partialStack;
        $this->usedHelpers = $context->usedHelpers;
        $this->usedDynPartial = $context->usedDynPartial;
        $this->usedPBlock = $context->usedPBlock;
        $this->usedPartial = $context->usedPartial;
    }
}
