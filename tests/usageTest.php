<?php

use LightnCandy\LightnCandy;
use PHPUnit\Framework\TestCase;

require_once('tests/helpers_for_test.php');

class usageTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider("compileProvider")]
    public function testUsedFeature($test)
    {
        LightnCandy::precompile($test['template'], $test['options']);
        $context = LightnCandy::getContext();
        $this->assertEquals($test['expected'], $context['usedFeature']);
    }

    public static function compileProvider(): array
    {
        $default = array(
            'dynpartial' => 0,
            'pblock' => 0,
        );

        $compileCases = array(
             array(
                 'template' => 'abc',
             ),

             array(
                 'template' => 'abc{{def',
             ),

             array(
                 'template' => 'abc{{def}}',
                 'expected' => array(),
             ),

             array(
                 'template' => 'abc{{{def}}}',
                 'expected' => array(),
             ),

             array(
                 'template' => 'abc{{&def}}',
                 'expected' => array(),
             ),

             array(
                 'template' => 'abc{{this}}',
                 'expected' => array(),
             ),

             array(
                 'template' => '{{#if abc}}OK!{{/if}}',
                 'expected' => array(),
             ),

             array(
                 'template' => '{{#unless abc}}OK!{{/unless}}',
                 'expected' => array(),
             ),

             array(
                 'template' => '{{#with abc}}OK!{{/with}}',
                 'expected' => array(),
             ),

             array(
                 'template' => '{{#abc}}OK!{{/abc}}',
                 'expected' => array(),
             ),

             array(
                 'template' => '{{^abc}}OK!{{/abc}}',
                 'expected' => array(),
             ),

             array(
                 'template' => '{{#each abc}}OK!{{/each}}',
                 'expected' => array(),
             ),

             array(
                 'template' => '{{! test}}OK!{{! done}}',
                 'expected' => array(),
             ),

             array(
                 'template' => '{{../OK}}',
                 'expected' => array(),
             ),

             array(
                 'template' => '{{&../../OK}}',
                 'expected' => array(),
             ),

             array(
                 'template' => '{{&../../../OK}} {{../OK}}',
                 'options' => array(
                    'helpers' => array(
                        'mytest' => function ($context) {
                            return $context;
                        }
                    )
                ),
                 'expected' => array(),
             ),

             array(
                 'template' => '{{mytest ../../../OK}} {{../OK}}',
                 'options' => array(
                    'helpers' => array(
                        'mytest' => function ($context) {
                            return $context;
                        }
                    )
                ),
                 'expected' => array(),
             ),

             array(
                 'template' => '{{mytest . .}}',
                 'options' => array(
                    'helpers' => array(
                        'mytest' => function ($a, $b) {
                            return '';
                        }
                    )
                ),
                 'expected' => array(),
             ),

             array(
                 'template' => '{{mytest (mytest ..)}}',
                 'options' => array(
                    'helpers' => array(
                        'mytest' => function ($context) {
                            return $context;
                        }
                    )
                ),
                 'expected' => array(),
             ),

             array(
                 'template' => '{{mytest (mytest ..) .}}',
                 'options' => array(
                    'helpers' => array(
                        'mytest' => function ($context) {
                            return $context;
                        }
                    )
                ),
                 'expected' => array(),
             ),

             array(
                 'template' => '{{mytest (mytest (mytest ..)) .}}',
                 'options' => array(
                    'helpers' => array(
                        'mytest' => function ($context) {
                            return $context;
                        }
                    )
                ),
                 'expected' => array(),
             ),

             array(
                 'id' => '134',
                 'template' => '{{#if 1}}{{keys (keys ../names)}}{{/if}}',
                 'options' => array(
                    'helpers' => array(
                        'keys' => function ($context) {
                            return $context;
                        }
                    )
                ),
                 'expected' => array(),
             ),

             array(
                 'id' => '196',
                 'template' => '{{log "this is a test"}}',
                 'expected' => array(),
             ),
        );

        return array_map(function($i) use ($default) {
            if (!isset($i['options'])) {
                $i['options'] = array('flags' => 0);
            }
            if (!isset($i['options']['flags'])) {
                $i['options']['flags'] = 0;
            }
            $i['expected'] = array_merge($default, $i['expected'] ?? []);
            return array($i);
        }, $compileCases);
    }
}

