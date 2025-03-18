<?php

namespace DevTheorem\Handlebars\Test;

use DevTheorem\Handlebars\Encoder;
use PHPUnit\Framework\TestCase;

class EncoderTest extends TestCase
{
    public function testEnc(): void
    {
        $this->assertSame('a', Encoder::enc('a'));
        $this->assertSame('a&amp;b', Encoder::enc('a&b'));
        $this->assertSame('a&#039;b', Encoder::enc('a\'b'));
    }

    public function testEncq(): void
    {
        $this->assertSame('a', Encoder::encq('a'));
        $this->assertSame('a&amp;b', Encoder::encq('a&b'));
        $this->assertSame('a&#x27;b', Encoder::encq('a\'b'));
        $this->assertSame('&#x60;a&#x27;b', Encoder::encq('`a\'b'));
    }
}
