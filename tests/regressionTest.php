<?php

use LightnCandy\LightnCandy;
use LightnCandy\SafeString;
use PHPUnit\Framework\TestCase;

require_once('tests/helpers_for_test.php');

class regressionTest extends TestCase
{
    #[\PHPUnit\Framework\Attributes\DataProvider("issueProvider")]
    public function testIssues($issue)
    {
        $templateSpec = LightnCandy::precompile($issue['template'], $issue['options'] ?? []);
        $context = LightnCandy::getContext();
        $parsed = print_r(LightnCandy::$lastParsed, true);
        if (count($context['error'])) {
            $this->fail('Compile failed due to: ' . print_r($context['error'], true) . "\nPARSED: $parsed");
        }

        try {
            $template = LightnCandy::template($templateSpec);
            $result = $template($issue['data'] ?? null);
            $this->assertEquals($issue['expected'], $result, "PHP CODE:\n$templateSpec");
        } catch (Throwable $e) {
            $this->fail("Error: {$e->getMessage()}\nPHP code:\n$templateSpec");
        }
    }

    public static function issueProvider(): array
    {
        $test_helpers = array('ouch' =>function() {
            return 'ok';
        });

        $test_helpers2 = array('ouch' =>function() {return 'wa!';});

        $test_helpers3 = array('ouch' =>function() {return 'wa!';}, 'god' => function () {return 'yo';});

        $issues = array(
            array(
                'id' => 39,
                'template' => '{{{tt}}}',
                'data' => array('tt' => 'bla bla bla'),
                'expected' => 'bla bla bla'
            ),

            array(
                'id' => 44,
                'template' => '<div class="terms-text"> {{render "artists-terms"}} </div>',
                'options' => array(
                    'helpers' => array(
                        'render' => function($view,$data = array()) {
                            return 'OK!';
                         }
                    )
                ),
                'data' => array('tt' => 'bla bla bla'),
                'expected' => '<div class="terms-text"> OK! </div>'
            ),

            array(
                'id' => 46,
                'template' => '{{{this.id}}}, {{a.id}}',
                'options' => array(),
                'data' => array('id' => 'bla bla bla', 'a' => array('id' => 'OK!')),
                'expected' => 'bla bla bla, OK!'
            ),

            array(
                'id' => 49,
                'template' => '{{date_format}} 1, {{date_format2}} 2, {{date_format3}} 3, {{date_format4}} 4',
                'options' => array(
                    'helpers' => array(
                        'date_format' => 'meetup_date_format',
                        'date_format2' => 'meetup_date_format2',
                        'date_format3' => 'meetup_date_format3',
                        'date_format4' => 'meetup_date_format4'
                    )
                ),
                'expected' => 'OKOK~1 1, OKOK~2 2, OKOK~3 3, OKOK~4 4'
            ),

            array(
                'id' => 52,
                'template' => '{{{test_array tmp}}} should be happy!',
                'options' => array(
                    'helpers' => array(
                        'test_array',
                    )
                ),
                'data' => array('tmp' => array('A', 'B', 'C')),
                'expected' => 'IS_ARRAY should be happy!'
            ),

            array(
                'id' => 62,
                'template' => '{{{test_join @root.foo.bar}}} should be happy!',
                'options' => array(
                     'helpers' => array('test_join')
                ),
                'data' => array('foo' => array('A', 'B', 'bar' => array('C', 'D'))),
                'expected' => 'C.D should be happy!',
            ),

            array(
                'id' => 64,
                'template' => '{{#each foo}} Test! {{this}} {{/each}}{{> test1}} ! >>> {{>recursive}}',
                'options' => array(
                    'partials' => array(
                        'test1' => "123\n",
                        'recursive' => "{{#if foo}}{{bar}} -> {{#with foo}}{{>recursive}}{{/with}}{{else}}END!{{/if}}\n",
                    ),
                ),
                'data' => array(
                 'bar' => 1,
                 'foo' => array(
                  'bar' => 3,
                  'foo' => array(
                   'bar' => 5,
                   'foo' => array(
                    'bar' => 7,
                    'foo' => array(
                     'bar' => 11,
                     'foo' => array(
                      'no foo here'
                     )
                    )
                   )
                  )
                 )
                ),
                'expected' => " Test! 3  Test! [object Object] 123\n ! >>> 1 -> 3 -> 5 -> 7 -> 11 -> END!\n\n\n\n\n\n",
            ),

            array(
                'id' => 66,
                'template' => '{{&foo}} , {{foo}}, {{{foo}}}',
                'data' => array('foo' => 'Test & " \' :)'),
                'expected' => 'Test & " \' :) , Test &amp; &quot; &#x27; :), Test & " \' :)',
            ),

            array(
                'id' => 68,
                'template' => '{{#myeach foo}} Test! {{this}} {{/myeach}}',
                'options' => array(
                    'helpers' => array(
                        'myeach' => function ($context, $options) {
                            $ret = '';
                            foreach ($context as $cx) {
                                $ret .= $options['fn']($cx);
                            }
                            return $ret;
                        }
                    )
                ),
                'data' => array('foo' => array('A', 'B', 'bar' => array('C', 'D', 'E'))),
                'expected' => ' Test! A  Test! B  Test! C,D,E ',
            ),

            array(
                'id' => 81,
                'template' => '{{#with ../person}} {{^name}} Unknown {{/name}} {{/with}}?!',
                'data' => array('parent?!' => array('A', 'B', 'bar' => array('C', 'D', 'E'))),
                'expected' => '?!'
            ),

            array(
                'id' => 83,
                'template' => '{{> tests/test1}}',
                'options' => array(
                    'partials' => array(
                        'tests/test1' => "123\n",
                    ),
                ),
                'expected' => "123\n"
            ),

            array(
                'id' => 85,
                'template' => '{{helper 1 foo bar="q"}}',
                'options' => array(
                    'helpers' => array(
                        'helper' => function ($arg1, $arg2, $options) {
                            return "ARG1:$arg1, ARG2:$arg2, HASH:{$options['hash']['bar']}";
                        }
                    )
                ),
                'data' => array('foo' => 'BAR'),
                'expected' => 'ARG1:1, ARG2:BAR, HASH:q',
            ),

            array(
                'id' => 88,
                'template' => '{{>test2}}',
                'options' => array(
                    'flags' => 0,
                    'partials' => array(
                        'test2' => "a{{> test1}}b\n",
                        'test1' => "123\n",
                    ),
                ),
                'expected' => "a123\nb\n",
            ),

            array(
                'id' => 90,
                'template' => '{{#items}}{{#value}}{{.}}{{/value}}{{/items}}',
                'data' => array('items' => array(array('value'=>'123'))),
                'expected' => '123',
            ),

            array(
                'id' => 109,
                'template' => '{{#if "OK"}}it\'s great!{{/if}}',
                'options' => array(
                    'flags' => LightnCandy::FLAG_NOESCAPE,
                ),
                'expected' => 'it\'s great!',
            ),

            array(
                'id' => 110,
                'template' => 'ABC{{#block "YES!"}}DEF{{foo}}GHI{{/block}}JKL',
                'options' => array(
                    'helpers' => array(
                        'block' => function ($name, $options) {
                            return "1-$name-2-" . $options['fn']() . '-3';
                        }
                    ),
                ),
                'data' => array('foo' => 'bar'),
                'expected' => 'ABC1-YES!-2-DEFbarGHI-3JKL',
            ),

            array(
                'id' => 109,
                'template' => '{{foo}} {{> test}}',
                'options' => array(
                    'flags' => LightnCandy::FLAG_NOESCAPE,
                    'partials' => array('test' => '{{foo}}'),
                ),
                'data' => array('foo' => '<'),
                'expected' => '< <',
            ),

            array(
                'id' => 114,
                'template' => '{{^myeach .}}OK:{{.}},{{else}}NOT GOOD{{/myeach}}',
                'options' => array(
                    'helpers' => array(
                        'myeach' => function ($context, $options) {
                            $ret = '';
                            foreach ($context as $cx) {
                                $ret .= $options['fn']($cx);
                            }
                            return $ret;
                        }
                    ),
                ),
                'data' => array(1, 'foo', 3, 'bar'),
                'expected' => 'NOT GOODNOT GOODNOT GOODNOT GOOD',
            ),

            array(
                'id' => 124,
                'template' => '{{list foo bar abc=(lt 10 3) def=(lt 3 10)}}',
                'options' => array(
                    'helpers' => array(
                        'lt' => function ($a, $b) {
                            return ($a > $b) ? new SafeString("$a>$b") : '';
                        },
                        'list' => function () {
                            $out = 'List:';
                            $args = func_get_args();
                            $opts = array_pop($args);

                            foreach ($args as $v) {
                                if ($v) {
                                    $out .= ")$v , ";
                                }
                            }

                            foreach ($opts['hash'] as $k => $v) {
                                if ($v) {
                                    $out .= "]$k=$v , ";
                                }
                            }
                            return new SafeString($out);
                        }
                    ),
                ),
                'data' => array('foo' => 'OK!', 'bar' => 'OK2', 'abc' => false, 'def' => 123),
                'expected' => 'List:)OK! , )OK2 , ]abc=10>3 , ',
            ),

            array(
                'id' => 124,
                'template' => '{{#if (equal \'OK\' cde)}}YES!{{/if}}',
                'options' => array(
                    'helpers' => array(
                        'equal' => function ($a, $b) {
                            return $a === $b;
                        }
                    ),
                ),
                'data' => array('cde' => 'OK'),
                'expected' => 'YES!'
            ),

            array(
                'id' => 124,
                'template' => '{{#if (equal true (equal \'OK\' cde))}}YES!{{/if}}',
                'options' => array(
                    'helpers' => array(
                        'equal' => function ($a, $b) {
                            return $a === $b;
                        }
                    ),
                ),
                'data' => array('cde' => 'OK'),
                'expected' => 'YES!'
            ),

            array(
                'id' => 125,
                'template' => '{{#if (equal true ( equal \'OK\' cde))}}YES!{{/if}}',
                'options' => array(
                    'helpers' => array(
                        'equal' => function ($a, $b) {
                            return $a === $b;
                        }
                    ),
                ),
                'data' => array('cde' => 'OK'),
                'expected' => 'YES!'
            ),

            array(
                'id' => 125,
                'template' => '{{#if (equal true (equal \' OK\' cde))}}YES!{{/if}}',
                'options' => array(
                    'helpers' => array(
                        'equal' => function ($a, $b) {
                            return $a === $b;
                        }
                    ),
                ),
                'data' => array('cde' => ' OK'),
                'expected' => 'YES!'
            ),

            array(
                'id' => 125,
                'template' => '{{#if (equal true (equal \' ==\' cde))}}YES!{{/if}}',
                'options' => array(
                    'helpers' => array(
                        'equal' => function ($a, $b) {
                            return $a === $b;
                        }
                    ),
                ),
                'data' => array('cde' => ' =='),
                'expected' => 'YES!'
            ),

            array(
                'id' => 125,
                'template' => '{{#if (equal true (equal " ==" cde))}}YES!{{/if}}',
                'options' => array(
                    'helpers' => array(
                        'equal' => function ($a, $b) {
                            return $a === $b;
                        }
                    ),
                ),
                'data' => array('cde' => ' =='),
                'expected' => 'YES!'
            ),

            array(
                'id' => 125,
                'template' => '{{[ abc]}}',
                'options' => array(
                    'helpers' => array(
                        'equal' => function ($a, $b) {
                            return $a === $b;
                        }
                    ),
                ),
                'data' => array(' abc' => 'YES!'),
                'expected' => 'YES!'
            ),

            array(
                'id' => 125,
                'template' => '{{list [ abc] " xyz" \' def\' "==" \'==\' "OK"}}',
                'options' => array(
                    'helpers' => array(
                        'list' => function ($a, $b) {
                            $out = 'List:';
                            $args = func_get_args();
                            $opts = array_pop($args);
                            foreach ($args as $v) {
                                if ($v) {
                                    $out .= ")$v , ";
                                }
                            }
                            return $out;
                        }
                    ),
                ),
                'data' => array(' abc' => 'YES!'),
                'expected' => 'List:)YES! , ) xyz , ) def , )&#x3D;&#x3D; , )&#x3D;&#x3D; , )OK , ',
            ),

            array(
                'id' => 127,
                'template' => '{{#each array}}#{{#if true}}{{name}}-{{../name}}-{{../../name}}-{{../../../name}}{{/if}}##{{#myif true}}{{name}}={{../name}}={{../../name}}={{../../../name}}{{/myif}}###{{#mywith true}}{{name}}~{{../name}}~{{../../name}}~{{../../../name}}{{/mywith}}{{/each}}',
                'data' => array('name' => 'john', 'array' => array(1,2,3)),
                'options' => array(
                    'helpers' => array('myif', 'mywith'),
                ),
                // PENDING ISSUE, check for https://github.com/wycats/handlebars.js/issues/1135
                // 'expected' => '#--john-##==john=###~~john~#--john-##==john=###~~john~#--john-##==john=###~~john~',
                'expected' => '#-john--##=john==###~~john~#-john--##=john==###~~john~#-john--##=john==###~~john~',
            ),

            array(
                'id' => 128,
                'template' => 'foo: {{foo}} , parent foo: {{../foo}}',
                'data' => array('foo' => 'OK'),
                'expected' => 'foo: OK , parent foo: ',
            ),

            array(
                'id' => 132,
                'template' => '{{list (keys .)}}',
                'data' => array('foo' => 'bar', 'test' => 'ok'),
                'options' => array(
                    'helpers' => array(
                        'keys' => function($arg) {
                            return array_keys($arg);
                         },
                        'list' => function($arg) {
                            return join(',', $arg);
                         }
                    ),
                ),
                'expected' => 'foo,test',
            ),

            array(
                'id' => 133,
                'template' => "{{list (keys\n .\n ) \n}}",
                'data' => array('foo' => 'bar', 'test' => 'ok'),
                'options' => array(
                    'helpers' => array(
                        'keys' => function($arg) {
                            return array_keys($arg);
                         },
                        'list' => function($arg) {
                            return join(',', $arg);
                         }
                    ),
                ),
                'expected' => 'foo,test',
            ),

            array(
                'id' => 133,
                'template' => "{{list\n .\n \n \n}}",
                'data' => array('foo', 'bar', 'test'),
                'options' => array(
                    'helpers' => array(
                        'list' => function($arg) {
                            return join(',', $arg);
                         }
                    ),
                ),
                'expected' => 'foo,bar,test',
            ),

            array(
                'id' => 134,
                'template' => "{{#if 1}}{{list (keys names)}}{{/if}}",
                'data' => array('names' => array('foo' => 'bar', 'test' => 'ok')),
                'options' => array(
                    'helpers' => array(
                        'keys' => function($arg) {
                            return array_keys($arg);
                         },
                        'list' => function($arg) {
                            return join(',', $arg);
                         }
                    ),
                ),
                'expected' => 'foo,test',
            ),

            array(
                'id' => 138,
                'template' => "{{#each (keys .)}}={{.}}{{/each}}",
                'data' => array('foo' => 'bar', 'test' => 'ok', 'Haha'),
                'options' => array(
                    'helpers' => array(
                        'keys' => function($arg) {
                            return array_keys($arg);
                         }
                    ),
                ),
                'expected' => '=foo=test=0',
            ),

            array(
                'id' => 140,
                'template' => "{{[a.good.helper] .}}",
                'data' => array('ha', 'hey', 'ho'),
                'options' => array(
                    'helpers' => array(
                        'a.good.helper' => function($arg) {
                            return join(',', $arg);
                         }
                    ),
                ),
                'expected' => 'ha,hey,ho',
            ),

            array(
                'id' => 141,
                'template' => "{{#with foo}}{{#getThis bar}}{{/getThis}}{{/with}}",
                'data' => array('foo' => array('bar' => 'Good!')),
                'options' => array(
                    'helpers' => array(
                        'getThis' => function($input, $options) {
                            return $input . '-' . $options['_this']['bar'];
                         }
                    ),
                ),
                'expected' => 'Good!-Good!',
            ),

            array(
                'id' => 141,
                'template' => "{{#with foo}}{{getThis bar}}{{/with}}",
                'data' => array('foo' => array('bar' => 'Good!')),
                'options' => array(
                    'helpers' => array(
                        'getThis' => function($input, $options) {
                            return $input . '-' . $options['_this']['bar'];
                         }
                    ),
                ),
                'expected' => 'Good!-Good!',
            ),

            array(
                'id' => 143,
                'template' => "{{testString foo bar=\" \"}}",
                'data' => array('foo' => 'good!'),
                'options' => array(
                    'helpers' => array(
                        'testString' => function($arg, $options) {
                            return $arg . '-' . $options['hash']['bar'];
                         }
                    ),
                ),
                'expected' => 'good!- ',
            ),

            array(
                'id' => 143,
                'template' => "{{testString foo bar=\"\"}}",
                'data' => array('foo' => 'good!'),
                'options' => array(
                    'helpers' => array(
                        'testString' => function($arg, $options) {
                            return $arg . '-' . $options['hash']['bar'];
                         }
                    ),
                ),
                'expected' => 'good!-',
            ),

            array(
                'id' => 143,
                'template' => "{{testString foo bar=' '}}",
                'data' => array('foo' => 'good!'),
                'options' => array(
                    'helpers' => array(
                        'testString' => function($arg, $options) {
                            return $arg . '-' . $options['hash']['bar'];
                         }
                    ),
                ),
                'expected' => 'good!- ',
            ),

            array(
                'id' => 143,
                'template' => "{{testString foo bar=''}}",
                'data' => array('foo' => 'good!'),
                'options' => array(
                    'helpers' => array(
                        'testString' => function($arg, $options) {
                            return $arg . '-' . $options['hash']['bar'];
                         }
                    ),
                ),
                'expected' => 'good!-',
            ),

            array(
                'id' => 143,
                'template' => "{{testString foo bar=\" \"}}",
                'data' => array('foo' => 'good!'),
                'options' => array(
                    'helpers' => array(
                        'testString' => function($arg1, $options) {
                            return $arg1 . '-' . $options['hash']['bar'];
                         }
                    ),
                ),
                'expected' => 'good!- ',
            ),

            array(
                'id' => 147,
                'template' => '{{> test/test3 foo="bar"}}',
                'data' => array('test' => 'OK!', 'foo' => 'error'),
                'options' => array(
                    'partials' => array('test/test3' => '{{test}}, {{foo}}'),
                ),
                'expected' => 'OK!, bar'
            ),

            array(
                'id' => 153,
                'template' => '{{echo "test[]"}}',
                'options' => array(
                    'helpers' => array(
                        'echo' => function ($in) {
                            return "-$in-";
                        }
                    )
                ),
                'expected' => "-test[]-",
            ),

            array(
                'id' => 153,
                'template' => '{{echo \'test[]\'}}',
                'options' => array(
                    'helpers' => array(
                        'echo' => function ($in) {
                            return "-$in-";
                        }
                    )
                ),
                'expected' => "-test[]-",
            ),

            array(
                'id' => 154,
                'template' => 'O{{! this is comment ! ... }}K!',
                'expected' => "OK!"
            ),

            array(
                'id' => 157,
                'template' => '{{{du_mp text=(du_mp "123")}}}',
                'options' => array(
                    'helpers' => array(
                        'du_mp' => function ($a) {
                            return '>' . print_r(isset($a['hash']) ? $a['hash'] : $a, true);
                        }
                    )
                ),
                'expected' => <<<VAREND
>Array
(
    [text] => >123
)

VAREND
            ),

            array(
                'id' => 157,
                'template' => '{{>test_js_partial}}',
                'options' => array(
                    'partials' => array(
                        'test_js_partial' => <<<VAREND
Test GA....
<script>
(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){console.log('works!')};})();
</script>
VAREND
                    )
                ),
                'expected' => <<<VAREND
Test GA....
<script>
(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){console.log('works!')};})();
</script>
VAREND
            ),

            array(
                'id' => 159,
                'template' => '{{#.}}true{{else}}false{{/.}}',
                'data' => new ArrayObject(),
                'expected' => "false",
            ),

            array(
                'id' => 169,
                'template' => '{{{{a}}}}true{{else}}false{{{{/a}}}}',
                'data' => array('a' => true),
                'expected' => "true{{else}}false",
            ),

            array(
                'id' => 171,
                'template' => '{{#my_private_each .}}{{@index}}:{{.}},{{/my_private_each}}',
                'data' => array('a', 'b', 'c'),
                'options' => array(
                    'helpers' => array(
                        'my_private_each'
                    )
                ),
                'expected' => '0:a,1:b,2:c,',
            ),

            array(
                'id' => 175,
                'template' => 'a{{!-- {{each}} haha {{/each}} --}}b',
                'expected' => 'ab',
            ),

            array(
                'id' => 175,
                'template' => 'c{{>test}}d',
                'options' => array(
                    'partials' => array(
                        'test' => 'a{{!-- {{each}} haha {{/each}} --}}b',
                    ),
                ),
                'expected' => 'cabd',
            ),

            array(
                'id' => 177,
                'template' => '{{{{a}}}} {{{{b}}}} {{{{/b}}}} {{{{/a}}}}',
                'data' => array('a' => true),
                'expected' => ' {{{{b}}}} {{{{/b}}}} ',
            ),

            array(
                'id' => 177,
                'template' => '{{{{a}}}} {{{{b}}}} {{{{/b}}}} {{{{/a}}}}',
                'data' => array('a' => true),
                'options' => array(
                    'helpers' => array(
                        'a' => function($options) {
                            return $options['fn']();
                        }
                    )
                ),
                'expected' => ' {{{{b}}}} {{{{/b}}}} ',
            ),

            array(
                'id' => 177,
                'template' => '{{{{a}}}} {{{{b}}}} {{{{/b}}}} {{{{/a}}}}',
                'expected' => ''
            ),

            array(
                'id' => 199,
                'template' => '{{#if foo}}1{{else if bar}}2{{else}}3{{/if}}',
                'expected' => '3',
            ),

            array(
                'id' => 199,
                'template' => '{{#if foo}}1{{else if bar}}2{{/if}}',
                'data' => array('bar' => true),
                'expected' => '2',
            ),

            array(
                'id' => 200,
                'template' => '{{#unless 0}}1{{else if foo}}2{{else}}3{{/unless}}',
                'data' => array('foo' => false),
                'expected' => '1',
            ),
            array(
                'id' => 201,
                'template' => '{{#unless 0 includeZero=true}}1{{else if foo}}2{{else}}3{{/unless}}',
                'data' => array('foo' => true),
                'expected' => '2',
            ),
            array(
                'id' => 202,
                'template' => '{{#unless 0 includeZero=true}}1{{else if foo}}2{{else}}3{{/unless}}',
                'data' => array('foo' => false),
                'expected' => '3',
            ),

            array(
                'id' => 204,
                'template' => '{{#> test name="A"}}B{{/test}}{{#> test name="C"}}D{{/test}}',
                'data' => array('bar' => true),
                'options' => array(
                    'partials' => array(
                        'test' => '{{name}}:{{> @partial-block}},',
                    )
                ),
                'expected' => 'A:B,C:D,',
            ),

            array(
                'id' => 206,
                'template' => '{{#with bar}}{{#../foo}}YES!{{/../foo}}{{/with}}',
                'data' => array('foo' => 999, 'bar' => true),
                'expected' => 'YES!',
            ),

            array(
                'id' => 213,
                'template' => '{{#if foo}}foo{{else if bar}}{{#moo moo}}moo{{/moo}}{{/if}}',
                'data' => array('foo' => true),
                'options' => array(
                    'helpers' => array(
                        'moo' => function($arg1) {
                            return ($arg1 === null);
                         }
                    )
                ),
                'expected' => 'foo',
            ),

            array(
                'id' => 213,
                'template' => '{{#with .}}bad{{else}}Good!{{/with}}',
                'data' => array(),
                'expected' => 'Good!',
            ),

            array(
                'id' => 216,
                'template' => '{{foo.length}}',
                'data' => array('foo' => array()),
                'expected' => '0',
            ),

            array(
                'id' => 221,
                'template' => 'a{{ouch}}b',
                'options' => array(
                    'helpers' => $test_helpers
                ),
                'expected' => 'aokb',
            ),

            array(
                'id' => 221,
                'template' => 'a{{ouch}}b',
                'options' => array(
                    'helpers' => $test_helpers2
                ),
                'expected' => 'awa!b',
            ),

            array(
                'id' => 221,
                'template' => 'a{{ouch}}b',
                'options' => array(
                    'helpers' => $test_helpers3
                ),
                'expected' => 'awa!b',
            ),

            array(
                'id' => 224,
                'template' => '{{#> foo bar}}a,b,{{.}},{{!-- comment --}},d{{/foo}}',
                'data' => array('bar' => 'BA!'),
                'options' => array(
                    'partials' => array('foo' => 'hello, {{> @partial-block}}')
                ),
                'expected' => 'hello, a,b,BA!,,d',
            ),

            array(
                'id' => 224,
                'template' => '{{#> foo bar}}{{#if .}}OK! {{.}}{{else}}no bar{{/if}}{{/foo}}',
                'data' => array('bar' => 'BA!'),
                'options' => array(
                    'partials' => array('foo' => 'hello, {{> @partial-block}}')
                ),
                'expected' => 'hello, OK! BA!',
            ),

            array(
                'id' => 227,
                'template' => '{{#if moo}}A{{else if bar}}B{{else foo}}C{{/if}}',
                'options' => array(
                    'helpers' => array(
                        'foo' => function($options) {
                            return $options['fn']();
                         }
                    )
                ),
                'expected' => 'C'
            ),

            array(
                'id' => 227,
                'template' => '{{#if moo}}A{{else if bar}}B{{else with foo}}C{{.}}{{/if}}',
                'data' => array('foo' => 'D'),
                'expected' => 'CD'
            ),

            array(
                'id' => 227,
                'template' => '{{#if moo}}A{{else if bar}}B{{else each foo}}C{{.}}{{/if}}',
                'data' => array('foo' => array(1, 3, 5)),
                'expected' => 'C1C3C5'
            ),

            array(
                'id' => 229,
                'template' => '{{#if foo.bar.moo}}TRUE{{else}}FALSE{{/if}}',
                'data' => array(),
                'expected' => 'FALSE'
            ),

            array(
                'id' => 233,
                'template' => '{{#if foo}}FOO{{else}}BAR{{/if}}',
                'data' => array(),
                'options' => array(
                    'helpers' => array(
                        'if' => function($arg, $options) {
                            return $options['fn']();
                         }
                    )
                ),
                'expected' => 'FOO'
            ),

            array(
                'id' => 234,
                'template' => '{{> (lookup foo 2)}}',
                'data' => array('foo' => array('a', 'b', 'c')),
                'options' => array(
                    'partials' => array(
                        'a' => '1st',
                        'b' => '2nd',
                        'c' => '3rd'
                    )
                ),
                'expected' => '3rd'
            ),

            array(
                'id' => 235,
                'template' => '{{#> "myPartial"}}{{#> myOtherPartial}}{{ @root.foo}}{{/myOtherPartial}}{{/"myPartial"}}',
                'data' => array('foo' => 'hello!'),
                'options' => array(
                    'partials' => array(
                        'myPartial' => '<div>outer {{> @partial-block}}</div>',
                        'myOtherPartial' => '<div>inner {{> @partial-block}}</div>'
                    )
                ),
                'expected' => '<div>outer <div>inner hello!</div></div>',
            ),

            array(
                'id' => 236,
                'template' => 'A{{#> foo}}B{{#> bar}}C{{>moo}}D{{/bar}}E{{/foo}}F',
                'options' => array(
                    'partials' => array(
                        'foo' => 'FOO>{{> @partial-block}}<FOO',
                        'bar' => 'bar>{{> @partial-block}}<bar',
                        'moo' => 'MOO!',
                    )
                ),
                'expected' => 'AFOO>Bbar>CMOO!D<barE<FOOF'
            ),

            array(
                'id' => 241,
                'template' => '{{#>foo}}{{#*inline "bar"}}GOOD!{{#each .}}>{{.}}{{/each}}{{/inline}}{{/foo}}',
                'data' => array('1', '3', '5'),
                'options' => array(
                    'partials' => array(
                        'foo' => 'A{{#>bar}}BAD{{/bar}}B',
                        'moo' => 'oh'
                    )
                ),
                'expected' => 'AGOOD!>1>3>5B'
            ),

            array(
                'id' => 243,
                'template' => '{{lookup . 3}}',
                'data' => array('3' => 'OK'),
                'expected' => 'OK'
            ),

            array(
                'id' => 243,
                'template' => '{{lookup . "test"}}',
                'data' => array('test' => 'OK'),
                'expected' => 'OK'
            ),

            array(
                'id' => 244,
                'template' => '{{#>outer}}content{{/outer}}',
                'data' => array('test' => 'OK'),
                'options' => array(
                    'partials' => array(
                        'outer' => 'outer+{{#>nested}}~{{>@partial-block}}~{{/nested}}+outer-end',
                        'nested' => 'nested={{>@partial-block}}=nested-end'
                    )
                ),
                'expected' => 'outer+nested=~content~=nested-end+outer-end'
            ),

            array(
                'id' => 245,
                'template' => '{{#each foo}}{{#with .}}{{bar}}-{{../../name}}{{/with}}{{/each}}',
                'data' => array('name' => 'bad', 'foo' => array(
                    array('bar' => 1),
                    array('bar' => 2),
                )),
                'expected' => '1-2-'
            ),

            array(
                'id' => 252,
                'template' => '{{foo (lookup bar 1)}}',
                'data' => array('bar' => array(
                    'nil',
                    array(3, 5)
                )),
                'options' => array(
                    'helpers' => array(
                        'foo' => function($arg1) {
                            return is_array($arg1) ? 'OK' : 'bad';
                         }
                    )
                ),
                'expected' => 'OK'
            ),

            array(
                'id' => 253,
                'template' => '{{foo.bar}}',
                'data' => array('foo' => array('bar' => 'OK!')),
                'options' => array(
                    'helpers' => array(
                        'foo' => function() {
                            return 'bad';
                         }
                    )
                ),
                'expected' => 'OK!'
            ),

            array(
                'id' => 254,
                'template' => '{{#if a}}a{{else if b}}b{{else}}c{{/if}}{{#if a}}a{{else if b}}b{{/if}}',
                'data' => array('b' => 1),
                'expected' => 'bb'
            ),

            array(
                'id' => 255,
                'template' => '{{foo.length}}',
                'data' => array('foo' => array(1, 2)),
                'options' => array(
                ),
                'expected' => '2'
            ),

            array(
                'id' => 256,
                'template' => '{{lookup . "foo"}}',
                'data' => array('foo' => 'ok'),
                'expected' => 'ok'
            ),

            array(
                'id' => 257,
                'template' => '{{foo a=(foo a=(foo a="ok"))}}',
                'options' => array(
                    'helpers' => array(
                        'foo' => function($opt) {
                            return $opt['hash']['a'];
                        }
                    )
                ),
                'expected' => 'ok'
            ),

            array(
                'id' => 261,
                'template' => '{{#each foo as |bar|}}?{{bar.[0]}}{{/each}}',
                'data' => array('foo' => array(array('a'), array('b'))),
                'expected' => '?a?b'
            ),

            array(
                'id' => 267,
                'template' => '{{#each . as |v k|}}#{{k}}>{{v}}|{{.}}{{/each}}',
                'data' => array('a' => 'b', 'c' => 'd'),
                'expected' => '#a>b|b#c>d|d'
            ),

            array(
                'id' => 268,
                'template' => '{{foo}}{{bar}}',
                'options' => array(
                    'helpers' => array(
                        'foo' => function($opt) {
                            $opt['_this']['change'] = true;
                        },
                        'bar' => function($opt) {
                            return $opt['_this']['change'] ? 'ok' : 'bad';
                        }
                    )
                ),
                'expected' => 'ok'
            ),

            array(
                'id' => 278,
                'template' => '{{#foo}}-{{#bar}}={{moo}}{{/bar}}{{/foo}}',
                'data' => array(
                    'foo' => array(
                         array('bar' => 0, 'moo' => 'A'),
                         array('bar' => 1, 'moo' => 'B'),
                         array('bar' => false, 'moo' => 'C'),
                         array('bar' => true, 'moo' => 'D'),
                    )
                ),
                'expected' => '-=-=--=D'
            ),

            array(
                'id' => 281,
                'template' => '{{echo (echo "foo bar (moo).")}}',
                'options' => array(
                    'helpers' => array(
                        'echo' => function($arg1) {
                            return "ECHO: $arg1";
                        }
                    )
                ),
                'expected' => 'ECHO: ECHO: foo bar (moo).'
            ),

            array(
                'id' => 284,
                'template' => '{{> foo}}',
                'options' => array(
                    'partials' => array('foo' => "12'34")
                ),
                'expected' => "12'34"
            ),

            array(
                'id' => 284,
                'template' => '{{> (lookup foo 2)}}',
                'data' => array('foo' => array('a', 'b', 'c')),
                'options' => array(
                    'partials' => array(
                        'a' => '1st',
                        'b' => '2nd',
                        'c' => "3'r'd"
                    )
                ),
                'expected' => "3'r'd"
            ),

            array(
                'id' => 289,
                'template' => "1\n2\n{{~foo~}}\n3",
                'data' => array('foo' => 'OK'),
                'expected' => "1\n2OK3"
            ),

            array(
                'id' => 289,
                'template' => "1\n2\n{{#test}}\n3TEST\n{{/test}}\n4",
                'data' => array('test' => 1),
                'expected' => "1\n2\n3TEST\n4"
            ),

            array(
                'id' => 289,
                'template' => "1\n2\n{{~#test}}\n3TEST\n{{/test}}\n4",
                'data' => array('test' => 1),
                'expected' => "1\n23TEST\n4"
            ),

            array(
                'id' => 289,
                'template' => "1\n2\n{{#>test}}\n3TEST\n{{/test}}\n4",
                'expected' => "1\n2\n3TEST\n4"
            ),

            array(
                'id' => 289,
                'template' => "1\n2\n\n{{#>test}}\n3TEST\n{{/test}}\n4",
                'expected' => "1\n2\n\n3TEST\n4"
            ),

            array(
                'id' => 289,
                'template' => "1\n2\n\n{{#>test~}}\n\n3TEST\n{{/test}}\n4",
                'expected' => "1\n2\n\n3TEST\n4"
            ),

            array(
                'id' => 290,
                'template' => '{{foo}} }} OK',
                'data' => array(
                  'foo' => 'YES',
                ),
                'expected' => 'YES }} OK'
            ),

            array(
                'id' => 290,
                'template' => '{{foo}}{{#with "}"}}{{.}}{{/with}}OK',
                'data' => array(
                  'foo' => 'YES',
                ),
                'expected' => 'YES}OK'
            ),

            array(
                'id' => 290,
                'template' => '{ {{foo}}',
                'data' => array(
                  'foo' => 'YES',
                ),
                'expected' => '{ YES'
            ),

            array(
                'id' => 290,
                'template' => '{{#with "{{"}}{{.}}{{/with}}{{foo}}{{#with "{{"}}{{.}}{{/with}}',
                'data' => array(
                  'foo' => 'YES',
                ),
                'expected' => '{{YES{{'
            ),

            [
                'id' => 297,
                'template' => '{{test "foo" prop="\" "}}',
                'options' => [
                    'helpers' => [
                        'test' => function ($arg1, $options) {
                            return "{$arg1} {$options['hash']['prop']}";
                        },
                    ],
                ],
                'expected' => 'foo &quot; '
            ],

            array(
                'id' => 302,
                'template' => "{{#*inline \"t1\"}}{{#if imageUrl}}<span />{{else}}<div />{{/if}}{{/inline}}{{#*inline \"t2\"}}{{#if imageUrl}}<span />{{else}}<div />{{/if}}{{/inline}}{{#*inline \"t3\"}}{{#if imageUrl}}<span />{{else}}<div />{{/if}}{{/inline}}",
                'expected' => '',
            ),

            array(
                'id' => 303,
                'template' => '{{#*inline "t1"}} {{#if url}} <a /> {{else if imageUrl}} <img /> {{else}} <span /> {{/if}} {{/inline}}',
                'expected' => ''
            ),

            array(
                'id' => 315,
                'template' => '{{#each foo}}#{{@key}}({{@index}})={{.}}-{{moo}}-{{@irr}}{{/each}}',
                'options' => array(
                    'helpers' => array(
                        'moo' => function($opts) {
                            $opts['data']['irr'] = '123';
                            return '321';
                        }
                    )
                ),
                'data' => array(
                    'foo' => array(
                        'a' => 'b',
                        'c' => 'd',
                        'e' => 'f',
                    )
                ),
                'expected' => '#a(0)=b-321-123#c(1)=d-321-123#e(2)=f-321-123'
            ),

            [
                'id' => 357,
                'template' => '{{echo (echo "foobar(moo).")}}',
                'options' => [
                    'helpers' => [
                        'echo' => function ($arg1) {
                            return "ECHO: $arg1";
                        }
                    ]
                ],
                'expected' => 'ECHO: ECHO: foobar(moo).'
            ],
            [
                'id' => 357,
                'template' => '{{echo (echo "foobar(moo)." (echo "moobar(foo)"))}}',
                'options' => [
                    'helpers' => [
                        'echo' => function ($arg1) {
                            return "ECHO: $arg1";
                        }
                    ]
                ],
                'expected' => 'ECHO: ECHO: foobar(moo).'
            ],

            array(
                'id' => 369,
                'template' => '{{#each paragraphs}}<p>{{this}}</p>{{else}}<p class="empty">{{foo}}</p>{{/each}}',
                'data' => array('foo' => 'bar'),
                'expected' => '<p class="empty">bar</p>'
            ),

            array(
                'template' => '{{#each . as |v k|}}#{{k}}{{/each}}',
                'data' => array('a' => array(), 'c' => array()),
                'expected' => '#a#c'
            ),

            array(
                'template' => '{{testNull null undefined 1}}',
                'data' => 'test',
                'options' => array(
                    'helpers' => array(
                        'testNull' => function($arg1, $arg2) {
                            return (($arg1 === null) && ($arg2 === null)) ? 'YES!' : 'no';
                        }
                    )
                ),
                'expected' => 'YES!'
            ),

            array(
                'template' => '{{> (pname foo) bar}}',
                'data' => array('bar' => 'OK! SUBEXP+PARTIAL!', 'foo' => 'test/test3'),
                'options' => array(
                    'helpers' => array(
                        'pname' => function($arg) {
                            return $arg;
                         }
                    ),
                    'partials' => array('test/test3' => '{{.}}'),
                ),
                'expected' => 'OK! SUBEXP+PARTIAL!'
            ),

            array(
                'template' => '{{> testpartial newcontext mixed=foo}}',
                'data' => array('foo' => 'OK!', 'newcontext' => array('bar' => 'test')),
                'options' => array(
                    'partials' => array('testpartial' => '{{bar}}-{{mixed}}'),
                ),
                'expected' => 'test-OK!'
            ),

            array(
                'template' => '{{[helper]}}',
                'options' => array(
                    'helpers' => array(
                        'helper' => function () {
                            return 'DEF';
                        }
                    )
                ),
                'data' => array(),
                'expected' => 'DEF'
            ),

            array(
                'template' => '{{#[helper3]}}ABC{{/[helper3]}}',
                'options' => array(
                    'helpers' => array(
                        'helper3' => function () {
                            return 'DEF';
                        }
                    )
                ),
                'data' => array(),
                'expected' => 'DEF'
            ),

            array(
                'template' => '{{hash abc=["def=123"]}}',
                'options' => array(
                    'helpers' => array(
                        'hash' => function ($options) {
                            $ret = '';
                            foreach ($options['hash'] as $k => $v) {
                                $ret .= "$k : $v,";
                            }
                            return $ret;
                        }
                    ),
                ),
                'data' => array('"def=123"' => 'La!'),
                'expected' => 'abc : La!,',
            ),

            array(
                'template' => '{{hash abc=[\'def=123\']}}',
                'options' => array(
                    'helpers' => array(
                        'hash' => function ($options) {
                            $ret = '';
                            foreach ($options['hash'] as $k => $v) {
                                $ret .= "$k : $v,";
                            }
                            return $ret;
                        }
                    ),
                ),
                'data' => array("'def=123'" => 'La!'),
                'expected' => 'abc : La!,',
            ),

            array(
                'template' => 'ABC{{#block "YES!"}}DEF{{foo}}GHI{{else}}NO~{{/block}}JKL',
                'options' => array(
                    'helpers' => array(
                        'block' => function ($name, $options) {
                            return "1-$name-2-" . $options['fn']() . '-3';
                        }
                    ),
                ),
                'data' => array('foo' => 'bar'),
                'expected' => 'ABC1-YES!-2-DEFbarGHI-3JKL',
            ),

            array(
                'template' => '-{{getroot}}=',
                'options' => array(
                    'helpers' => array('getroot'),
                ),
                'data' => 'ROOT!',
                'expected' => '-ROOT!=',
            ),

            array(
                'template' => 'A{{#each .}}-{{#each .}}={{.}},{{@key}},{{@index}},{{@../index}}~{{/each}}%{{/each}}B',
                'data' => array(array('a' => 'b'), array('c' => 'd'), array('e' => 'f')),
                'expected' => 'A-=b,a,0,0~%-=d,c,0,1~%-=f,e,0,2~%B',
            ),

            array(
                'template' => 'ABC{{#block "YES!"}}TRUE{{else}}DEF{{foo}}GHI{{/block}}JKL',
                'options' => array(
                    'helpers' => array(
                        'block' => function ($name, $options) {
                            return "1-$name-2-" . $options['inverse']() . '-3';
                        }
                    ),
                ),
                'data' => array('foo' => 'bar'),
                'expected' => 'ABC1-YES!-2-DEFbarGHI-3JKL',
            ),

            array(
                'template' => '{{#each .}}{{..}}>{{/each}}',
                'data' => array('a', 'b', 'c'),
                'expected' => 'a,b,c>a,b,c>a,b,c>',
            ),

            array(
                'template' => '{{#each .}}->{{>tests/test3}}{{/each}}',
                'data' => array('a', 'b', 'c'),
                'options' => array(
                    'partials' => array(
                        'tests/test3' => 'New context:{{.}}'
                    ),
                ),
                'expected' => "->New context:a->New context:b->New context:c",
            ),

            array(
                'template' => '{{#each .}}->{{>tests/test3 ../foo}}{{/each}}',
                'data' => array('a', 'foo' => array('d', 'e', 'f')),
                'options' => array(
                    'partials' => array(
                        'tests/test3' => 'New context:{{.}}'
                    ),
                ),
                'expected' => "->New context:d,e,f->New context:d,e,f",
            ),

            array(
                'template' => '{{{"{{"}}}',
                'data' => array('{{' => ':D'),
                'expected' => ':D',
            ),

            array(
                'template' => '{{{\'{{\'}}}',
                'data' => array('{{' => ':D'),
                'expected' => ':D',
            ),

            array(
                'template' => '{{#with "{{"}}{{.}}{{/with}}',
                'expected' => '{{',
            ),

            array(
                'template' => '{{good_helper}}',
                'options' => array(
                    'helpers' => array('good_helper' => 'foo::bar'),
                ),
                'expected' => 'OK!',
            ),

            array(
                'template' => '-{{.}}-',
                'options' => array(),
                'data' => 'abc',
                'expected' => '-abc-',
            ),

            array(
                'template' => '-{{this}}-',
                'options' => array(),
                'data' => 123,
                'expected' => '-123-',
            ),

            array(
                'template' => '{{#if .}}YES{{else}}NO{{/if}}',
                'data' => true,
                'expected' => 'YES',
            ),

            array(
                'template' => '{{foo}}',
                'data' => array('foo' => 'OK'),
                'expected' => 'OK',
            ),

            array(
                'template' => '{{foo}}',
                'expected' => '',
            ),

            array(
                'template' => '{{#myif foo}}YES{{else}}NO{{/myif}}',
                'options' => array(
                    'helpers' => array('myif'),
                ),
                'expected' => 'NO',
            ),

            array(
                'template' => '{{#myif foo}}YES{{else}}NO{{/myif}}',
                'data' => array('foo' => 1),
                'options' => array(
                    'helpers' => array('myif'),
                ),
                'expected' => 'YES',
            ),

            array(
                'template' => '{{#mylogic 0 foo bar}}YES:{{.}}{{else}}NO:{{.}}{{/mylogic}}',
                'data' => array('foo' => 'FOO', 'bar' => 'BAR'),
                'options' => array(
                    'helpers' => array('mylogic'),
                ),
                'expected' => 'NO:BAR',
            ),

            array(
                'template' => '{{#mylogic true foo bar}}YES:{{.}}{{else}}NO:{{.}}{{/mylogic}}',
                'data' => array('foo' => 'FOO', 'bar' => 'BAR'),
                'options' => array(
                    'helpers' => array('mylogic'),
                ),
                'expected' => 'YES:FOO',
            ),

            array(
                'template' => '{{#mywith foo}}YA: {{name}}{{/mywith}}',
                'data' => array('name' => 'OK?', 'foo' => array('name' => 'OK!')),
                'options' => array(
                    'helpers' => array('mywith'),
                ),
                'expected' => 'YA: OK!',
            ),

            array(
                'template' => '{{mydash \'abc\' "dev"}}',
                'data' => array('a' => 'a', 'b' => 'b', 'c' => array('c' => 'c'), 'd' => 'd', 'e' => 'e'),
                'options' => array(
                    'helpers' => array('mydash'),
                ),
                'expected' => 'abc-dev',
            ),

            array(
                'template' => '{{mydash \'a b c\' "d e f"}}',
                'data' => array('a' => 'a', 'b' => 'b', 'c' => array('c' => 'c'), 'd' => 'd', 'e' => 'e'),
                'options' => array(
                    'helpers' => array('mydash'),
                ),
                'expected' => 'a b c-d e f',
            ),

            array(
                'template' => '{{mydash "abc" (test_array 1)}}',
                'data' => array('a' => 'a', 'b' => 'b', 'c' => array('c' => 'c'), 'd' => 'd', 'e' => 'e'),
                'options' => array(
                    'helpers' => array('mydash', 'test_array'),
                ),
                'expected' => 'abc-NOT_ARRAY',
            ),

            array(
                'template' => '{{mydash "abc" (myjoin a b)}}',
                'data' => array('a' => 'a', 'b' => 'b', 'c' => array('c' => 'c'), 'd' => 'd', 'e' => 'e'),
                'options' => array(
                    'helpers' => array('mydash', 'myjoin'),
                ),
                'expected' => 'abc-ab',
            ),

            array(
                'template' => '{{#with people}}Yes , {{name}}{{else}}No, {{name}}{{/with}}',
                'data' => array('people' => array('name' => 'Peter'), 'name' => 'NoOne'),
                'expected' => 'Yes , Peter',
            ),

            array(
                'template' => '{{#with people}}Yes , {{name}}{{else}}No, {{name}}{{/with}}',
                'data' => array('name' => 'NoOne'),
                'expected' => 'No, NoOne',
            ),

            array(
                'template' => <<<VAREND
<ul>
 <li>1. {{helper1 name}}</li>
 <li>2. {{helper1 value}}</li>
 <li>3. {{myClass::helper2 name}}</li>
 <li>4. {{myClass::helper2 value}}</li>
 <li>5. {{he name}}</li>
 <li>6. {{he value}}</li>
 <li>7. {{h2 name}}</li>
 <li>8. {{h2 value}}</li>
 <li>9. {{link name}}</li>
 <li>10. {{link value}}</li>
 <li>11. {{alink url text}}</li>
 <li>12. {{{alink url text}}}</li>
</ul>
VAREND
                ,
                'data' => array('name' => 'John', 'value' => 10000, 'url' => 'http://yahoo.com', 'text' => 'You&Me!'),
                'options' => array(
                    'helpers' => array(
                        'helper1',
                        'myClass::helper2',
                        'he' => 'helper1',
                        'h2' => 'myClass::helper2',
                        'link' => function ($arg) {
                            if (is_array($arg)) {
                                $arg = 'Array';
                            }
                            return "<a href=\"{$arg}\">click here</a>";
                        },
                        'alink',
                    )
                ),
                'expected' => <<<VAREND
<ul>
 <li>1. -John-</li>
 <li>2. -10000-</li>
 <li>3. &#x3D;John&#x3D;</li>
 <li>4. &#x3D;10000&#x3D;</li>
 <li>5. -John-</li>
 <li>6. -10000-</li>
 <li>7. &#x3D;John&#x3D;</li>
 <li>8. &#x3D;10000&#x3D;</li>
 <li>9. &lt;a href&#x3D;&quot;John&quot;&gt;click here&lt;/a&gt;</li>
 <li>10. &lt;a href&#x3D;&quot;10000&quot;&gt;click here&lt;/a&gt;</li>
 <li>11. &lt;a href&#x3D;&quot;http://yahoo.com&quot;&gt;You&amp;Me!&lt;/a&gt;</li>
 <li>12. <a href="http://yahoo.com">You&Me!</a></li>
</ul>
VAREND
            ),

            array(
                'template' => '{{#each foo}}{{@key}}: {{.}},{{/each}}',
                'data' => array('foo' => array(1,'a'=>'b',5)),
                'expected' => '0: 1,a: b,1: 5,',
            ),

            array(
                'template' => '{{#each foo}}{{@key}}: {{.}},{{/each}}',
                'data' => array('foo' => new twoDimensionIterator(2, 3)),
                'expected' => '0x0: 0,1x0: 0,0x1: 0,1x1: 1,0x2: 0,1x2: 2,',
            ),

            array(
                'template' => "   {{#if foo}}\nYES\n{{else}}\nNO\n{{/if}}\n",
                'expected' => "NO\n",
            ),

            array(
                'template' => "  {{#each foo}}\n{{@key}}: {{.}}\n{{/each}}\nDONE",
                'data' => array('foo' => array('a' => 'A', 'b' => 'BOY!')),
                'expected' => "a: A\nb: BOY!\nDONE",
            ),

            array(
                'template' => "{{>test1}}\n  {{>test1}}\nDONE\n",
                'options' => array(
                    'partials' => array('test1' => "1:A\n 2:B\n  3:C\n 4:D\n5:E\n"),
                ),
                'expected' => "1:A\n 2:B\n  3:C\n 4:D\n5:E\n  1:A\n   2:B\n    3:C\n   4:D\n  5:E\nDONE\n",
            ),

            array(
                'template' => "{{>test1}}\n  {{>test1}}\nDONE\n",
                'options' => array(
                    'flags' => LightnCandy::FLAG_PREVENTINDENT,
                    'partials' => array('test1' => "1:A\n 2:B\n  3:C\n 4:D\n5:E\n"),
                ),
                'expected' => "1:A\n 2:B\n  3:C\n 4:D\n5:E\n  1:A\n 2:B\n  3:C\n 4:D\n5:E\nDONE\n",
            ),

            array(
                'template' => "{{foo}}\n  {{bar}}\n",
                'data' => array('foo' => 'ha', 'bar' => 'hey'),
                'options' => array(
                ),
                'expected' => "ha\n  hey\n",
            ),

            array(
                'template' => "{{>test}}\n",
                'data' => array('foo' => 'ha', 'bar' => 'hey'),
                'options' => array(
                    'partials' => array('test' => "{{foo}}\n  {{bar}}\n"),
                ),
                'expected' => "ha\n  hey\n",
            ),

            array(
                'template' => " {{>test}}\n",
                'data' => array('foo' => 'ha', 'bar' => 'hey'),
                'options' => array(
                    'flags' => LightnCandy::FLAG_PREVENTINDENT,
                    'partials' => array('test' => "{{foo}}\n  {{bar}}\n"),
                ),
                'expected' => " ha\n  hey\n",
            ),

            array(
                'template' => "\n {{>test}}\n",
                'data' => array('foo' => 'ha', 'bar' => 'hey'),
                'options' => array(
                    'flags' => LightnCandy::FLAG_PREVENTINDENT,
                    'partials' => array('test' => "{{foo}}\n  {{bar}}\n"),
                ),
                'expected' => "\n ha\n  hey\n",
            ),

            array(
                'template' => "\n{{#each foo~}}\n  <li>{{.}}</li>\n{{~/each}}\n\nOK",
                'data' => array('foo' => array('ha', 'hu')),
                'expected' => "\n<li>ha</li><li>hu</li>\nOK",
            ),

            array(
                'template' => "ST:\n{{#foo}}\n {{>test1}}\n{{/foo}}\nOK\n",
                'data' => array('foo' => array(1, 2)),
                'options' => array(
                    'partials' => array('test1' => "1:A\n 2:B({{@index}})\n"),
                ),
                'expected' => "ST:\n 1:A\n  2:B(0)\n 1:A\n  2:B(1)\nOK\n",
            ),

            array(
                'template' => ">{{helper1 \"===\"}}<",
                'options' => array(
                    'helpers' => array(
                        'helper1',
                    )
                ),
                'expected' => ">-&#x3D;&#x3D;&#x3D;-<",
            ),

            array(
                'template' => "{{foo}}",
                'data' => array('foo' => 'A&B " \''),
                'options' => array('flags' => LightnCandy::FLAG_NOESCAPE),
                'expected' => "A&B \" '",
            ),

            array(
                'template' => "{{foo}}",
                'data' => array('foo' => 'A&B " \' ='),
                'expected' => "A&amp;B &quot; &#x27; &#x3D;",
            ),

            array(
                'template' => "{{foo}}",
                'data' => array('foo' => '<a href="#">\'</a>'),
                'expected' => '&lt;a href&#x3D;&quot;#&quot;&gt;&#x27;&lt;/a&gt;',
            ),

            array(
                'template' => '{{#>foo}}inline\'partial{{/foo}}',
                'expected' => 'inline\'partial',
            ),

            array(
                'template' => "{{#> testPartial}}\n ERROR: testPartial is not found!\n  {{#> innerPartial}}\n   ERROR: innerPartial is not found!\n   ERROR: innerPartial is not found!\n  {{/innerPartial}}\n ERROR: testPartial is not found!\n {{/testPartial}}",
                'expected' => " ERROR: testPartial is not found!\n   ERROR: innerPartial is not found!\n   ERROR: innerPartial is not found!\n ERROR: testPartial is not found!\n",
            ),

        );

        return array_map(function($i) {
            return array($i);
        }, $issues);
    }
}

