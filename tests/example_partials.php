<?php

use LightnCandy\LightnCandy;

require 'vendor/autoload.php';

$template = '{{> (partial_name_helper type)}}';

$data = array(
    'type' => 'dog',
    'name' => 'Lucky',
    'age' => 5
);

function partial_name_helper ($type) {
    switch ($type[0]) {
    case 'man':
    case 'woman':
        return 'people';
    case 'dog':
    case 'cat':
        return 'animal';
    default:
        return 'default';
    }
}

$php = LightnCandy::precompile($template, array(
    'flags' => LightnCandy::FLAG_RUNTIMEPARTIAL,
    'helpers' => array(
        'partial_name_helper'
    ),
    'partials' => array(
        'people' => 'This is {{name}}, he is {{age}} years old.',
        'animal' => 'This is {{name}}, it is {{age}} years old.',
        'default' => 'This is {{name}}.',
    )
));

$renderer = LightnCandy::template($php);

echo "Data:\n";
print_r($data);

echo "\nTemplate:\n$template\n";

echo "\nCode:\n$php\n\n";

echo "\nOutput:\n";
echo $renderer($data);
echo "\n";
