<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';
require_once __DIR__ . '/native_record_export.php';

function derived_data_source(string $text): string
{
	Compiler_Lifecycle::reset();
	$module = new module('/derived-data', '/derived-data', '/derived-data');
	$file = new file(); $file->path = 'main.phs'; $file->content = $text;
	Source_Registry::add($module, $file); Model::$modules->add($module->name, $module);
	(new Compiler())->exec_cpp();
	return native_record_export();
}

$results = [];
foreach (['class', 'struct'] as $kind) {
	$base = $kind . ' B { int $n; function __construct(int $input = 7) { $this->n = $input + 1; } } ';
	$child = 'data ' . $kind . ' C extends B { int $extra = 1; } ';
	foreach ([
		'inherited' => [$base . $child . '$c C = {input: 8}; return $c->n;', 9],
		'default' => [$base . $child . '$c C = {}; $n = $c->n; $extra = $c->extra; return $n + $extra;', 9],
		'php_keys' => [$base . $child . '$c = C::["input" => 8]; return $c->n;', 9],
		'unique' => [$base . $child . '$c unique<C> = {input: 8}; return $c->n;', 9],
		'storage' => [$base . $child . '$c ' . ($kind === 'class' ? 'value<C>' : 'shared<C>') . ' = {input: 8}; return $c->n;', 9],
		'multilevel' => [$base . $kind . ' M extends B {} data ' . $kind . ' C extends M {} $c C = {input: 8}; return $c->n;', 9],
		'authored' => [$base . 'data ' . $kind . ' C extends B { private int $extra; function __construct(int $base_input, set:$extra = 2) { parent::__construct($base_input); $this->n += $extra; } } $c C = {base_input: 6}; return $c->n;', 9],
		'sequence' => ['function bump(int &$n): int { $n++; return $n; } ' . $kind . ' B { int $n; function __construct(int $a, int $b) { $this->n = $a * 10 + $b; } } data ' . $kind . ' C extends B {} $n = 0; $c C = {b: bump($n), a: bump($n)}; return $c->n;', 21],
		'nested' => ['data struct Item { int $n; } ' . $kind . ' B { Item $item; function __construct(set:$item) {} } data ' . $kind . ' C extends B {} $c C = {item: {n: 9}}; return $c->item->n;', 9],
		'base_opt_in' => ['data ' . $kind . ' B { int $n; function __construct(set:$n) {} } data ' . $kind . ' C extends B {} $c C = {n: 9}; return $c->n;', 9],
	] as $name => [$text, $expected]) {
		$cpp = derived_data_source($text);
		if (isset($argv[1])) {
			$path = $argv[1] . '/' . $kind . '_' . $name . '.cpp'; file_put_contents($path, $cpp);
			$results[] = ['path' => $path, 'exit_code' => $expected];
		}
	}
	foreach ([
		[$base . $kind . ' C extends B {} $c C = {input: 8};', 'data opt-in'],
		[$base . $child . '$c C = {n: 8};', 'unknown record'],
		[$base . $child . '$c C = {extra: 8};', 'unknown record'],
		[$base . $child . '$c C = {input: 8, input: 9};', 'duplicate record'],
		[$base . $child . '$c C = {input: "wrong"};', 'value boundary'],
		[str_replace(' = 7', '', $base) . $child . '$c C = {};', 'missing required'],
		[$kind . ' B { int $n; } data ' . $kind . ' C extends B {}', 'Synthesized inherited'],
		['data ' . $kind . ' B { int $n; function __construct(set:$n) {} } ' . $kind . ' C extends B {} $c C = {n: 9};', 'data opt-in'],
		['enum E { case A; } ' . $base . 'data ' . $kind . ' C extends B { E $required; }', 'default-constructible child'],
	] as [$text, $message]) {
		try { derived_data_source($text); throw new \LogicException('Invalid derived data accepted'); }
		catch (\RuntimeException $error) { if (!str_contains($error->getMessage(), $message)) { throw $error; } }
	}
}

// The child and caller remain unchanged while the inherited parameter contract changes.
$directory = sys_get_temp_dir() . '/derived-data-' . bin2hex(random_bytes(6)); mkdir($directory);
$base_path = $directory . '/base.phs'; $child_path = $directory . '/child.phs'; $main_path = $directory . '/main.phs';
try {
	$base = 'namespace Types; class B { int $n; function __construct(int $input = 9) { $this->n = $input; } }';
	$child = 'namespace Types; data class C extends B {}';
	file_put_contents($base_path, $base); file_put_contents($child_path, $child);
	file_put_contents($main_path, 'use Types\C; $c C = {input: 8}; return $c->n;');
	Compiler_Lifecycle::reset(); $compiler = new Compiler(); $compiler->init([$directory]); $compiler->select_entry('main', 'main.phs'); $compiler->exec_cpp();
	$original = CPP_Output::encode(Model::$cpp_files); $compiler->update_cpp([]);
	if ($original !== CPP_Output::encode(Model::$cpp_files)) { throw new \LogicException('No-op changed derived data output'); }
	foreach ([str_replace('$input', '$renamed', $base),
		'namespace Types; class B { int $n; function __construct(string $input = "ok") { $this->n = 9; } }',
		'namespace Types; class B { int $n; }'] as $invalid) {
		file_put_contents($base_path, $invalid);
		foreach ([[$base_path], []] as $changes) {
			try { $compiler->update_cpp($changes); throw new \LogicException('Invalid inherited data survived edit/retry'); }
			catch (\RuntimeException $expected) {}
		}
		file_put_contents($base_path, $base); $compiler->update_cpp([$base_path]);
		if ($original !== CPP_Output::encode(Model::$cpp_files)) { throw new \LogicException('Repair changed derived data output'); }
	}
	file_put_contents($child_path, 'namespace Types; data class C extends B { function __construct(int $input) { parent::__construct($input + 1); } }');
	$compiler->update_cpp([$child_path]);
	$repaired = CPP_Output::encode(Model::$cpp_files); $cpp = native_record_export();
	if (isset($argv[1])) {
		$path = $argv[1] . '/cross_file.cpp'; file_put_contents($path, $cpp);
		$results[] = ['path' => $path, 'exit_code' => 9];
	}
	Compiler_Lifecycle::reset_preparation(); $compiler->prepare(); $compiler->cpp();
	if ($repaired !== CPP_Output::encode(Model::$cpp_files)) { throw new \LogicException('Reset changed derived data output'); }
	Compiler_Lifecycle::reset(); $compiler = new Compiler(); $compiler->init([$directory]); $compiler->select_entry('main', 'main.phs'); $compiler->exec_cpp();
	if ($repaired !== CPP_Output::encode(Model::$cpp_files)) { throw new \LogicException('Fresh/incremental derived data mismatch'); }
}
finally {
	Compiler_Lifecycle::reset();
	foreach ([$base_path, $child_path, $main_path] as $path) { if (is_file($path)) { unlink($path); } }
	rmdir($directory);
}
if (isset($argv[1])) { file_put_contents($argv[1] . '/executions.json', json_encode($results, JSON_PRETTY_PRINT) . "\n"); }
echo "Derived data: shared constructor contracts, boundaries and edit recovery passed\n";
