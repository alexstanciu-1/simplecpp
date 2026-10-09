<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

function catalog_check(bool $condition, string $message): void
{
	if (!$condition) { throw new \LogicException($message); }
}

/** Deliberately host-only fixture IO: loaded metadata must survive removal of its files. */
$directory = $argv[1];
$root = dirname(__DIR__, 3) . '/runtime/metadata/modules';
$core = file_get_contents($root . '/core.json');
$json = file_get_contents($root . '/json.json');
file_put_contents($directory . '/core.json', $core);
file_put_contents($directory . '/json.json', $json);
$input = Runtime_Catalog_Loader::load($directory, ['json', 'core', 'json']);
catalog_check(count($input->modules) === 2, 'Modules were not deduplicated');
unlink($directory . '/core.json');
unlink($directory . '/json.json');
Compiler_Lifecycle::configure_runtime($input);

foreach ([
	str_replace('"version": 1', '"version": 2', $core),
	str_replace('"version": 1', '"version": 1, "version": 1', $core),
	str_replace('"module": "core"', '"module": "core", "unknown": 1', $core),
	str_replace('"passing": "value"', '"passing": "optional"', $core),
	str_replace('"returns": "int"', '"returns": null', $core),
	$core . ' false',
] as $invalid) {
	$failed = false;
	try { Runtime_Catalog_Reader::decode($invalid, 'invalid.json'); }
	catch (\RuntimeException $error) { $failed = true; }
	catalog_check($failed && Model::$runtime_metadata === $input, 'Invalid schema changed configuration');
}

foreach ([
	str_replace('"returns": "int"', '"returns": "missing_type"', $core),
	str_replace('"returns": "int"', '"returns": {"name":"result","arguments":[]}', $core),
	str_replace('"type": "string"', '"type": "void"', $core),
	str_replace('scpp::str::byte_length', 'evil();', $core),
] as $invalid) {
	$candidate = new runtime_catalog_input();
	$candidate->modules->add('core', Runtime_Catalog_Reader::decode($invalid, 'invalid.json'));
	$failed = false;
	try { Compiler_Lifecycle::configure_runtime($candidate); }
	catch (\Throwable $error) { $failed = true; }
	catalog_check($failed && Model::$runtime_metadata === $input, 'Invalid contract changed configuration');
}

file_put_contents($directory . '/core.json', str_replace('"dependencies": []', '"dependencies": ["json"]', $core));
file_put_contents($directory . '/json.json', $json);
$failed = false;
try { Runtime_Catalog_Loader::load($directory, ['json']); }
catch (\RuntimeException $error) { $failed = true; }
catalog_check($failed, 'Dependency cycle accepted');
unlink($directory . '/core.json');
unlink($directory . '/json.json');

Compiler_Lifecycle::reset();
$target = Model::$runtime_callables->function_definition('core.strlen');
$metadata = Model::$runtime_metadata;
Compiler_Lifecycle::reset_syntax();
$replacement = Model::$runtime_callables->function_definition('core.strlen');
catalog_check($target !== $replacement && Model::$runtime_metadata === $metadata, 'Reset did not reuse decoded input with fresh identities');
$failed = false;
try { Model::$cpp_callable_bindings->function_binding($target); }
catch (\LogicException $error) { $failed = true; }
catalog_check($failed, 'Stale runtime target accepted');
$failed = false;
try { Compiler_Lifecycle::configure_runtime($input); }
catch (\LogicException $error) { $failed = true; }
catalog_check($failed, 'Live configuration replacement accepted');

/** Exercise the ordinary frontend, call preparation and artifact emission. */
function catalog_compile(string $source): void
{
	Compiler_Lifecycle::reset();
	$module = new module('/catalog', '/catalog', '/catalog');
	$file = new file();
	$file->path = 'main.phs';
	$file->content = $source;
	Source_Registry::add($module, $file);
	Model::$modules->add($module->name, $module);
	$compiler = new Compiler();
	$compiler->exec_cpp();
}

catalog_compile('$unicode = strlen("é中😀"); $empty = strlen(""); $ascii = strlen("abc"); '
	. 'if ($unicode !== 9) { return 1; } if ($empty !== 0) { return 2; } if ($ascii !== 3) { return 3; } return 0;');
file_put_contents($directory . '/length.cpp', CPP_Probe::text(Model::$cpp_files));
$binding = Model::$cpp_callable_bindings->function_binding(Model::$runtime_callables->function_definition('core.strlen'));
catalog_check($binding->symbol() === 'scpp::str::byte_length', 'Wrong length semantics');
foreach (['strlen();', 'strlen("a", "b");', 'strlen(1);'] as $source) {
	$failed = false;
	try { catalog_compile($source); }
	catch (\RuntimeException $error) { $failed = true; }
	catalog_check($failed && Model::$cpp_files->is_empty(), 'Invalid call published C++');
}
catalog_compile('function strlen(int $value): int { return $value; } return strlen(0);');
file_put_contents($directory . '/shadow.cpp', CPP_Probe::text(Model::$cpp_files));
echo "Runtime catalogs passed\n";
