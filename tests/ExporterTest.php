<?php

namespace DevTheorem\Handlebars\Test;

use DevTheorem\Handlebars\Exporter;
use PHPUnit\Framework\TestCase;

class ExporterTest extends TestCase
{
    public function testClosure(): void
    {
        $this->assertSame('function($a) {return;}', Exporter::closure(
            ['flags' => []],
            function ($a) {return;}
        ));
    }
}
