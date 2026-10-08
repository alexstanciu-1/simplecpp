<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';
require_once __DIR__ . '/native_record_export.php';

function upcast_source(string $text): string
{
	Compiler_Lifecycle::reset();
	$module = new module('/upcasts', '/upcasts', '/upcasts');
	$file = new file(); $file->path = 'main.phs'; $file->content = $text;
	Source_Registry::add($module, $file); Model::$modules->add($module->name, $module);
	(new Compiler())->exec_cpp();
	return native_record_export();
}
$results = [];
function upcast_export(string $name, string $cpp, int $expected): void
{
	global $argv, $results;
	if (!isset($argv[1])) { return; }
	$path = $argv[1] . '/' . $name . '.cpp'; file_put_contents($path, $cpp);
	$results[] = ['path' => $path, 'exit_code' => $expected];
}
$classes = 'class B { int $n = 1; } class M extends B {} class C extends M {} ';
foreach ([
	'local_identity' => [$classes . '$c = new C(); $b B = $c; $b->n = 9; return $c->n;', 9],
	'assignment' => [$classes . '$c = new C(); $b B; $b = $c; $b->n = 9; return $c->n;', 9],
	'argument' => [$classes . 'function set_value(B $b): void { $b->n = 9; } $c = new C(); set_value($c); return $c->n;', 9],
	'return' => [$classes . 'function produce(): B { return new C(); } $b = produce(); return $b->n;', 1],
	'empty' => [$classes . '$c C; $b B = $c; return isset($b) ? 1 : 9;', 9],
	'property' => [$classes . 'class Holder { B $b; } $h = new Holder(); $c = new C(); $h->b = $c; $h->b->n = 9; return $c->n;', 9],
	'method' => [$classes . 'class Ops { function pass(C $c): B { return $c; } static function read(B $b): int { return $b->n; } } $o = new Ops(); $c = new C(); $b = $o->pass($c); $b->n = 9; return Ops::read($c);', 9],
	'shared_struct' => ['struct B { int $n = 1; } struct C extends B {} $c shared<C> = new C(); $b shared<B> = $c; $b->n = 9; return $c->n;', 9],
	'data_input' => [$classes . 'data class Holder { B $b; } $c = new C(); $h Holder = {b: $c}; $h->b->n = 9; return $c->n;', 9],
	'element_inputs' => [$classes . '$c = new C(); $bs vector<B> = [$c]; $bs[] = $c; $bs[0] = $c; $b = $bs[1]; $b->n = 9; return $c->n;', 9],
	'iteration' => [$classes . '$c = new C(); $cs vector<C> = [$c]; $b B; foreach ($cs as $b) { $b->n = 9; } return $c->n;', 9],
	'virtual' => ['abstract class B { abstract function read(): int; } class C extends B { function read(): int { return 9; } } $c = new C(); $b B = $c; return $b->read();', 9],
	'cleanup' => ['class Log { static int $n = 0; } class B { function __destruct() { Log::$n += 1; } } class C extends B { function __destruct() { Log::$n += 8; } } $c = new C(); $b B = $c; $c = null; if (Log::$n !== 0) { return 101; } $b = null; return Log::$n;', 9],
] as $name => [$source, $expected]) { upcast_export($name, upcast_source($source), $expected); }
// A pure conversion caller must not read settled ancestry without registering dependencies.
$registry = Model::$type_catalog->registry();
$source_type = $registry->canonical(Source_Types::definition(Model::$global_scope->source_types_named('C')[0]));
$target_type = $registry->canonical(Source_Types::definition(Model::$global_scope->source_types_named('B')[0]));
try {
	Conversion_Preparation::decide($registry->use($source_type->type_id()), $registry->use($target_type->type_id()), conversion_context::argument);
	throw new \LogicException('Upcast bypassed dependency-aware query');
}
catch (\RuntimeException $expected) {}
foreach ([
	$classes . '$b = new B(); $c C = $b;',
	$classes . 'class Other {} $c = new C(); $o Other = $c;',
	$classes . 'function replace(B &$b): void {} $c = new C(); replace($c);',
	$classes . 'function inspect(const B &$b): void {} $c = new C(); inspect($c);',
	$classes . '$c = new C(); $b B =& $c;',
	'struct B {} struct C extends B {} $c C; $b B = $c;',
	'struct B {} struct C extends B {} $c C; $b shared<B> = $c;',
	'struct B {} struct C extends B {} $c unique<C>; $b unique<B> = move($c);',
	'struct B {} struct C extends B {} $c weak<C>; $b weak<B> = $c;',
	$classes . '$cs vector<C> = []; $bs vector<B> = $cs;',
] as $source) {
	try { upcast_source($source); throw new \LogicException('Expected rejected non-shared-upcast boundary'); }
	catch (\RuntimeException $expected) {}
}

// Only the relationship changes; unchanged consumer bodies must lose their accepted conversion.
$directory = sys_get_temp_dir() . '/shared-upcasts-' . bin2hex(random_bytes(6));
mkdir($directory);
$base_path = $directory . '/base.phs';
$child_path = $directory . '/child.phs';
$main_path = $directory . '/main.phs';
try {
	file_put_contents($base_path, 'class B {}');
	$child_text = 'class C extends B {}';
	file_put_contents($child_path, $child_text);
	foreach ([
		'assignment' => '$c C; $b B = $c; return isset($b) ? 1 : 9;',
		'argument' => 'function accept(B $b): int { return isset($b) ? 1 : 9; } $c C; return accept($c);',
		'return' => 'function convert(C $c): B { return $c; } $c C; $b = convert($c); return isset($b) ? 1 : 9;',
	] as $boundary => $main) {
		file_put_contents($main_path, $main);
		Compiler_Lifecycle::reset();
		$compiler = new Compiler(); $compiler->init([$directory]); $compiler->select_entry('main', 'main.phs'); $compiler->exec_cpp();
		$original = CPP_Output::encode(Model::$cpp_files);
		file_put_contents($child_path, 'class C {}');
		foreach ([[$child_path], []] as $changes) {
			try { $compiler->update_cpp($changes); throw new \LogicException('Stale upcast survived relationship edit/retry: ' . $boundary); }
			catch (\RuntimeException $expected) {}
		}
		file_put_contents($child_path, $child_text); $compiler->update_cpp([$child_path]);
		if (CPP_Output::encode(Model::$cpp_files) !== $original) { throw new \LogicException('Upcast repair changed output'); }
		upcast_export('cross_file_' . $boundary, native_record_export(), 9);
		Compiler_Lifecycle::reset();
		$compiler->init([$directory]); $compiler->select_entry('main', 'main.phs'); $compiler->exec_cpp();
		if (CPP_Output::encode(Model::$cpp_files) !== $original) { throw new \LogicException('Fresh upcast output differs'); }
	}
}
finally {
	Compiler_Lifecycle::reset();
	foreach ([$base_path, $child_path, $main_path] as $path) { if (is_file($path)) { unlink($path); } }
	rmdir($directory);
}
if (isset($argv[1])) { file_put_contents($argv[1] . '/executions.json', json_encode($results, JSON_PRETTY_PRINT)); }
echo "Shared upcasts, invariant references and ancestry invalidation passed\n";
