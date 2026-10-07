<?php

/* Shared class/struct defaults: runtime results, declaration dependencies and recovery. */
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';
require_once __DIR__ . '/native_record_export.php';

/** Compile through the actual stage/publication pipeline, preserving artifact boundaries. */
function property_defaults_source(string $source): string
{
	Compiler_Lifecycle::reset();
	$input_module = new module('/property-defaults', '/property-defaults', '/property-defaults');
	$input = new file();
	$input->path = 'main.phs';
	$input->content = $source;
	Source_Registry::add($input_module, $input);
	Model::$modules->add($input_module->name, $input_module);
	(new Compiler())->exec_cpp();
	return native_record_export();
}

$results = [];
foreach (['class', 'struct'] as $kind) {
	$cases = [
		'inferred' => ['const N = 7; ' . $kind . ' C { public $n = N; $f = 2.5; $b = true; $s = "8"; static $total = 3; } $c = new C(); $c->n++; C::$total += 2; $n = $c->n; $s = (int)$c->s; $f = (int)$c->f; $b = $c->b; $t = C::$total; if (!$b) { return 1; } return $n + $s + $f + $t;', 23],
		'inferred_enum' => ['enum E { case First; case Second; } ' . $kind . ' C { $e = E::Second; static $other = E::First; } $c = new C(); $e = $c->e; return $e === E::Second ? 9 : 1;', 9],
		'inferred_data' => [$kind . ' C { $v = vector<int>::[7, 2]; $d = dynamic::{x: 5}; } $c = new C(); $v = $c->v; $d = $c->d; $a int = $v[0]; $b int = $d["x"]; return $a + $b;', 12],
		'inferred_constructor' => ['data ' . $kind . ' C { public $n = 7; } $c C = {n: 9}; return $c->n;', 9],
		'scalar' => ['const N = 7; ' . $kind . ' C { int $n = -(-N); float $f = 2.5; bool $b = true; string $s = "7"; } $c = new C(); $f = (int) $c->f; $b = $c->b; $s = (int) $c->s; if ($b && $f === 2 && $s === 7) { return $c->n; } return 1;', 7],
		'nullable' => [$kind . ' C { ?int $a = null; ?int $b = 8; } $c = new C(); $a = $c->a; $b = $c->b; if ($a === null) { return $b ?? 1; } return 2;', 8],
		'enum' => ['enum E { case First; case Second; } ' . $kind . ' C { E $e = E::Second; } $c = new C(); $e = $c->e; return $e === E::Second ? 9 : 1;', 9],
		'boxed' => [$kind . ' C { mixed $m = 7; dynamic $d = 8; } $c = new C(); $n int = $c->m; $d int = $c->d; return $n + $d;', 15],
		'owned' => [$kind . ' C { int $n = 9; } $c unique<C> = new C(); return $c->n;', 9],
		'per_instance' => [$kind . ' C { int $n = 7; string $s = "7"; } $a = new C(); $a->n = 9; $a->s = "9"; $b = new C(); $s = (int) $b->s; return $s === 7 ? $b->n : 1;', 7],
		'nested' => ['struct Inner { int $n = 9; } ' . $kind . ' C { Inner $inner; } $c = new C(); return $c->inner->n;', 9],
		'containers' => ['const N = 7; ' . $kind . ' C { vector<int> $v = [N, 2,]; hash<int> $h = {x: 3}; mixed $m = {x: 4}; dynamic $d = {x: 5}; } $c = new C(); $v = $c->v; $h = $c->h; $m = $c->m; $d = $c->d; $a int = $v[0]; $b int = $h["x"]; $e int = $m["x"]; $f int = $d["x"]; return $a + $b + $e + $f;', 19],
		'nested_containers' => [$kind . ' C { vector<vector<int>> $v = [[9]]; dynamic $d = {inner: {x: 7}}; } $c = new C(); $v = $c->v; $d = $c->d; $a int = $v[0][0]; $b int = $d["inner"]["x"]; return $a + $b;', 16],
		'container_instances' => [$kind . ' C { dynamic $d = {x: 7}; vector<int> $v = [7]; } $a = new C(); $b = new C(); $d = $a->d; $d["x"] = 9; $v = $a->v; $v[0] = 9; $a->v = $v; $other = $b->d; $bv = $b->v; $x int = $other["x"]; $y int = $bv[0]; return $x + $y;', 14],
	];
	foreach ($cases as $name => [$source, $expected]) {
		$output = property_defaults_source($source);
		if (isset($argv[1])) {
			$path = $argv[1] . '/' . $kind . '_' . $name . '.cpp';
			file_put_contents($path, $output);
			$results[] = ['path' => $path, 'exit_code' => $expected];
		}
	}
	$storage = $kind === 'class' ? 'value<C>' : 'shared<C>';
	$output = property_defaults_source($kind . ' C { int $n = 9; } $c ' . $storage . ' = new C(); return $c->n;');
	if (isset($argv[1])) {
		$path = $argv[1] . '/' . $kind . '_storage_override.cpp';
		file_put_contents($path, $output);
		$results[] = ['path' => $path, 'exit_code' => 9];
	}
	foreach ([
		'$n;',
		'$n = null;',
		'$n = [];',
		'$n = {x: 1};',
		'$n = calculate();',
		'static $n = vector<int>::[1];',
		'int $n = missing;',
		'int $n = "wrong";',
		'int $n = null;',
		'int $n = calculate();',
		'int $n = $this->other;',
		'vector<int> $n = [calculate()];',
		'E $n = F::First;',
	] as $property) {
		try {
			property_defaults_source('enum E { case First; } enum F { case First; } ' . $kind . ' C { ' . $property . ' }');
			throw new \LogicException('Unexpected default acceptance: ' . $property);
		}
		catch (\RuntimeException $error) {
			// Unused declarations must reject before output, without relying on a C++ failure.
		}
	}
}

// A default reads its declaration namespace/import environment, not the caller's locals.
$directory = sys_get_temp_dir() . '/property-defaults-' . bin2hex(random_bytes(6));
mkdir($directory);
$constants_path = $directory . '/constants.phs';
$types_path = $directory . '/types.phs';
$main_path = $directory . '/main.phs';
try {
	file_put_contents($constants_path, 'namespace Defaults; const N = 7;');
	file_put_contents($types_path, 'namespace Types; use const Defaults\N as LIMIT; class C { int $n = LIMIT; }');
	file_put_contents($main_path, 'use Types\C; $c = new C(); return $c->n;');
	Compiler_Lifecycle::reset();
	$compiler = new Compiler();
	$compiler->init([$directory]);
	$compiler->select_entry('main', 'main.phs');
	$compiler->exec_cpp();
	$original = CPP_Output::encode(Model::$cpp_files);
	$compiler->update_cpp([]);
	if (CPP_Output::encode(Model::$cpp_files) !== $original) {
		throw new \LogicException('No-op changed defaults output');
	}
	file_put_contents($constants_path, 'namespace Defaults; const N = 9;');
	$compiler->update_cpp([$constants_path]);
	$changed = CPP_Output::encode(Model::$cpp_files);
	if ($changed === $original) {
		throw new \LogicException('Changed constant did not invalidate property default');
	}
	file_put_contents($constants_path, 'namespace Defaults; const N = "wrong";');
	foreach ([[$constants_path], []] as $changes) {
		try {
			$compiler->update_cpp($changes);
			throw new \LogicException('Invalid default survived dependency edit/retry');
		}
		catch (\RuntimeException $error) {
		}
	}
	file_put_contents($constants_path, 'namespace Defaults; const N = 9;');
	$compiler->update_cpp([$constants_path]);
	if (CPP_Output::encode(Model::$cpp_files) !== $changed) {
		throw new \LogicException('Repair retained stale defaults');
	}
	foreach (['int $n = 4;', 'int $n;', 'int $n = LIMIT;'] as $property) {
		file_put_contents($types_path, 'namespace Types; use const Defaults\N as LIMIT; class C { ' . $property . ' }');
		$compiler->update_cpp([$types_path]);
	}
	$incremental = CPP_Output::encode(Model::$cpp_files);
	Compiler_Lifecycle::reset();
	$fresh = new Compiler();
	$fresh->init([$directory]);
	$fresh->select_entry('main', 'main.phs');
	$fresh->exec_cpp();
	if (CPP_Output::encode(Model::$cpp_files) !== $incremental) {
		throw new \LogicException('Incremental defaults differ from fresh compilation');
	}
	Compiler_Lifecycle::reset_preparation();
	$fresh->prepare();
	$fresh->cpp();
	if (CPP_Output::encode(Model::$cpp_files) !== $incremental) {
		throw new \LogicException('Cleanup changed defaults');
	}
	if (isset($argv[1])) {
		$path = $argv[1] . '/imported_default.cpp';
		file_put_contents($path, CPP_Probe::text(Model::$cpp_files));
		$results[] = ['path' => $path, 'exit_code' => 9];
	}
	// An imported default owns both inferred layout and initializer dependencies.
	file_put_contents($types_path, 'namespace Types; use const Defaults\\N as LIMIT; class C { public $n = LIMIT; }');
	file_put_contents($main_path, 'use Types\\C; $c = new C(); return (int)$c->n;');
	$fresh->update_cpp([$types_path, $main_path]);
	$inferred_int = CPP_Output::encode(Model::$cpp_files);
	file_put_contents($constants_path, 'namespace Defaults; const N = "8";');
	$fresh->update_cpp([$constants_path]);
	$inferred_string = CPP_Output::encode(Model::$cpp_files);
	if ($inferred_string === $inferred_int) { throw new \LogicException('Inferred property type did not invalidate output'); }
	Compiler_Lifecycle::reset();
	$check = new Compiler();
	$check->init([$directory]);
	$check->select_entry('main', 'main.phs');
	$check->exec_cpp();
	if (CPP_Output::encode(Model::$cpp_files) !== $inferred_string) { throw new \LogicException('Inferred type edit differs from fresh build'); }
	file_put_contents($constants_path, 'namespace Defaults; const N = null;');
	foreach ([[$constants_path], []] as $changes) {
		try { $check->update_cpp($changes); throw new \LogicException('Null established inferred property type'); }
		catch (\RuntimeException $expected) {}
	}
	file_put_contents($constants_path, 'namespace Defaults; const N = "8";');
	$check->update_cpp([$constants_path]);
	if (CPP_Output::encode(Model::$cpp_files) !== $inferred_string) { throw new \LogicException('Inferred property repair retained stale facts'); }
}
finally {
	unlink($constants_path);
	unlink($types_path);
	unlink($main_path);
	rmdir($directory);
}
if (isset($argv[1])) {
	file_put_contents($argv[1] . '/executions.json', json_encode($results));
}
echo "property defaults pass\n";
