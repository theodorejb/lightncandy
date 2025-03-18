<?php

namespace DevTheorem\Handlebars;

/**
 * @internal
 */
class RuntimeContext
{
    public function __construct(
        public array $helpers = [],
        public array $partials = [],
        public array $scopes = [],
        public array $spVars = [],
        public array $blParam = [],
        public int $partialId = 0,
    ) {}
}
