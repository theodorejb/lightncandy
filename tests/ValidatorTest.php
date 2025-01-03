<?php
/**
 * Generated by build/gen_test
 */
use LightnCandy\LightnCandy;
use LightnCandy\Runtime;
use LightnCandy\SafeString;
use PHPUnit\Framework\TestCase;

require_once(__DIR__ . '/test_util.php');

class ValidatorTest extends TestCase
{
    public function testOn_delimiter() {
        $method = new \ReflectionMethod('LightnCandy\Validator', 'delimiter');
        $method->setAccessible(true);
        $this->assertEquals(false, $method->invokeArgs(null, array_by_ref(array(
            array_fill(0, 11, ''), array()
        ))));
        $this->assertEquals(false, $method->invokeArgs(null, array_by_ref(array(
            array(0, 0, 0, 0, 0, '{{', '#', '...', '}}'), array()
        ))));
        $this->assertEquals(true, $method->invokeArgs(null, array_by_ref(array(
            array(0, 0, 0, 0, 0, '{', '#', '...', '}'), array()
        ))));
    }
    public function testOn_operator() {
        $method = new \ReflectionMethod('LightnCandy\Validator', 'operator');
        $method->setAccessible(true);
        $this->assertEquals(null, $method->invokeArgs(null, array_by_ref(array(
            '', array(), array()
        ))));
        $this->assertEquals(2, $method->invokeArgs(null, array_by_ref(array(
            '^', array('level' => 0, 'currentToken' => array(0,0,0,0,0,0,0,0), 'elselvl' => array(), 'flags' => array(), 'elsechain' => false), array(array('foo'))
        ))));
        $this->assertEquals(true, $method->invokeArgs(null, array_by_ref(array(
            '/', array('stack' => array('[with]', '#'), 'level' => 1, 'currentToken' => array(0,0,0,0,0,0,0,'with'), 'flags' => array()), array(array())
        ))));
        $this->assertEquals(4, $method->invokeArgs(null, array_by_ref(array(
            '#', array('level' => 0, 'currentToken' => array(0,0,0,0,0,0,0,0), 'flags' => array(), 'elsechain' => false, 'elselvl' => array()), array(array('x'))
        ))));
        $this->assertEquals(5, $method->invokeArgs(null, array_by_ref(array(
            '#', array('level' => 0, 'currentToken' => array(0,0,0,0,0,0,0,0), 'flags' => array(), 'elsechain' => false, 'elselvl' => array()), array(array('if'))
        ))));
        $this->assertEquals(6, $method->invokeArgs(null, array_by_ref(array(
            '#', array('level' => 0, 'flags' => array(), 'currentToken' => array(0,0,0,0,0,0,0,0), 'elsechain' => false, 'elselvl' => array()), array(array('with'))
        ))));
        $this->assertEquals(7, $method->invokeArgs(null, array_by_ref(array(
            '#', array('level' => 0, 'currentToken' => array(0,0,0,0,0,0,0,0), 'flags' => array(), 'elsechain' => false, 'elselvl' => array()), array(array('each'))
        ))));
        $this->assertEquals(8, $method->invokeArgs(null, array_by_ref(array(
            '#', array('level' => 0, 'currentToken' => array(0,0,0,0,0,0,0,0), 'flags' => array(), 'elsechain' => false, 'elselvl' => array()), array(array('unless'))
        ))));
        $this->assertEquals(9, $method->invokeArgs(null, array_by_ref(array(
            '#', array('helpers' => array('abc' => ''), 'level' => 0, 'currentToken' => array(0,0,0,0,0,0,0,0), 'flags' => array(), 'elsechain' => false, 'elselvl' => array()), array(array('abc'))
        ))));
        $this->assertEquals(11, $method->invokeArgs(null, array_by_ref(array(
            '#', array('helpers' => array('abc' => ''), 'level' => 0, 'currentToken' => array(0,0,0,0,0,0,0,0), 'flags' => array(), 'elsechain' => false, 'elselvl' => array()), array(array('abc'))
        ))));
        $this->assertEquals(true, $method->invokeArgs(null, array_by_ref(array(
            '>', array('level' => 0, 'flags' => array(), 'currentToken' => array(0,0,0,0,0,0,0,0), 'elsechain' => false, 'elselvl' => array()), array('test')
        ))));
    }
}
