<?php
declare(strict_types=1);
namespace Scpp\Api;

require_once __DIR__ . '/../../tools/api_generator/php/input.php';
require_once __DIR__ . '/../../tools/api_generator/php/publication.php';
require_once __DIR__ . '/../../tools/api_generator/php/target.php';
require_once __DIR__ . '/../../tools/api_generator/php/scalars.php';
require_once __DIR__ . '/../../tools/api_generator/php/families.php';
require_once __DIR__ . '/../../tools/api_generator/php/render.php';

// Native consumers request their own fixture shapes using the production concrete recipe.
[, $metadata, $publication, $facts, $context, $directory] = $argv;
$target = targetFacts(decode(readFile($facts), 'facts'), decode(readFile($context), 'context'));
$plan = prepareRuntime(decode(readFile($metadata), 'metadata'), decode(readFile($publication), 'publication'), $target);
foreach (['int64', 'void', 'string', 'result_int64'] as $name) {
    $value = $name !== 'void';
    $type = $value ? new RuntimeConcreteType($plan, $name) : new VoidConcreteType();
    file_put_contents("$directory/$name.hpp", asyncTaskHeader($type, $name . '_task', $value, 'scpp::async'));
}
