<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

// Current production catalogs must compose, not merely pass in isolated fixtures.
$names = ['process', 'source', 'regex', 'filesystem', 'datetime', 'strings', 'json', 'core'];
$input = Runtime_Catalog_Loader::load(dirname(__DIR__, 3) . '/runtime/metadata/modules', $names);
Compiler_Lifecycle::configure_runtime($input);
Compiler_Lifecycle::reset();
$before = Model::$type_catalog->definition('process_handle');
$build = CPP_Runtime_Build::requirements($input);
if ($build->sources !== ['core/runtime.cpp', 'modules/process/process.cpp', 'modules/regex/regex.cpp',
	'modules/datetime/datetime.cpp', 'modules/json/document.cpp'] || $build->libraries !== ['pcre2-8']) {
	throw new \LogicException('Production module build closure changed');
}
Compiler_Lifecycle::reset();
if (Model::$runtime_metadata !== $input || Model::$type_catalog->definition('process_handle') === $before) {
	throw new \LogicException('Production reset lost retained input/fresh type identities');
}
echo "Production runtime catalogs compose and reset\n";
