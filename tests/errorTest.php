<?php

use LightnCandy\LightnCandy;
use LightnCandy\Runtime;
use PHPUnit\Framework\TestCase;

require_once('tests/helpers_for_test.php');

class errorTest extends TestCase
{
    public function testException()
    {
        try {
          $php = LightnCandy::compile('{{{foo}}');
        } catch (\Exception $E) {
            $this->assertEquals('Bad token {{{foo}} ! Do you mean {{foo}} or {{{foo}}}?', $E->getMessage());
        }
    }

    public function testLog()
    {
        $php = LightnCandy::compile('{{log foo}}');
        $renderer = LightnCandy::prepare($php);

        date_default_timezone_set('GMT');
        $tmpDir = sys_get_temp_dir();
        $tmpFile = tempnam($tmpDir, 'terr_');
        ini_set('error_log', $tmpFile);

        $renderer(array('foo' => 'OK!'));

        $contents = array_map(function ($l) {
            $l = rtrim($l);
            preg_match('/GMT\] (.+)/', $l, $m);
            return $m[1] ?? $l;
        }, file($tmpFile));

        $this->assertEquals(['array (', "  0 => 'OK!',", ')'], $contents);
        ini_restore('error_log');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider("renderErrorProvider")]
    public function testRenderingException($test)
    {
        $php = LightnCandy::compile($test['template'], $test['options'] ?? []);
        $renderer = LightnCandy::prepare($php);
        try {
            $renderer($test['data'] ?? null);
        } catch (\Exception $E) {
            $this->assertEquals($test['expected'], $E->getMessage());
            return;
        }
        $this->fail("Expected to throw exception: {$test['expected']} . CODE: $php");
    }

    public static function renderErrorProvider()
    {
        $errorCases = array(
             array(
                 'template' => "{{#> testPartial}}\n  {{#> innerPartial}}\n   {{> @partial-block}}\n  {{/innerPartial}}\n{{/testPartial}}",
                 'options' => array(
                   'flags' => LightnCandy::FLAG_RUNTIMEPARTIAL,
                   'partials' => array(
                     'testPartial' => 'testPartial => {{> @partial-block}} <=',
                     'innerPartial' => 'innerPartial -> {{> @partial-block}} <-',
                   ),
                 ),
                 'expected' => "Can not find partial named as '@partial-block' !!",
             ),
             array(
                 'template' => '{{> @partial-block}}',
                 'options' => array(
                   'flags' => LightnCandy::FLAG_RUNTIMEPARTIAL,
                 ),
                 'expected' => "Can not find partial named as '@partial-block' !!",
             ),
             array(
                 'template' => '{{{foo}}}',
                 'options' => [
                     'flags' => LightnCandy::FLAG_STRICT,
                 ],
                 'expected' => 'Runtime: [foo] does not exist',
             ),
             array(
                 'template' => '{{foo}}',
                 'options' => array(
                     'helpers' => array(
                         'foo' => function () {
                             throw new Exception('Expect the unexpected');
                         }
                     ),
                 ),
                 'expected' => 'Runtime: call custom helper \'foo\' error: Expect the unexpected',
             ),
        );

        return array_map(function($i) {
            return array($i);
        }, $errorCases);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider("errorProvider")]
    public function testErrors($test)
    {
        try {
            $php = LightnCandy::compile($test['template'], $test['options']);
        } catch (\Exception $e) {
            $this->assertEquals($test['expected'], explode("\n", $e->getMessage()));
        }

        // This case should be compiled without error
        if (!isset($test['expected'])) {
            $this->assertEquals(true, true);
        }
    }

    public static function errorProvider()
    {
        $errorCases = array(
            array(
                'template' => '{{testerr1}}}',
                'expected' => 'Bad token {{testerr1}}} ! Do you mean {{testerr1}} or {{{testerr1}}}?',
            ),
            array(
                'template' => '{{{testerr2}}',
                'expected' => 'Bad token {{{testerr2}} ! Do you mean {{testerr2}} or {{{testerr2}}}?',
            ),
            array(
                'template' => '{{{#testerr3}}}',
                'expected' => 'Bad token {{{#testerr3}}} ! Do you mean {{#testerr3}} ?',
            ),
            array(
                'template' => '{{{!testerr4}}}',
                'expected' => 'Bad token {{{!testerr4}}} ! Do you mean {{!testerr4}} ?',
            ),
            array(
                'template' => '{{{^testerr5}}}',
                'expected' => 'Bad token {{{^testerr5}}} ! Do you mean {{^testerr5}} ?',
            ),
            array(
                'template' => '{{{/testerr6}}}',
                'expected' => 'Bad token {{{/testerr6}}} ! Do you mean {{/testerr6}} ?',
            ),
            array(
                'template' => '{{win[ner.test1}}',
                'expected' => array(
                    "Error in 'win[ner.test1': expect ']' but the token ended!!",
                    'Wrong variable naming in {{win[ner.test1}}',
                ),
            ),
            array(
                'template' => '{{win]ner.test2}}',
                'expected' => 'Wrong variable naming as \'win]ner.test2\' in {{win]ner.test2}} !',
            ),
            array(
                'template' => '{{wi[n]ner.test3}}',
                'expected' => array(
                    'Wrong variable naming as \'wi[n]ner.test3\' in {{wi[n]ner.test3}} !',
                    "Unexpected charactor in 'wi[n]ner.test3' ! (should it be 'wi.[n].ner.test3' ?)",
                ),
            ),
            array(
                'template' => '{{winner].[test4]}}',
                'expected' => array(
                    'Wrong variable naming as \'winner].[test4]\' in {{winner].[test4]}} !',
                    "Unexpected charactor in 'winner].[test4]' ! (should it be 'winner.[test4]' ?)",
                ),
            ),
            array(
                'template' => '{{winner[.test5]}}',
                'expected' => array(
                    'Wrong variable naming as \'winner[.test5]\' in {{winner[.test5]}} !',
                    "Unexpected charactor in 'winner[.test5]' ! (should it be 'winner.[.test5]' ?)",
                ),
            ),
            array(
                'template' => '{{winner.[.test6]}}',
            ),
            array(
                'template' => '{{winner.[#te.st7]}}',
            ),
            array(
                'template' => '{{test8}}',
            ),
            array(
                'template' => '{{test9]}}',
                'expected' => array(
                    'Wrong variable naming as \'test9]\' in {{test9]}} !',
                    "Unexpected charactor in 'test9]' ! (should it be 'test9' ?)",
                ),
            ),
            array(
                'template' => '{{testA[}}',
                'expected' => array(
                    "Error in 'testA[': expect ']' but the token ended!!",
                    'Wrong variable naming in {{testA[}}',
                ),
            ),
            array(
                'template' => '{{[testB}}',
                'expected' => array(
                    "Error in '[testB': expect ']' but the token ended!!",
                    'Wrong variable naming in {{[testB}}',
                ),
            ),
            array(
                'template' => '{{]testC}}',
                'expected' => array(
                    'Wrong variable naming as \']testC\' in {{]testC}} !',
                    "Unexpected charactor in ']testC' ! (should it be 'testC' ?)",
                )
            ),
            array(
                'template' => '{{[testD]}}',
            ),
            array(
                'template' => '{{te]stE}}',
                'expected' => 'Wrong variable naming as \'te]stE\' in {{te]stE}} !',
            ),
            array(
                'template' => '{{tee[stF}}',
                'expected' => array(
                    "Error in 'tee[stF': expect ']' but the token ended!!",
                    'Wrong variable naming in {{tee[stF}}',
                )
            ),
            array(
                'template' => '{{te.e[stG}}',
                'expected' => array(
                    "Error in 'te.e[stG': expect ']' but the token ended!!",
                    'Wrong variable naming in {{te.e[stG}}',
                ),
            ),
            array(
                'template' => '{{te.e]stH}}',
                'expected' => 'Wrong variable naming as \'te.e]stH\' in {{te.e]stH}} !',
            ),
            array(
                'template' => '{{te.e[st.endI}}',
                'expected' => array(
                    "Error in 'te.e[st.endI': expect ']' but the token ended!!",
                    'Wrong variable naming in {{te.e[st.endI}}',
                ),
            ),
            array(
                'template' => '{{te.e]st.endJ}}',
                'expected' => 'Wrong variable naming as \'te.e]st.endJ\' in {{te.e]st.endJ}} !',
            ),
            array(
                'template' => '{{te.[est].endK}}',
            ),
            array(
                'template' => '{{te.t[est].endL}}',
                'expected' => array(
                    'Wrong variable naming as \'te.t[est].endL\' in {{te.t[est].endL}} !',
                    "Unexpected charactor in 'te.t[est].endL' ! (should it be 'te.t.[est].endL' ?)",
                ),
            ),
            array(
                'template' => '{{te.t[est]o.endM}}',
                'expected' => array(
                    'Wrong variable naming as \'te.t[est]o.endM\' in {{te.t[est]o.endM}} !',
                    "Unexpected charactor in 'te.t[est]o.endM' ! (should it be 'te.t.[est].o.endM' ?)"
                ),
            ),
            array(
                'template' => '{{te.[est]o.endN}}',
                'expected' => array(
                    'Wrong variable naming as \'te.[est]o.endN\' in {{te.[est]o.endN}} !',
                    "Unexpected charactor in 'te.[est]o.endN' ! (should it be 'te.[est].o.endN' ?)",
                ),
            ),
            array(
                'template' => '{{te.[e.st].endO}}',
            ),
            array(
                'template' => '{{te.[e.s[t].endP}}',
            ),
            array(
                'template' => '{{te.[e[s.t].endQ}}',
            ),
            array(
                'template' => '{{helper}}',
                'options' => array('helpers' => array(
                    'helper' => array('bad input'),
                )),
                'expected' => 'I found an array in helpers with key as helper, please fix it.',
            ),
            array(
                'template' => '<ul>{{#each item}}<li>{{name}}</li>',
                'expected' => 'Unclosed token {{#each item}} !!',
            ),
            array(
                'template' => 'issue63: {{test_join}} Test! {{this}} {{/test_join}}',
                'expected' => 'Unexpect token: {{/test_join}} !',
            ),
            array(
                'template' => '{{#if a}}TEST{{/with}}',
                'expected' => 'Unexpect token: {{/with}} !',
            ),
            array(
                'template' => '{{#foo}}error{{/bar}}',
                'expected' => 'Unexpect token {{/bar}} ! Previous token {{#[foo]}} is not closed',
            ),
            array(
                'template' => '{{a=b}}',
                'expected' => 'Do not support name=value in {{a=b}}, you should use it after a custom helper.',
            ),
            array(
                'template' => '{{#with a}OK!{{/with}}',
                'expected' => array(
                    'Wrong variable naming as \'a}OK!{{/with\' in {{#with a}OK!{{/with}} ! You should wrap ! " # % & \' * + , ; < = > { | } ~ into [ ]',
                    'Unclosed token {{#with a}OK!{{/with}} !!',
                ),
            ),
            array(
                'template' => '{{#each a}OK!{{/each}}',
                'expected' => array(
                    'Wrong variable naming as \'a}OK!{{/each\' in {{#each a}OK!{{/each}} ! You should wrap ! " # % & \' * + , ; < = > { | } ~ into [ ]',
                    'Unclosed token {{#each a}OK!{{/each}} !!',
                ),
            ),
            array(
                'template' => '{{#with items}}OK!{{/with}}',
            ),
            array(
                'template' => '{{#with}}OK!{{/with}}',
                'expected' => '#with requires exactly one argument',
            ),
            array(
                'template' => '{{#if}}OK!{{/if}}',
                'expected' => '#if requires exactly one argument',
            ),
            array(
                'template' => '{{#unless}}OK!{{/unless}}',
                'expected' => '#unless requires exactly one argument',
            ),
            array(
                'template' => '{{#each}}OK!{{/each}}',
                'expected' => 'No argument after {{#each}} !',
            ),
            array(
                'template' => '{{lookup}}',
                'expected' => 'No argument after {{lookup}} !',
            ),
            array(
                'template' => '{{lookup foo}}',
                'expected' => '{{lookup}} requires 2 arguments !',
            ),
            array(
                'template' => '{{#test foo}}{{/test}}',
                'expected' => 'Custom helper not found: test in {{#test foo}} !',
            ),
            array(
                'template' => '{{>not_found}}',
                'expected' => "Can not find partial for 'not_found', you should provide partials in options",
            ),
            array(
                'template' => '{{>tests/test1 foo}}',
                'options' => array('partials' => array('tests/test1' => '')),
                'expected' => 'Do not support {{>tests/test1 foo}}, you should do compile with LightnCandy::FLAG_RUNTIMEPARTIAL flag',
            ),
            array(
                'template' => '{{abc}}',
                'options' => array('helpers' => array('abc')),
                'expected' => "You provide a custom helper named as 'abc' in options['helpers'], but the function abc() is not defined!",
            ),
            array(
                'template' => '{{>recursive}}',
                'options' => array('partials' => array('recursive' => '{{>recursive}}')),
                'expected' => array(
                    'I found recursive partial includes as the path: recursive -> recursive! You should fix your template or compile with LightnCandy::FLAG_RUNTIMEPARTIAL flag.',
                )
            ),
            array(
                'template' => '{{test_join (foo bar)}}',
                'options' => array(
                    'helpers' => array('test_join'),
                ),
                'expected' => "Can not find custom helper function defination foo() !",
            ),
            array(
                'template' => '{{1 + 2}}',
                'options' => array(
                    'helpers' => array('test_join'),
                ),
                'expected' => "Wrong variable naming as '+' in {{1 + 2}} ! You should wrap ! \" # % & ' * + , ; < = > { | } ~ into [ ]",
            ),
            array(
                'template' => '{{> (foo) bar}}',
                'expected' => array(
                    "Can not find custom helper function defination foo() !",
                    "You use dynamic partial name as '(foo)', this only works with option FLAG_RUNTIMEPARTIAL enabled",
                )
            ),
            array(
                'template' => '{{{{#foo}}}',
                'expected' => array(
                    'Bad token {{{{#foo}}} ! Do you mean {{{{#foo}}}} ?',
                    'Wrong raw block begin with {{{{#foo}}} ! Remove "#" to fix this issue.',
                    'Unclosed token {{{{foo}}}} !!',
                )
            ),
            array(
                'template' => '{{{{foo}}}} {{ {{{{/bar}}}}',
                'expected' => array(
                    'Unclosed token {{{{foo}}}} !!',
                )
            ),
            array(
                'template' => '{{foo (foo (foo 1 2) 3))}}',
                'options' => array(
                     'helpers' => array(
                         'foo' => function () {
                             return;
                         }
                     )
                ),
                'expected' => array(
                    'Unexcepted \')\' in expression \'foo (foo (foo 1 2) 3))\' !!',
                )
            ),
            array(
                'template' => '{{{{foo}}}} {{ {{{{#foo}}}}',
                'expected' => array(
                    'Unclosed token {{{{foo}}}} !!',
                )
            ),
            array(
                'template' => '{{else}}',
                'expected' => array(
                    '{{else}} only valid in if, unless, each, and #section context',
                )
            ),
            array(
                'template' => '{{log}}',
                'expected' => array(
                    'No argument after {{log}} !',
                )
            ),
            array(
                'template' => '{{#*inline test}}{{/inline}}',
                'expected' => array(
                    'Do not support {{#*inline test}}, you should do compile with LightnCandy::FLAG_RUNTIMEPARTIAL flag',
                )
            ),
            array(
                'template' => '{{#*help me}}{{/help}}',
                'options' => array(
                    'flags' => LightnCandy::FLAG_RUNTIMEPARTIAL,
                ),
                'expected' => array(
                    'Do not support {{#*help me}}, now we only support {{#*inline "partialName"}}template...{{/inline}}'
                )
            ),
            array(
                'template' => '{{#*inline}}{{/inline}}',
                'options' => array(
                    'flags' => LightnCandy::FLAG_RUNTIMEPARTIAL,
                ),
                'expected' => array(
                    'Error in {{#*inline}}: inline require 1 argument for partial name!',
                )
            ),
            array(
                'template' => '{{#>foo}}bar',
                'options' => array(
                    'flags' => LightnCandy::FLAG_RUNTIMEPARTIAL,
                ),
                'expected' => array(
                    'Unclosed token {{#>foo}} !!',
                )
            ),
            array(
                'template' => '{{ #2 }}',
                'options' => array(
                    'flags' => 0,
                ),
                'expected' => array(
                    'Unclosed token {{#2}} !!',
                )
            ),
        );

        return array_map(function($i) {
            if (!isset($i['options'])) {
                $i['options'] = array('flags' => 0);
            }
            if (!isset($i['options']['flags'])) {
                $i['options']['flags'] = 0;
            }
            if (isset($i['expected']) && !is_array($i['expected'])) {
                $i['expected'] = array($i['expected']);
            }
            return array($i);
        }, $errorCases);
    }
}

