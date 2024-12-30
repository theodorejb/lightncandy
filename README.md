LightnCandy
===========

⚡🍭 An extremely fast PHP implementation of handlebars ( http://handlebarsjs.com/ ) and mustache ( http://mustache.github.io/ ).

Features
--------

* Compile template to **pure PHP** code. Examples:
   * <a href="https://github.com/zordius/HandlebarsTest/blob/master/fixture/001-simple-vars.tmpl">Template A</a> generated <a href="https://github.com/zordius/HandlebarsTest/blob/master/fixture/001-simple-vars.php">PHP A</a>
   * <a href="https://github.com/zordius/HandlebarsTest/blob/master/fixture/016-hb-eachthis.tmpl">Template B</a> generated <a href="https://github.com/zordius/HandlebarsTest/blob/master/fixture/016-hb-eachthis.php">PHP B</a>
* **FAST!**
   * Runs 2~7 times faster than <a href="https://github.com/bobthecow/mustache.php">mustache.php</a> (Justin Hileman/bobthecow implementation).
   * Runs 2~7 times faster than <a href="https://github.com/dingram/mustache-php">mustache-php</a> (Dave Ingram implementation).
   * Runs 10~50 times faster than <a href="https://github.com/XaminProject/handlebars.php">handlebars.php</a>.
   * Detail performance test reports can be found <a href="https://github.com/zordius/HandlebarsTest">here</a>, go http://zordius.github.io/HandlebarsTest/ to see charts.
* **SMALL!** all PHP files in 189K
* **ROBUST!**
   * Supports almost all <a href="https://github.com/jbboehr/handlebars-spec">handlebars.js spec</a>
   * Output <a href="https://github.com/zordius/HandlebarsTest/blob/master/FEATURES.md">SAME</a> with <a href="https://github.com/wycats/handlebars.js">handlebars.js</a>
* Context generation
   * Analyze used features from your template (execute `LightnCandy::getContext()` to get it) .
* Debug
   * <a href="#template-debugging">Generate debug version template</a>
      * Find out missing data when rendering template.
      * Generate visually debug template.
* Standalone Template
   * The compiled PHP code can run without any PHP library. You do not need to include LightnCandy when execute rendering function.

Installation
------------

Use Composer ( https://getcomposer.org/ ) to install LightnCandy:

```
composer require zordius/lightncandy:dev-master
```

**UPGRADE NOTICE**

* Please check <a href="HISTORY.md">HISTORY.md</a> for versions history.
* Please check <a href="UPGRADE.md">UPGRADE.md</a> for upgrade notice.

Documents
---------

* <a href="https://zordius.github.io/HandlebarsCookbook/9000-quickstart.html">Quick Start</a>

Compile Options
---------------

You can apply more options by running `LightnCandy::compile($template, $options)`:

```php
LightnCandy::compile($template, array(
    'flags' => LightnCandy::FLAG_ERROR_LOG
));
```

**Error Handling**
* <a href="https://zordius.github.io/HandlebarsCookbook/LC-FLAG_ERROR_LOG.html">FLAG_ERROR_LOG</a>
* <a href="https://zordius.github.io/HandlebarsCookbook/LC-FLAG_ERROR_EXCEPTION.html">FLAG_ERROR_EXCEPTION</a>
* <a href="https://zordius.github.io/HandlebarsCookbook/LC-FLAG_RENDER_DEBUG.html">FLAG_RENDER_DEBUG</a>

**Handlebars Options**
* <a href="https://zordius.github.io/HandlebarsCookbook/LC-FLAG_NOESCAPE.html">FLAG_NOESCAPE</a>
* <a href="https://zordius.github.io/HandlebarsCookbook/LC-FLAG_PARTIALNEWCONTEXT.html">FLAG_PARTIALNEWCONTEXT</a>
* `FLAG_IGNORESTANDALONE` : prevent standalone detection on `{{#foo}}`, `{{/foo}}` or `{{^}}`, the behavior is same with handlebars.js ignoreStandalone compile time option.
* `FLAG_PREVENTINDENT` : align partial indent behavior with mustache specification. This is same with handlebars.js preventIndent copmile time option.

**PHP**
* <a href="https://zordius.github.io/HandlebarsCookbook/LC-FLAG_RUNTIMEPARTIAL.html">FLAG_RUNTIMEPARTIAL</a>
* <a href="https://zordius.github.io/HandlebarsCookbook/LC-FLAG_PROPERTY.html">FLAG_PROPERTY</a>

Partial Support
---------------

* <a href="https://zordius.github.io/HandlebarsCookbook/0011-partial.html">Example of compile time partial</a>
* <a href="https://zordius.github.io/HandlebarsCookbook/0024-partialcontext.html">Example of partial context changing</a>
* <a href="https://zordius.github.io/HandlebarsCookbook/0028-dynamicpartial.html">use dynamic partial</a>

Custom Helper
-------------

* <a href="https://zordius.github.io/HandlebarsCookbook/9001-customhelper.html">Custom Helpers in LighnCandy</a>
* <a href="https://zordius.github.io/HandlebarsCookbook/9002-helperoptions.html">The $options Object</a>
* <a href="https://zordius.github.io/HandlebarsCookbook/9003-helperescaping.html">Use SafeString</a>

Custom Helper Examples
----------------------

**#mywith (context change)**
* LightnCandy
```php
// LightnCandy sample, #mywith works same with #with
$php = LightnCandy::compile($template, array(
    'helpers' => array(
        'mywith' => function ($context, $options) {
            return $options['fn']($context);
        }
    )
));
```

* Handlebars.js
```javascript
// Handlebars.js sample, #mywith works same with #with
Handlebars.registerHelper('mywith', function(context, options) {
    return options.fn(context);
});
```

**#myeach (context change)**
* LightnCandy
```php
// LightnCandy sample, #myeach works same with #each
$php = LightnCandy::compile($template, array(
    'helpers' => array(
        'myeach' => function ($context, $options) {
            $ret = '';
            foreach ($context as $cx) {
                $ret .= $options['fn']($cx);
            }
            return $ret;
        }
    )
));
```

* Handlebars.js
```javascript
// Handlebars.js sample, #myeach works same with #each
Handlebars.registerHelper('myeach', function(context, options) {
    var ret = '', i, j = context.length;
    for (i = 0; i < j; i++) {
        ret = ret + options.fn(context[i]);
    }
    return ret;
});
```

**#myif (no context change)**
* LightnCandy
```php
// LightnCandy sample, #myif works same with #if
$php = LightnCandy::compile($template, array(
    'helpers' => array(
        'myif' => function ($conditional, $options) {
            if ($conditional) {
                return $options['fn']();
            } else {
                return $options['inverse']();
            }
        }
    )
));
```

* Handlebars.js
```javascript
// Handlebars.js sample, #myif works same with #if
Handlebars.registerHelper('myif', function(conditional, options) {
    if (conditional) {
        return options.fn(this);
    } else {
        return options.inverse(this);
    }
});
```

You can use `isset($options['fn'])` to detect your custom helper is a block or not; you can also use `isset($options['inverse'])` to detect the existence of `{{else}}`.

**Data variables and context**

You can get special data variables from `$options['data']`. Using `$options['_this']` to receive current context.

```php
$php = LightnCandy::compile($template, array(
    'helpers' => array(
        'getRoot' => function ($options) {
            print_r($options['_this']); // dump current context
            return $options['data']['root']; // same as {{@root}}
        }
    )
));
```

* Handlebars.js
```javascript
Handlebars.registerHelper('getRoot', function(options) {
    console.log(this); // dump current context
    return options.data.root; // same as {{@root}}
});
```

**Private variables**

You can inject private variables into inner block when you execute child block with second parameter. The example code showed similar behavior with `{{#each}}` which sets index for child block and can be accessed with `{{@index}}`.

* LightnCandy
```php
$php = LightnCandy::compile($template, array(
    'helpers' => array(
        'list' => function ($context, $options) {
            $out = '';
            $data = $options['data'];

            foreach ($context as $idx => $cx) {
                $data['index'] = $idx;
                $out .= $options['fn']($cx, array('data' => $data));
            }

            return $out;
        }
    )
));
```

* Handlebars.js
```javascript
Handlebars.registerHelper('list', function(context, options) {
  var out = '';
  var data = options.data ? Handlebars.createFrame(options.data) : undefined;

  for (var i=0; i<context.length; i++) {
    if (data) {
      data.index = i;
    }
    out += options.fn(context[i], {data: data});
  }
  return out;
});
```

Template Debugging
------------------

When template error happened, LightnCandy::compile() will return false. You may compile with `FLAG_ERROR_LOG` to see more error message, or compile with `FLAG_ERROR_EXCEPTION` to catch the exception.

You may generate debug version of templates with `FLAG_RENDER_DEBUG` when compile() . The debug template contained more debug information and slower (TBD: performance result) , you may pass extra LightnCandy\Runtime options into render function to know more rendering error (missing data). For example:

```php
$template = "Hello! {{name}} is {{gender}}.
Test1: {{@root.name}}
Test2: {{@root.gender}}
Test3: {{../test3}}
Test4: {{../../test4}}
Test5: {{../../.}}
Test6: {{../../[test'6]}}
{{#each .}}
each Value: {{.}}
{{/each}}
{{#.}}
section Value: {{.}}
{{/.}}
{{#if .}}IF OK!{{/if}}
{{#unless .}}Unless not OK!{{/unless}}
";

// compile to debug version
$phpStr = LightnCandy::compile($template, array(
    'flags' => LightnCandy::FLAG_RENDER_DEBUG
));

// Save the compiled PHP code into a php file
file_put_contents('render.php', '<?php ' . $phpStr . '?>');

// Get the render function from the php file
$renderer = include('render.php');

// error_log() when missing data:
//   LightnCandy\Runtime: [gender] is not exist
//   LightnCandy\Runtime: ../[test] is not exist
$renderer(array('name' => 'John'), array('debug' => LightnCandy\Runtime::DEBUG_ERROR_LOG));

// Output visual debug template with ANSI color:
echo $renderer(array('name' => 'John'), array('debug' => LightnCandy\Runtime::DEBUG_TAGS_ANSI));

// Output debug template with HTML comments:
echo $renderer(array('name' => 'John'), array('debug' => LightnCandy\Runtime::DEBUG_TAGS_HTML));
```

Here are the list of LightnCandy\Runtime debug options for render function:

* `DEBUG_ERROR_LOG` : error_log() when missing required data
* `DEBUG_ERROR_EXCEPTION` : throw exception when missing required data
* `DEBUG_TAGS` : turn the return value of render function into normalized mustache tags
* `DEBUG_TAGS_ANSI` : turn the return value of render function into normalized mustache tags with ANSI color
* `DEBUG_TAGS_HTML` : turn the return value of render function into normalized mustache tags with HTML comments

Preprocess Partials
-------------------

If you want to do extra process before the partial be compiled, you may use `prepartial` when `compile()`. For example, this sample adds HTML comments to identify the partial by the name:

```php
$php = LightnCandy::compile($template, array(
    'prepartial' => function ($context, $template, $name) {
        return "<!-- partial start: $name -->$template<!-- partial end: $name -->";
    }
));
```

You may also extend <a href="https://zordius.github.io/lightncandy/class-LightnCandy.Partial.html">LightnCandy\Partial</a> by override the <a href="https://zordius.github.io/lightncandy/class-LightnCandy.Partial.html#_prePartial">prePartial()</a> static method to turn your preprocess into a built-in feature.

Customize Render Function
-------------------------

If you want to do extra tasks inside render function or add more comment, you may use `renderex` when `compile()` . For example, this sample embed the compile time comment into the template:

```php
$php = LightnCandy::compile($template, array(
    'renderex' => '// Compiled at ' . date('Y-m-d h:i:s')
));
```

Your render function will be:

```php
function ($in) {
    $cx = array(...);
    // compiled at 1999-12-31 00:00:00
    return .....
}
```

Please make sure the passed in `renderex` is valid PHP, LightnCandy will not check it.

Unsupported Feature
-------------------

* `{{foo/bar}}` style variable name, it is deprecated in official handlebars.js document, please use this style: `{{foo.bar}}`.

Detail Feature list
-------------------

Go http://handlebarsjs.com/ to see more feature description about handlebars.js. All features align with it.

* Exact same CR/LF behavior with handlebars.js
* Exact same CR/LF bahavior with mustache spec
* Exact same 'true' or 'false' output with handlebars.js
* Exact same '[object Object]' output or join(',' array) output with handlebars.js
* Can place heading/tailing space, tab, CR/LF inside `{{ var }}` or `{{{ var }}}`
* Indent behavior of the partial same with mustache spec
* `{{{value}}}` or `{{&value}}` : raw variable
   * true as 'true'
   * false as 'false' (require `FLAG_TRUE`)
* `{{value}}` : HTML escaped variable
   * true as 'true'
   * false as 'false'
* `{{{path.to.value}}}` : dot notation, raw
* `{{path.to.value}}` : dot notation, HTML escaped 
* `{{.}}` : current context, HTML escaped
* `{{{.}}}` : current context, raw
* `{{this}}` : current context, HTML escaped
* `{{{this}}}` : current context, raw
* `{{#value}}` : section
   * false, undefined and null will skip the section
   * true will run the section with original scope
   * All others will run the section with new scope (includes 0, 1, -1, '', '1', '0', '-1', 'false', Array, ...)
* `{{/value}}` : end section
* `{{^value}}` : inverted section
   * false, undefined and null will run the section with original scope
   * All others will skip the section (includes 0, 1, -1, '', '1', '0', '-1', 'false', Array, ...)
* `{{! comment}}` : comment
* `{{!-- comment or {{ or }} --}}` : extended comment that can contain }} or {{ .
* `{{#each var}}` : each loop
* `{{#each}}` : each loop on {{.}}
* `{{/each}}` : end loop
* `{{#each bar as |foo|}}` : echo loop on bar and set the value as foo.
* `{{#each bar as |foo moo|}}` : echo loop on bar, set the value as foo, set the index as moo.
* `{{#if var}}` : run if logic with original scope (null, false, empty Array and '' will skip this block)
* `{{#if foo includeZero=true}}` : result as true when foo === 0
* `{{/if}}` : end if
* `{{else}}` or `{{^}}` : run else logic, should between `{{#if var}}` and `{{/if}}` ; or between `{{#unless var}}` and `{{/unless}}`; or between `{{#foo}}` and `{{/foo}}`; or between `{{#each var}}` and `{{/each}}`; or between `{{#with var}}` and `{{/with}}`.
* `{{#if foo}} ... {{else if bar}} ... {{/if}}` : chained if else blocks
* `{{#unless var}}` : run unless logic with original scope (null, false, empty Array and '' will render this block)
* `{{#unless foo}} ... {{else if bar}} ... {{/unless}}` : chained unless else blocks
* `{{#unless foo}} ... {{else unless bar}} ... {{/unless}}` : chained unless else blocks
* `{{#foo}} ... {{else bar}} ... {{/foo}}` : custom helper chained else blocks
* `{{#with var}}` : change context scope. If the var is false or an empty array, skip included section.
* `{{#with bar as |foo|}}` : change context to bar and set the value as foo.
* `{{lookup foo bar}}` : lookup foo by value of bar as key.
* `{{../var}}` : parent template scope.
* `{{>file}}` : partial; include another template inside a template.
* `{{>file foo}}` : partial with new context (require `FLAG_RUNTIMEPARTIAL`)
* `{{>file foo bar=another}}` : partial with new context which mixed with followed key value (require `FLAG_RUNTIMEPARTIAL`)
* `{{>(helper) foo}}` : include dynamic partial by name provided from a helper (require `FLAG_RUNTIMEPARTIAL`)
* `{{@index}}` : references to current index in a `{{#each}}` loop on an array.
* `{{@key}}` : references to current key in a `{{#each}}` loop on an object.
* `{{@root}}` : references to root context.
* `{{@first}}` : true when looping at first item.
* `{{@last}}` : true when looping at last item.
* `{{@root.path.to.value}}` : references to root context then follow the path.
* `{{@../index}}` : access to parent loop index.
* `{{@../key}}` : access to parent loop key.
* `{{foo.[ba.r].[#spec].0.ok}}` : references to $CurrentConext['foo']['ba.r']['#spec'][0]['ok'] .
* `{{~any_valid_tag}}` : Space control, remove all previous spacing (includes CR/LF, tab, space; stop on any none spacing character)
* `{{any_valid_tag~}}` : Space control, remove all next spacing (includes CR/LF, tab, space; stop on any none spacing character)
* `{{{helper var}}}` : Execute custom helper then render the result
* `{{helper var}}` : Execute custom helper then render the HTML escaped result
* `{{helper "str"}}` or `{{helper 'str'}}` : Execute custom helper with string arguments
* `{{helper 123 null true false undefined}}` : Pass number, true, false, null or undefined into helper
* `{{helper name1=var name2=var2}}` : Execute custom helper with named arguments
* `{{#helper ...}}...{{/helper}}` : Execute block custom helper
* `{{helper (helper2 foo) bar}}` : Execute custom helpers as subexpression
* `{{{{raw_block}}}} {{will_not_parsed}} {{{{/raw_block}}}}` : Raw block
* `{{#> foo}}block{{/foo}}` : Partial block, provide `foo` partial default content (require `FLAG_RUNTIMEPARTIAL`)
* `{{#> @partial-block}}` : access partial block content inside a partial
* `{{#*inline "partial_name"}}...{{/inline}}` : Inline partial, provide a partial and overwrite the original one.
* `{{log foo}}` : output value to stderr for debug.
