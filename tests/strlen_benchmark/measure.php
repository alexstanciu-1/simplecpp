<?php

declare(strict_types=1);
require_once __DIR__ . '/../../compiler/boot.php';

use scpp\compiler\{flavor, source_file, Source_Frontend, entry_target, Cpp_Emitter};

$source = new source_file('measure.cpp', 'int measure(string &s) { return strlen(s); } return 0;', flavor::cpp);
$entry = (new Source_Frontend())->prepare($source, new entry_target(31));
echo (new Cpp_Emitter())->source_definitions($entry->file);
$name = Cpp_Emitter::function_name($entry->file->functions()[0]);
echo "inline scpp::int64 measure_source(scpp::string& value) { return $name(value); }\n";
