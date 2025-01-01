<?php
/**
 * Generated by build/gen_test
 */
use LightnCandy\LightnCandy;
use LightnCandy\Runtime;
use LightnCandy\SafeString;
use PHPUnit\Framework\TestCase;

require_once(__DIR__ . '/test_util.php');

class RuntimeTest extends TestCase
{
    public function testOn_ifvar() {
        $method = new \ReflectionMethod('LightnCandy\Runtime', 'ifvar');
        $this->assertEquals(false, $method->invokeArgs(null, array_by_ref(array(
            array(), null, false
        ))));
        $this->assertEquals(false, $method->invokeArgs(null, array_by_ref(array(
            array(), 0, false
        ))));
        $this->assertEquals(true, $method->invokeArgs(null, array_by_ref(array(
            array(), 0, true
        ))));
        $this->assertEquals(false, $method->invokeArgs(null, array_by_ref(array(
            array(), false, false
        ))));
        $this->assertEquals(true, $method->invokeArgs(null, array_by_ref(array(
            array(), true, false
        ))));
        $this->assertEquals(true, $method->invokeArgs(null, array_by_ref(array(
            array(), 1, false
        ))));
        $this->assertEquals(false, $method->invokeArgs(null, array_by_ref(array(
            array(), '', false
        ))));
        $this->assertEquals(false, $method->invokeArgs(null, array_by_ref(array(
            array(), array(), false
        ))));
        $this->assertEquals(true, $method->invokeArgs(null, array_by_ref(array(
            array(), array(''), false
        ))));
        $this->assertEquals(true, $method->invokeArgs(null, array_by_ref(array(
            array(), array(0), false
        ))));
    }
    public function testOn_isec() {
        $method = new \ReflectionMethod('LightnCandy\Runtime', 'isec');
        $this->assertEquals(true, $method->invokeArgs(null, array_by_ref(array(
            array(), null
        ))));
        $this->assertEquals(false, $method->invokeArgs(null, array_by_ref(array(
            array(), 0
        ))));
        $this->assertEquals(true, $method->invokeArgs(null, array_by_ref(array(
            array(), false
        ))));
        $this->assertEquals(false, $method->invokeArgs(null, array_by_ref(array(
            array(), 'false'
        ))));
        $this->assertEquals(true, $method->invokeArgs(null, array_by_ref(array(
            array(), array()
        ))));
        $this->assertEquals(false, $method->invokeArgs(null, array_by_ref(array(
            array(), array('1')
        ))));
    }
    public function testOn_enc() {
        $method = new \ReflectionMethod('LightnCandy\Runtime', 'enc');
        $this->assertEquals('a', $method->invokeArgs(null, array_by_ref(array(
            array('flags' => array()), 'a'
        ))));
        $this->assertEquals('a&amp;b', $method->invokeArgs(null, array_by_ref(array(
            array('flags' => array()), 'a&b'
        ))));
        $this->assertEquals('a&#039;b', $method->invokeArgs(null, array_by_ref(array(
            array('flags' => array()), 'a\'b'
        ))));
        $this->assertEquals('a&b', $method->invokeArgs(null, array_by_ref(array(
            [], new \LightnCandy\SafeString('a&b')
        ))));
    }
    public function testOn_encq() {
        $method = new \ReflectionMethod('LightnCandy\Runtime', 'encq');
        $this->assertEquals('a', $method->invokeArgs(null, array_by_ref(array(
            array('flags' => array()), 'a'
        ))));
        $this->assertEquals('a&amp;b', $method->invokeArgs(null, array_by_ref(array(
            array('flags' => array()), 'a&b'
        ))));
        $this->assertEquals('a&#x27;b', $method->invokeArgs(null, array_by_ref(array(
            array('flags' => array()), 'a\'b'
        ))));
        $this->assertEquals('&#x60;a&#x27;b', $method->invokeArgs(null, array_by_ref(array(
            array('flags' => array()), '`a\'b'
        ))));
    }
    public function testOn_sec() {
        $method = new \ReflectionMethod('LightnCandy\Runtime', 'sec');
        $this->assertEquals('', $method->invokeArgs(null, array_by_ref(array(
            array('flags' => array()), false, null, false, false, function () {return 'A';}
        ))));
        $this->assertEquals('', $method->invokeArgs(null, array_by_ref(array(
            array('flags' => array()), null, null, null, false, function () {return 'A';}
        ))));
        $this->assertEquals('A', $method->invokeArgs(null, array_by_ref(array(
            array('flags' => array()), true, null, true, false, function () {return 'A';}
        ))));
        $this->assertEquals('A', $method->invokeArgs(null, array_by_ref(array(
            array('flags' => array()), 0, null, 0, false, function () {return 'A';}
        ))));
        $this->assertEquals('-a=', $method->invokeArgs(null, array_by_ref(array(
            array('scopes' => array(), 'flags' => array()), array('a'), null, array('a'), false, function ($c, $i) {return "-$i=";}
        ))));
        $this->assertEquals('-a=-b=', $method->invokeArgs(null, array_by_ref(array(
            array('scopes' => array(), 'flags' => array()), array('a','b'), null, array('a','b'), false, function ($c, $i) {return "-$i=";}
        ))));
        $this->assertEquals('', $method->invokeArgs(null, array_by_ref(array(
            array('scopes' => array(), 'flags' => array()), 'abc', null, 'abc', true, function ($c, $i) {return "-$i=";}
        ))));
        $this->assertEquals('-b=', $method->invokeArgs(null, array_by_ref(array(
            array('scopes' => array(), 'flags' => array()), array('a' => 'b'), null, array('a' => 'b'), true, function ($c, $i) {return "-$i=";}
        ))));
        $this->assertEquals('b', $method->invokeArgs(null, array_by_ref(array(
            array('flags' => array()), 'b', null, 'b', false, function ($c, $i) {return print_r($i, true);}
        ))));
        $this->assertEquals('1', $method->invokeArgs(null, array_by_ref(array(
            array('flags' => array()), 1, null, 1, false, function ($c, $i) {return print_r($i, true);}
        ))));
        $this->assertEquals('0', $method->invokeArgs(null, array_by_ref(array(
            array('flags' => array()), 0, null, 0, false, function ($c, $i) {return print_r($i, true);}
        ))));
        $this->assertEquals('{"b":"c"}', $method->invokeArgs(null, array_by_ref(array(
            array('flags' => array()), array('b' => 'c'), null, array('b' => 'c'), false, function ($c, $i) {return json_encode($i);}
        ))));
        $this->assertEquals('inv', $method->invokeArgs(null, array_by_ref(array(
            array('flags' => array()), array(), null, 0, true, function ($c, $i) {return 'cb';}, function ($c, $i) {return 'inv';}
        ))));
        $this->assertEquals('inv', $method->invokeArgs(null, array_by_ref(array(
            array('flags' => array()), array(), null, 0, false, function ($c, $i) {return 'cb';}, function ($c, $i) {return 'inv';}
        ))));
        $this->assertEquals('inv', $method->invokeArgs(null, array_by_ref(array(
            array('flags' => array()), false, null, 0, true, function ($c, $i) {return 'cb';}, function ($c, $i) {return 'inv';}
        ))));
        $this->assertEquals('inv', $method->invokeArgs(null, array_by_ref(array(
            array('flags' => array()), false, null, 0, false, function ($c, $i) {return 'cb';}, function ($c, $i) {return 'inv';}
        ))));
        $this->assertEquals('inv', $method->invokeArgs(null, array_by_ref(array(
            array('flags' => array()), '', null, 0, true, function ($c, $i) {return 'cb';}, function ($c, $i) {return 'inv';}
        ))));
        $this->assertEquals('cb', $method->invokeArgs(null, array_by_ref(array(
            array('flags' => array()), '', null, 0, false, function ($c, $i) {return 'cb';}, function ($c, $i) {return 'inv';}
        ))));
        $this->assertEquals('inv', $method->invokeArgs(null, array_by_ref(array(
            array('flags' => array()), 0, null, 0, true, function ($c, $i) {return 'cb';}, function ($c, $i) {return 'inv';}
        ))));
        $this->assertEquals('cb', $method->invokeArgs(null, array_by_ref(array(
            array('flags' => array()), 0, null, 0, false, function ($c, $i) {return 'cb';}, function ($c, $i) {return 'inv';}
        ))));
        $this->assertEquals('inv', $method->invokeArgs(null, array_by_ref(array(
            array('flags' => array()), new stdClass, null, 0, true, function ($c, $i) {return 'cb';}, function ($c, $i) {return 'inv';}
        ))));
        $this->assertEquals('cb', $method->invokeArgs(null, array_by_ref(array(
            array('flags' => array()), new stdClass, null, 0, false, function ($c, $i) {return 'cb';}, function ($c, $i) {return 'inv';}
        ))));
        $this->assertEquals('268', $method->invokeArgs(null, array_by_ref(array(
            array('scopes' => array(), 'flags' => array(), 'sp_vars'=>array('root' => 0)), array(1,3,4), null, 0, false, function ($c, $i) {return $i * 2;}
        ))));
        $this->assertEquals('038', $method->invokeArgs(null, array_by_ref(array(
            array('scopes' => array(), 'flags' => array(), 'sp_vars'=>array('root' => 0)), array(1,3,'a'=>4), null, 0, true, function ($c, $i) {return $i * $c['sp_vars']['index'];}
        ))));
    }
    public function testOn_wi() {
        $method = new \ReflectionMethod('LightnCandy\Runtime', 'wi');
        $this->assertEquals('', $method->invokeArgs(null, array_by_ref(array(
            array(), false, null, false, function () {return 'A';}
        ))));
        $this->assertEquals('', $method->invokeArgs(null, array_by_ref(array(
            array(), null, null, null, function () {return 'A';}
        ))));
        $this->assertEquals('{"a":"b"}', $method->invokeArgs(null, array_by_ref(array(
            array(), array('a'=>'b'), null, array('a'=>'c'), function ($c, $i) {return json_encode($i);}
        ))));
        $this->assertEquals('-b=', $method->invokeArgs(null, array_by_ref(array(
            array(), 'b', null, array('a'=>'b'), function ($c, $i) {return "-$i=";}
        ))));
    }
}
