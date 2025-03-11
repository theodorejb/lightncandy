<?php

namespace LightnCandy\Test;

use LightnCandy\LightnCandy;
use LightnCandy\Options;
use PHPUnit\Framework\TestCase;

class UsageTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider("compileProvider")]
    public function testUsedFeature($test)
    {
        LightnCandy::precompile($test['template'], $test['options'] ?? new Options());
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
                 'options' => new Options(
                     helpers: [
                         'mytest' => function ($context) {
                             return $context;
                         }
                     ],
                 ),
                 'expected' => array(),
             ),

             array(
                 'template' => '{{mytest ../../../OK}} {{../OK}}',
                 'options' => new Options(
                     helpers: [
                         'mytest' => function ($context) {
                             return $context;
                         }
                     ],
                 ),
                 'expected' => array(),
             ),

             array(
                 'template' => '{{mytest . .}}',
                 'options' => new Options(
                     helpers: [
                         'mytest' => function ($a, $b) {
                             return '';
                         },
                     ],
                 ),
                 'expected' => array(),
             ),

             array(
                 'template' => '{{mytest (mytest ..)}}',
                 'options' => new Options(
                     helpers: [
                         'mytest' => function ($context) {
                             return $context;
                         }
                     ],
                 ),
                 'expected' => array(),
             ),

             array(
                 'template' => '{{mytest (mytest ..) .}}',
                 'options' => new Options(
                     helpers: [
                         'mytest' => function ($context) {
                             return $context;
                         }
                     ],
                 ),
                 'expected' => array(),
             ),

             array(
                 'template' => '{{mytest (mytest (mytest ..)) .}}',
                 'options' => new Options(
                     helpers: [
                         'mytest' => function ($context) {
                             return $context;
                         }
                     ],
                 ),
                 'expected' => array(),
             ),

             array(
                 'id' => '134',
                 'template' => '{{#if 1}}{{keys (keys ../names)}}{{/if}}',
                 'options' => new Options(
                     helpers: [
                         'keys' => function ($context) {
                             return $context;
                         }
                     ],
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
            $i['expected'] = array_merge($default, $i['expected'] ?? []);
            return array($i);
        }, $compileCases);
    }
}
