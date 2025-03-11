<?php

namespace LightnCandy\Test;

use LightnCandy\LightnCandy;
use LightnCandy\Options;
use PHPUnit\Framework\TestCase;

class ErrorTest extends TestCase
{
    public function testException()
    {
        try {
          $php = LightnCandy::precompile('{{{foo}}');
        } catch (\Exception $E) {
            $this->assertEquals('Bad token {{{foo}} ! Do you mean {{foo}} or {{{foo}}}?', $E->getMessage());
        }
    }

    public function testLog()
    {
        $template = LightnCandy::compile('{{log foo}}');

        date_default_timezone_set('GMT');
        $tmpDir = sys_get_temp_dir();
        $tmpFile = tempnam($tmpDir, 'terr_');
        ini_set('error_log', $tmpFile);

        $template(array('foo' => 'OK!'));

        $contents = array_map(function ($l) {
            $l = rtrim($l);
            preg_match('/GMT] (.+)/', $l, $m);
            return $m[1] ?? $l;
        }, file($tmpFile));

        $this->assertEquals(['array (', "  0 => 'OK!',", ')'], $contents);
        ini_restore('error_log');
    }

    #[\PHPUnit\Framework\Attributes\DataProvider("renderErrorProvider")]
    public function testRenderingException($test)
    {
        $php = LightnCandy::precompile($test['template'], $test['options'] ?? new Options());
        $renderer = LightnCandy::template($php);
        try {
            $renderer($test['data'] ?? null);
            $this->fail("Expected to throw exception: {$test['expected']}. CODE: $php");
        } catch (\Exception $E) {
            $this->assertEquals($test['expected'], $E->getMessage());
        }
    }

    public static function renderErrorProvider(): array
    {
        $errorCases = [
            [
                'template' => "{{#> testPartial}}\n  {{#> innerPartial}}\n   {{> @partial-block}}\n  {{/innerPartial}}\n{{/testPartial}}",
                'options' => new Options(
                    partials: [
                        'testPartial' => 'testPartial => {{> @partial-block}} <=',
                        'innerPartial' => 'innerPartial -> {{> @partial-block}} <-',
                    ],
                ),
                'expected' => "Runtime: the partial @partial-block could not be found",
            ],
            [
                'template' => '{{> @partial-block}}',
                'expected' => "Runtime: the partial @partial-block could not be found",
            ],
            [
                'template' => '{{foo}}',
                'options' => new Options(strict: true),
                'expected' => 'Runtime: [foo] does not exist',
            ],
            [
                'template' => '{{#foo}}OK{{/foo}}',
                'options' => new Options(strict: true),
                'expected' => 'Runtime: [foo] does not exist',
            ],
            [
                'template' => '{{{foo}}}',
                'options' => new Options(strict: true),
                'expected' => 'Runtime: [foo] does not exist',
            ],
            [
                'template' => '{{foo}}',
                'options' => new Options(
                    helpers: [
                        'foo' => function () {
                            throw new \Exception('Expect the unexpected');
                        },
                    ],
                ),
                'expected' => 'Runtime: call custom helper \'foo\' error: Expect the unexpected',
            ],
        ];

        return array_map(fn($i) => [$i], $errorCases);
    }

    #[\PHPUnit\Framework\Attributes\DataProvider("errorProvider")]
    public function testErrors($test)
    {
        if (!isset($test['expected'])) {
            // should compile without error
            LightnCandy::precompile($test['template'], $test['options']);
            $this->assertTrue(true);
            return;
        }

        try {
            LightnCandy::precompile($test['template'], $test['options']);
            $this->fail("Expected to throw exception: {$test['expected']}");
        } catch (\Exception $e) {
            $this->assertEquals($test['expected'], explode("\n", $e->getMessage()));
        }
    }

    public static function errorProvider(): array
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
                    "Unexpected character in 'wi[n]ner.test3' (should it be 'wi.[n].ner.test3' ?)",
                ),
            ),
            array(
                'template' => '{{winner].[test4]}}',
                'expected' => array(
                    'Wrong variable naming as \'winner].[test4]\' in {{winner].[test4]}} !',
                    "Unexpected character in 'winner].[test4]' (should it be 'winner.[test4]' ?)",
                ),
            ),
            array(
                'template' => '{{winner[.test5]}}',
                'expected' => array(
                    'Wrong variable naming as \'winner[.test5]\' in {{winner[.test5]}} !',
                    "Unexpected character in 'winner[.test5]' (should it be 'winner.[.test5]' ?)",
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
                    "Unexpected character in 'test9]' (should it be 'test9' ?)",
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
                    "Unexpected character in ']testC' (should it be 'testC' ?)",
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
                    "Unexpected character in 'te.t[est].endL' (should it be 'te.t.[est].endL' ?)",
                ),
            ),
            array(
                'template' => '{{te.t[est]o.endM}}',
                'expected' => array(
                    'Wrong variable naming as \'te.t[est]o.endM\' in {{te.t[est]o.endM}} !',
                    "Unexpected character in 'te.t[est]o.endM' (should it be 'te.t.[est].o.endM' ?)"
                ),
            ),
            array(
                'template' => '{{te.[est]o.endN}}',
                'expected' => array(
                    'Wrong variable naming as \'te.[est]o.endN\' in {{te.[est]o.endN}} !',
                    "Unexpected character in 'te.[est]o.endN' (should it be 'te.[est].o.endN' ?)",
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
                'options' => new Options(
                    helpers: [
                        'helper' => array('bad input'),
                    ]
                ),
                'expected' => 'I found an array in helpers with key as helper, please fix it.',
            ),
            array(
                'template' => '{{typeof hello}}',
            ),
            array(
                'template' => '{{typeof hello}}',
                'options' => new Options(knownHelpersOnly: true),
                'expected' => 'Missing helper: "typeof"',
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
                'expected' => "The partial not_found could not be found",
            ),
            array(
                'template' => '{{abc}}',
                'options' => new Options(
                    helpers: ['abc'],
                ),
                'expected' => "You provide a custom helper named as 'abc' in options['helpers'], but the function abc() is not defined!",
            ),
            array(
                'template' => '{{test_join (foo bar)}}',
                'options' => new Options(
                    helpers: [
                        'test_join' => function ($input) {
                            return join('.', $input);
                        },
                    ],
                ),
                'expected' => 'Missing helper: "foo"',
            ),
            array(
                'template' => '{{1 + 2}}',
                'expected' => "Wrong variable naming as '+' in {{1 + 2}} ! You should wrap ! \" # % & ' * + , ; < = > { | } ~ into [ ]",
            ),
            array(
                'template' => '{{> (foo) bar}}',
                'expected' => array(
                    'Missing helper: "foo"',
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
                'expected' => 'Unclosed token {{{{foo}}}} !!',
            ),
            array(
                'template' => '{{foo (foo (foo 1 2) 3))}}',
                'options' => new Options(
                    helpers: [
                        'foo' => function () {
                            return;
                        },
                    ],
                ),
                'expected' => 'Unexpected \')\' in expression \'foo (foo (foo 1 2) 3))\' !!',
            ),
            array(
                'template' => '{{{{foo}}}} {{ {{{{#foo}}}}',
                'expected' => 'Unclosed token {{{{foo}}}} !!',
            ),
            array(
                'template' => '{{else}}',
                'expected' => '{{else}} only valid in if, unless, each, and #section context',
            ),
            array(
                'template' => '{{log}}',
                'expected' => 'No argument after {{log}} !',
            ),
            array(
                'template' => '{{#*help me}}{{/help}}',
                'expected' => 'Do not support {{#*help me}}, now we only support {{#*inline "partialName"}}template...{{/inline}}',
            ),
            array(
                'template' => '{{#*inline}}{{/inline}}',
                'expected' => 'Error in {{#*inline}}: inline require 1 argument for partial name!',
            ),
            array(
                'template' => '{{#>foo}}bar',
                'expected' => 'Unclosed token {{#>foo}} !!',
            ),
            array(
                'template' => '{{ #2 }}',
                'expected' => 'Unclosed token {{#2}} !!',
            ),
        );

        return array_map(function ($i) {
            if (!isset($i['options'])) {
                $i['options'] = new Options();
            }
            if (isset($i['expected']) && is_string($i['expected'])) {
                $i['expected'] = [$i['expected']];
            }
            return [$i];
        }, $errorCases);
    }
}
