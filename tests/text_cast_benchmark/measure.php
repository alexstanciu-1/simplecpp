<?php

declare(strict_types=1);
require_once __DIR__ . '/../../compiler/boot.php';

use scpp\compiler\{flavor, source_file, Source_Frontend, entry_target, Cpp_Emitter};

$text = '';
$family = $argv[1];
foreach (['bool', 'byte', 'int', 'float'] as $type) {
	$body = match ($family) {
		'scpp' => 'return (string)v;',
		'mixed', 'dynamic' => "$family box = v; return (string)box;",
	};
	$text .= "string render_$type($type &v) { $body } ";
}
$source = new source_file('measure.cpp', $text . 'return 0;', flavor::cpp);
$entry = (new Source_Frontend())->prepare($source, new entry_target(31));
echo (new Cpp_Emitter())->source_definitions($entry->file);
foreach ($entry->file->functions() as $function) {
	echo '#define ' . $function->short_name . ' ' . Cpp_Emitter::function_name($function) . "\n";
}
