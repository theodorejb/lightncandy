<?php

namespace DevTheorem\Handlebars\Test;

use DevTheorem\Handlebars\SafeString;
use PHPUnit\Framework\TestCase;

class SafeStringTest extends TestCase
{
    public function testStripExtendedComments(): void
    {
        $this->assertSame('abc', SafeString::stripExtendedComments('abc'));
        $this->assertSame('abc{{!}}cde', SafeString::stripExtendedComments('abc{{!}}cde'));
        $this->assertSame('abc{{! }}cde', SafeString::stripExtendedComments('abc{{!----}}cde'));
    }

    public function testEscapeTemplate(): void
    {
        $this->assertSame('abc', SafeString::escapeTemplate('abc'));
        $this->assertSame('a\\\\bc', SafeString::escapeTemplate('a\bc'));
        $this->assertSame('a\\\'bc', SafeString::escapeTemplate('a\'bc'));
    }
}
