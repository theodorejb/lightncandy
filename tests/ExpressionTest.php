<?php

namespace LightnCandy\Test;

use LightnCandy\Expression;
use PHPUnit\Framework\TestCase;

class ExpressionTest extends TestCase
{
    public function testBoolString(): void
    {
        $this->assertSame('true', Expression::boolString(1));
        $this->assertSame('true', Expression::boolString(999));
        $this->assertSame('false', Expression::boolString(0));
        $this->assertSame('false', Expression::boolString(-1));
    }

    public function testListString(): void
    {
        $this->assertSame('', Expression::listString([]));
        $this->assertSame("'a'", Expression::listString(['a']));
        $this->assertSame("'a','b','c'", Expression::listString(['a', 'b', 'c']));
    }

    public function testArrayString(): void
    {
        $this->assertSame('', Expression::arrayString([]));
        $this->assertSame("['a']", Expression::arrayString(['a']));
        $this->assertSame("['a']['b']['c']", Expression::arrayString(['a', 'b', 'c']));
    }

    public function testAnalyze(): void
    {
        $this->assertSame([0, false, ['foo']], Expression::analyze(['flags' => []], [0, 'foo']));
        $this->assertSame([1, false, ['foo']], Expression::analyze(['flags' => []], [1, 'foo']));
    }

    public function testToString(): void
    {
        $this->assertSame('[a].[b]', Expression::toString(0, false, ['a', 'b']));
        $this->assertSame('@[root]', Expression::toString(0, true, ['root']));
        $this->assertSame('this.[id]', Expression::toString(0, false, [null, 'id']));
        $this->assertSame('@[root].[a].[b]', Expression::toString(0, true, ['root', 'a', 'b']));
        $this->assertSame('../../[a].[b]', Expression::toString(2, false, ['a', 'b']));
        $this->assertSame('../[a\'b]', Expression::toString(1, false, ['a\'b']));
    }
}
