<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';
require_once __DIR__ . '/native_record_export.php';

function shared_join_source(string $text): string
{
	Compiler_Lifecycle::reset();
	$module = new module('/shared-joins', '/shared-joins', '/shared-joins');
	$file = new file(); $file->path = 'main.phs'; $file->content = $text;
	Source_Registry::add($module, $file); Model::$modules->add($module->name, $module);
	(new Compiler(new cpp_lowering_options(false)))->exec_cpp();
	return native_record_export();
}
$results = [];
function shared_join_export(string $name, string $cpp): void
{
	global $argv, $results;
	if (!isset($argv[1])) { return; }
	$path = $argv[1] . '/' . $name . '.cpp'; file_put_contents($path, $cpp);
	$results[] = ['path' => $path, 'exit_code' => 9];
}
foreach (['class', 'struct'] as $kind) {
	$base_type = $kind === 'class' ? 'B' : 'shared<B>';
	$child_type = $kind === 'class' ? 'C' : 'shared<C>';
	$prefix = $kind . ' B { int $n = 1; } ' . $kind . ' C extends B {} ';
	foreach (['true ? $c : $b', 'false ? $b : $c', '$c ?: $b', '$b ?: $c', '$c ?? $b', '$b ?? $c', 'true ? $c : $c'] as $index => $expression) {
		// Invariant reference parameters prove the inferred result, not just C++ convertibility.
		$source = $prefix . 'function check(' . ($index === 6 ? 'CHILD' : 'BASE') . ' &$v): void {} '
			. '$c CHILD = new C(); $b BASE; $out = EXPR; check($out); $out->n = 9; return $c->n;';
		shared_join_export($kind . '_selection_' . $index, shared_join_source(str_replace(['CHILD', 'BASE', 'EXPR'], [$child_type, $base_type, $expression], $source)));
	}
	foreach (['?:', '??'] as $index => $operator) {
		$source = $prefix . 'function left(int &$calls, bool $present): CHILD { $calls++; $v CHILD; if ($present) { $created CHILD = new C(); $v = $created; } return $v; } '
			. 'function right(int &$calls): BASE { $calls += 10; $v BASE = new B(); return $v; } '
			. '$calls = 0; $a = left($calls, true) OP right($calls); if ($calls !== 1) { return 101; } '
			. '$b = left($calls, false) OP right($calls); if ($calls !== 12) { return 102; } $b->n = 9; return $b->n;';
		shared_join_export($kind . '_lazy_' . $index, shared_join_source(str_replace(['CHILD', 'BASE', 'OP'], [$child_type, $base_type, $operator], $source)));
	}
	$interface_prefix = 'interface I { function read(): int; } ' . $kind . ' C implements I { function read(): int { return 9; } } ';
	foreach (['true ? $c : $i', '$i ?: $c', '$i ?? $c'] as $index => $expression) {
		shared_join_export($kind . '_interface_' . $index, shared_join_source($interface_prefix . '$c ' . $child_type . ' = new C(); $i I; $out = ' . $expression . '; return $out->read();'));
	}
	shared_join_export($kind . '_empty', shared_join_source($prefix . '$c ' . $child_type . '; $b ' . $base_type . '; $out = $c ?? $b; return isset($out) ? 101 : 9;'));
}
foreach (['true ? $c : $d', '$c ?: $d', '$c ?? $d'] as $expression) {
	try {
		shared_join_source('class B {} class C extends B {} class D extends B {} $c C; $d D; $out B = ' . $expression . ';');
		throw new \LogicException('Destination or sibling ancestry incorrectly supplied join type');
	}
	catch (\RuntimeException $error) {
		if (!str_contains($error->getMessage(), 'sibling inference')) { throw $error; }
	}
}
foreach ([
	'class C {} $a unique<C>; $b unique<C>; $out = true ? $a : $b;',
	'class C {} $a weak<C>; $b weak<C>; $out = true ? $a : $b;',
	'class C {} $a C; $out = $a ?? null;',
	'struct B {} struct C extends B {} $a B; $b C; $out = true ? $a : $b;',
] as $source) {
	try { shared_join_source($source); throw new \LogicException('Unsupported shared join accepted'); }
	catch (\RuntimeException $expected) {}
}
$directory = sys_get_temp_dir() . '/shared-joins-' . bin2hex(random_bytes(6)); mkdir($directory);
$base_path = $directory . '/base.phs'; $child_path = $directory . '/child.phs'; $main_path = $directory . '/main.phs';
try {
	file_put_contents($base_path, 'class B { int $n = 9; }');
	foreach (['true ? $c : $b', '$c ?: $b', '$c ?? $b'] as $index => $expression) {
		file_put_contents($child_path, 'class C extends B {}');
		file_put_contents($main_path, '$c = new C(); $b B; $out = ' . $expression . '; return $out->n;');
		Compiler_Lifecycle::reset(); $compiler = new Compiler(new cpp_lowering_options(false));
		$compiler->init([$directory]); $compiler->select_entry('main', 'main.phs'); $compiler->exec_cpp();
		$original = CPP_Output::encode(Model::$cpp_files);
		file_put_contents($child_path, 'class C { int $n = 9; }');
		foreach ([[$child_path], []] as $changes) {
			try { $compiler->update_cpp($changes); throw new \LogicException('Join survived ancestry removal'); }
			catch (\RuntimeException $error) {
				if (!str_contains($error->getMessage(), 'sibling inference')) { throw $error; }
			}
		}
		file_put_contents($child_path, 'class C extends B {}'); $compiler->update_cpp([$child_path]);
		if (CPP_Output::encode(Model::$cpp_files) !== $original) { throw new \LogicException('Join repair changed output'); }
		shared_join_export('cross_file_' . $index, native_record_export());
		Compiler_Lifecycle::reset_preparation(); $compiler->prepare(); $compiler->cpp();
		if (CPP_Output::encode(Model::$cpp_files) !== $original) { throw new \LogicException('Join reset changed output'); }
		Compiler_Lifecycle::reset(); $compiler->init([$directory]); $compiler->select_entry('main', 'main.phs'); $compiler->exec_cpp();
		if (CPP_Output::encode(Model::$cpp_files) !== $original) { throw new \LogicException('Fresh join changed output'); }
	}
}
finally { Compiler_Lifecycle::reset(); unlink($base_path); unlink($child_path); unlink($main_path); rmdir($directory); }
if (isset($argv[1])) { file_put_contents($argv[1] . '/executions.json', json_encode($results, JSON_PRETTY_PRINT)); }
echo "Shared ancestry selection joins passed\n";
