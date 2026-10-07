<?php

/* Explicit data records use one class/struct contract and ordinary construction syntax. */
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

/** Preserve artifact boundaries for independent generated execution. */
function data_records_source(string $source): string
{
	Compiler_Lifecycle::reset();
	$input_module = new module('/data-records', '/data-records', '/data-records');
	$input = new file();
	$input->path = 'main.phs';
	$input->content = $source;
	Source_Registry::add($input_module, $input);
	Model::$modules->add($input_module->name, $input_module);
	(new Compiler())->exec_cpp();
	return CPP_Probe::text(Model::$cpp_files);
}

$results = [];
foreach (['class', 'struct'] as $kind) {
	$cases = [
		'defaults' => ['data ' . $kind . ' C { int $x = 7; int $y; vector<int> $v = [3]; } $c C = {y: 2}; $v = $c->v; $x = $c->x; $y = $c->y; $z = $v[0]; return $x + $y + $z;', 12],
		'explicit' => ['data ' . $kind . ' C { int $x; } $c = C::["x" => 9,]; return $c->x;', 9],
		'empty' => ['data ' . $kind . ' C {} $c C = {}; return 0;', 0],
		'ordinary_new' => ['data ' . $kind . ' C { int $n = 9; vector<int> $v = [7]; } $c = new C(); $v = $c->v; $n = $c->n; $x = $v[0]; return $n + $x;', 16],
		'required' => ['enum E { case A; case B; } data ' . $kind . ' C { E $e; } $c C = {e: E::B}; $e = $c->e; return $e === E::B ? 9 : 1;', 9],
		'sequence' => ['function bump(int &$n): int { $n += 1; return $n; } data ' . $kind . ' C { int $x; int $y; } $n = 0; $c C = {y: bump($n), x: bump($n)}; $x = $c->x; $y = $c->y; return $x * 10 + $y;', 21],
		'suppressed_default' => ['data ' . $kind . ' C { hash<int> $h = {x: 1, x: 2}; } $c C = {h: {ok: 9}}; $h = $c->h; return $h["ok"];', 9],
		'nested' => ['data struct Inner { int $n = 7; } data ' . $kind . ' C { Inner $i = {n: 9}; vector<Inner> $v = [{n: 3}]; } $c C = {}; $v = $c->v; $i = $v[0]; $a = $i->n; $b = $c->i->n; return $a + $b;', 12],
		'boundary' => ['data ' . $kind . ' C { int $n; } function make(): C { return {n: 9}; } function read(C $c): int { return $c->n; } $a = make(); $n = read({n: 7}); $b = $a->n; return $n + $b;', 16],
		'copy' => ['data ' . $kind . ' C { int $n; } $a C = {n: 7}; $b = $a; $b->n = 9; return $a->n;', $kind === 'class' ? 9 : 7],
		'unique' => ['data ' . $kind . ' C { int $n; } $a unique<C> = {n: 9}; return $a->n;', 9],
		'nested_class' => ['data class Inner { int $n; } data ' . $kind . ' C { Inner $i = {n: 7}; } $a C = {}; $b C = {}; $a->i->n = 9; return $b->i->n;', 7],
	];
	$storage = $kind === 'class' ? 'value<C>' : 'shared<C>';
	$cases['storage'] = ['data ' . $kind . ' C { int $n; } $c ' . $storage . ' = {n: 9}; return $c->n;', 9];
	foreach ($cases as $name => [$source, $expected]) {
		$output = data_records_source($source);
		if (isset($argv[1])) {
			$path = $argv[1] . '/' . $kind . '_' . $name . '.cpp';
			file_put_contents($path, $output);
			$results[] = ['path' => $path, 'exit_code' => $expected];
		}
	}
}

$recursive = data_records_source('data class A { B $b; int $n = 9; } data class B { A $a; } $a A = {}; return $a->n;');
if (isset($argv[1])) {
	$path = $argv[1] . '/recursive_handles.cpp';
	file_put_contents($path, $recursive);
	$results[] = ['path' => $path, 'exit_code' => 9];
}

foreach ([
	['class C { int $x; } $c C = {x: 1};', 'data opt-in'],
	['data class C { int $x; } $c C = {x: 1, x: 2};', 'duplicate record'],
	['data struct C { int $x; } $c C = {y: 1};', 'unknown record'],
	['data struct C { int $x; } $key = "x"; $c C = [$key => 1];', 'literal string keys'],
	['data class C { int $x; } $c C = [1];', 'literal string keys'],
	['enum E { case A; } data struct C { E $e; } $c C = {};', 'missing required'],
	['enum E { case A; } data class C { E $e; } $c = new C();', 'default-constructible'],
	['data class C { int $x; } $c C = {x: "wrong"};', 'value boundary'],
	['data class C { int $x; } $c weak<C> = {x: 1};', 'cannot allocate'],
] as [$source, $message]) {
	try {
		data_records_source($source);
		throw new \LogicException('Unexpected record construction acceptance: ' . $source);
	}
	catch (\RuntimeException $error) {
		if (!str_contains($error->getMessage(), $message)) {
			throw new \LogicException('Wrong diagnostic: ' . $error->getMessage());
		}
	}
}

// Schema/default changes must invalidate retained callers, including failed no-change retries.
$directory = sys_get_temp_dir() . '/data-records-' . bin2hex(random_bytes(6));
mkdir($directory);
$types_path = $directory . '/types.phs';
$main_path = $directory . '/main.phs';
try {
	$declaration = 'namespace Types; const N = 7; data class C { int $n; vector<int> $v = [N]; }';
	file_put_contents($types_path, $declaration);
	file_put_contents($main_path, 'use Types\C; $c C = {n: 2}; $v = $c->v; $a = $v[0]; $b = $c->n; return $a + $b;');
	Compiler_Lifecycle::reset();
	$compiler = new Compiler();
	$compiler->init([$directory]);
	$compiler->select_entry('main', 'main.phs');
	$compiler->exec_cpp();
	$original = CPP_Output::encode(Model::$cpp_files);
	$compiler->cpp();
	if (CPP_Output::encode(Model::$cpp_files) !== $original) {
		throw new \LogicException('Repeated generation changed data record output');
	}
	foreach ([str_replace('data class', 'class', $declaration), str_replace('int $n;', 'int $renamed;', $declaration)] as $invalid) {
		file_put_contents($types_path, $invalid);
		foreach ([[$types_path], []] as $changes) {
			try {
				$compiler->update_cpp($changes);
				throw new \LogicException('Changed schema survived preparation/retry');
			}
			catch (\RuntimeException $error) {
			}
		}
		file_put_contents($types_path, $declaration);
		$compiler->update_cpp([$types_path]);
		if (CPP_Output::encode(Model::$cpp_files) !== $original) {
			throw new \LogicException('Schema repair differs from original');
		}
	}
	file_put_contents($types_path, str_replace('N = 7', 'N = 9', $declaration));
	$compiler->update_cpp([$types_path]);
	$incremental = CPP_Output::encode(Model::$cpp_files);
	if ($incremental === $original) {
		throw new \LogicException('Container default dependency change was lost');
	}
	Compiler_Lifecycle::reset();
	$fresh = new Compiler();
	$fresh->init([$directory]);
	$fresh->select_entry('main', 'main.phs');
	$fresh->exec_cpp();
	if (CPP_Output::encode(Model::$cpp_files) !== $incremental) {
		throw new \LogicException('Fresh schema differs from incremental');
	}
	Compiler_Lifecycle::reset_preparation();
	$fresh->prepare();
	$fresh->cpp();
	if (CPP_Output::encode(Model::$cpp_files) !== $incremental) {
		throw new \LogicException('Schema cleanup changed output');
	}
	if (isset($argv[1])) {
		$path = $argv[1] . '/imported_schema.cpp';
		file_put_contents($path, CPP_Probe::text(Model::$cpp_files));
		$results[] = ['path' => $path, 'exit_code' => 11];
	}
}
finally {
	unlink($types_path);
	unlink($main_path);
	rmdir($directory);
}

if (isset($argv[1])) {
	file_put_contents($argv[1] . '/executions.json', json_encode($results));
}
echo "data record construction passes\n";
