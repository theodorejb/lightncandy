<?php

use LightnCandy\LightnCandy;

require 'vendor/autoload.php';

$template = '{{> (partial_name_helper type)}}';

$data = [
    'type' => 'dog',
    'name' => 'Lucky',
    'age' => 5,
];

$php = LightnCandy::precompile($template, array(
    'helpers' => array(
        'partial_name_helper' => function (string $type) {
            return match ($type) {
                'man', 'woman' => 'people',
                'dog', 'cat' => 'animal',
                default => 'default',
            };
        },
    ),
    'partials' => array(
        'people' => 'This is {{name}}, he is {{age}} years old.',
        'animal' => 'This is {{name}}, it is {{age}} years old.',
        'default' => 'This is {{name}}.',
    ),
));

$renderer = LightnCandy::template($php);

echo "Data:\n";
var_export($data);

echo "\nTemplate:\n$template\n";
echo "\nCode:\n$php\n\n";
echo "\nOutput:\n";
echo $renderer($data);
