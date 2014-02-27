<?php
/**
 * Generated by build/gen_test on 2014-02-26 at 05:34:15.
 */
require_once('src/lightncandy.php');

class LCRunTest extends PHPUnit_Framework_TestCase
{
    /**
     * @covers LCRun::ifvar
     */
    public function testOn_ifvar() {
        $method = new ReflectionMethod('LCRun', 'ifvar');
        $this->assertEquals(false, $method->invoke(null, 'a', Array(), Array()));
        $this->assertEquals(false, $method->invoke(null, 'a', Array(), Array('a' => null)));
        $this->assertEquals(false, $method->invoke(null, 'a', Array(), Array('a' => 0)));
        $this->assertEquals(false, $method->invoke(null, 'a', Array(), Array('a' => false)));
        $this->assertEquals(true, $method->invoke(null, 'a', Array(), Array('a' => true)));
        $this->assertEquals(true, $method->invoke(null, 'a', Array(), Array('a' => 1)));
        $this->assertEquals(false, $method->invoke(null, 'a', Array(), Array('a' => '')));
        $this->assertEquals(false, $method->invoke(null, 'a', Array(), Array('a' => Array())));
        $this->assertEquals(true, $method->invoke(null, 'a', Array(), Array('a' => Array(''))));
        $this->assertEquals(true, $method->invoke(null, 'a', Array(), Array('a' => Array(0))));
    }
    /**
     * @covers LCRun::ifv
     */
    public function testOn_ifv() {
        $method = new ReflectionMethod('LCRun', 'ifv');
        $this->assertEquals('', $method->invoke(null, 'a', Array('scopes' => Array()), Array(), function () {return 'Y';}));
        $this->assertEquals('Y', $method->invoke(null, 'a', Array('scopes' => Array()), Array('a' => 1), function () {return 'Y';}));
        $this->assertEquals('N', $method->invoke(null, 'a', Array('scopes' => Array()), Array(), function () {return 'Y';}, function () {return 'N';}));
        $this->assertEquals('N', $method->invoke(null, 'a', Array('scopes' => Array()), Array('a' => null), function () {return 'Y';}, function () {return 'N';}));
        $this->assertEquals('N', $method->invoke(null, 'a', Array('scopes' => Array()), Array('a' => false), function () {return 'Y';}, function () {return 'N';}));
        $this->assertEquals('N', $method->invoke(null, 'a', Array('scopes' => Array()), Array('a' => ''), function () {return 'Y';}, function () {return 'N';}));
        $this->assertEquals('N', $method->invoke(null, 'a', Array('scopes' => Array()), Array('a' => Array()), function () {return 'Y';}, function () {return 'N';}));
        $this->assertEquals('N', $method->invoke(null, 'a', Array('scopes' => Array()), Array('a' => 0), function () {return 'Y';}, function () {return 'N';}));
        $this->assertEquals('Y', $method->invoke(null, 'a', Array('scopes' => Array()), Array('a' => Array(0)), function () {return 'Y';}, function () {return 'N';}));
    }
    /**
     * @covers LCRun::unl
     */
    public function testOn_unl() {
        $method = new ReflectionMethod('LCRun', 'unl');
        $this->assertEquals('Y', $method->invoke(null, 'a', Array('scopes' => Array()), Array(), function () {return 'Y';}));
        $this->assertEquals('', $method->invoke(null, 'a', Array('scopes' => Array()), Array('a' => 1), function () {return 'Y';}));
        $this->assertEquals('Y', $method->invoke(null, 'a', Array('scopes' => Array()), Array(), function () {return 'Y';}, function () {return 'N';}));
        $this->assertEquals('Y', $method->invoke(null, 'a', Array('scopes' => Array()), Array('a' => null), function () {return 'Y';}, function () {return 'N';}));
        $this->assertEquals('Y', $method->invoke(null, 'a', Array('scopes' => Array()), Array('a' => false), function () {return 'Y';}, function () {return 'N';}));
        $this->assertEquals('Y', $method->invoke(null, 'a', Array('scopes' => Array()), Array('a' => ''), function () {return 'Y';}, function () {return 'N';}));
        $this->assertEquals('Y', $method->invoke(null, 'a', Array('scopes' => Array()), Array('a' => Array()), function () {return 'Y';}, function () {return 'N';}));
        $this->assertEquals('Y', $method->invoke(null, 'a', Array('scopes' => Array()), Array('a' => 0), function () {return 'Y';}, function () {return 'N';}));
        $this->assertEquals('N', $method->invoke(null, 'a', Array('scopes' => Array()), Array('a' => Array(0)), function () {return 'Y';}, function () {return 'N';}));
    }
    /**
     * @covers LCRun::isec
     */
    public function testOn_isec() {
        $method = new ReflectionMethod('LCRun', 'isec');
        $this->assertEquals(true, $method->invoke(null, 'a', Array(), Array()));
        $this->assertEquals(false, $method->invoke(null, 'a', Array(), Array('a' => 0)));
        $this->assertEquals(true, $method->invoke(null, 'a', Array(), Array('a' => false)));
        $this->assertEquals(false, $method->invoke(null, 'a', Array(), Array('a' => 'false')));
        $this->assertEquals(true, $method->invoke(null, 'a', Array(), Array('a' => null)));
    }
    /**
     * @covers LCRun::val
     */
    public function testOn_val() {
        $method = new ReflectionMethod('LCRun', 'val');
        $this->assertEquals(Array(), $method->invoke(null, '', Array(), Array()));
        $this->assertEquals(null, $method->invoke(null, 'a', Array(), Array()));
        $this->assertEquals(0, $method->invoke(null, 'a', Array(), Array('a' => 0)));
        $this->assertEquals(null, $method->invoke(null, 'a]b', Array(), Array('a' => 0)));
        $this->assertEquals(null, $method->invoke(null, 'a]b', Array(), Array()));
        $this->assertEquals('Q', $method->invoke(null, 'a]b', Array(), Array('a' => Array('b' => 'Q'))));
        $this->assertEquals('', $method->invoke(null, '..', Array('scopes' => Array()), Array()));
        $this->assertEquals('Y', $method->invoke(null, '..', Array('scopes' => Array('Y')), Array()));
        $this->assertEquals(null, $method->invoke(null, '../a', Array('scopes' => Array('Y')), Array()));
        $this->assertEquals('q', $method->invoke(null, '../a', Array('scopes' => Array(Array('a' => 'q'))), Array()));
        $this->assertEquals('o', $method->invoke(null, '../../a', Array('scopes' => Array(Array('a' => 'o'), Array('a' => 'p'))), Array()));
        $this->assertEquals('x', $method->invoke(null, '../../../', Array('scopes' => Array('x', Array('a' => 'q'), Array('b' => 'r'))), Array()));
        $this->assertEquals('o', $method->invoke(null, '../../../c', Array('scopes' => Array(Array('c' => 'o'), Array('a' => 'q'), Array('b' => 'r'))), Array()));
    }
}
?>