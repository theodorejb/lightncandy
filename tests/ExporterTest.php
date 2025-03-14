<?php

namespace DevTheorem\Handlebars\Test;

use DevTheorem\Handlebars\Exporter;
use PHPUnit\Framework\TestCase;

class ExporterTest extends TestCase
{
    public function testClosure(): void
    {
        $this->assertSame('function($a) { return 1; }', Exporter::closure(
            function ($a) { return 1; },
        ));
    }
}
