<?php
/**
 * Generated by build/gen_test on 2014-02-26 at 05:34:15.
 */
include('src/lightncandy.inc');
class LightnCandyTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers LightnCandy::_fileext
     */
    public function testOn__fileext() {
        $method = new ReflectionMethod('LightnCandy', '_fileext');
        $method->setAccessible(true);
        $this->assertEquals(Array('.tmpl'), $method->invoke(null, Array()));
        $this->assertEquals(Array('test'), $method->invoke(null, Array('fileext' => 'test')));
        $this->assertEquals(Array('test1'), $method->invoke(null, Array('fileext' => Array('test1'))));
        $this->assertEquals(Array('test2', 'test3'), $method->invoke(null, Array('fileext' => Array('test2', 'test3'))));
    }
    /**
     * @covers LightnCandy::_scope
     */
    public function testOn__scope() {
        $method = new ReflectionMethod('LightnCandy', '_scope');
        $method->setAccessible(true);
        $this->assertEquals('', $method->invoke(null, Array()));
        $this->assertEquals('[a]', $method->invoke(null, Array('a')));
        $this->assertEquals('[a][b][c]', $method->invoke(null, Array('a', 'b', 'c')));
    }
    /**
     * @covers LightnCandy::_vs
     */
    public function testOn__vs() {
        $method = new ReflectionMethod('LightnCandy', '_vs');
        $method->setAccessible(true);
        $this->assertEquals(Array('.'), $method->invoke(null, '.'));
    }
}

class LCRunTest extends PHPUnit_Framework_TestCase
{
}

?>