<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

$output = $argv[1];
$directory = sys_get_temp_dir() . '/closure-execution-' . bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory . '/main.phs';
$cases = [
	'capture_snapshot' => ['$n = 3; $f = function (int $x) use ($n): int { return $x + $n; }; $n = 8; return $f(4);', 7],
	'capture_copy' => ['$n = 3; $f = function () use ($n): int { return $n; }; $g = $f; return $g();', 3],
	'capture_nested' => ['$n = 4; $f = function () use ($n): int { $g = function () use ($n): int { return $n + 1; }; return $g(); }; return $f();', 5],
	'capture_alias' => ['$n = 4; $alias =& $n; $f = function () use ($alias): int { return $alias; }; $n = 9; return $f();', 4],
	'capture_vector' => ['$v vector<int> = [3]; $f = function () use ($v): int { return $v[0]; }; $v[0] = 8; return $f();', 3],
	'capture_shared' => ['class Box { public int $n = 3; } $box = new Box(); $old = $box; $f = function () use ($box): int { $box->n = 7; return $box->n; }; $box = new Box(); $f(); return $old->n;', 7],
	'capture_vector_return' => ['$n = 5; $f = function () use ($n): vector<int> { return [$n, 2]; }; $v = $f(); return $v[0];', 5],
	'capture_shared_method' => ['class Box { public int $n = 3; public function set(): void { $this->n = 8; } } $box = new Box(); $f = function () use ($box): void { $box->set(); }; $f(); return $box->n;', 8],
	'capture_dynamic' => ['$d dynamic = {n: 3}; $f = function () use ($d): void { $d["n"] = 9; }; $f(); $n int = $d["n"]; return $n;', 9],
	'capture_dynamic_remove' => ['$d dynamic = {n: 3}; $f = function () use ($d): void { unset($d["n"]); }; $f(); return isset($d["n"]);', 0],
	'capture_mixed' => ['$m mixed = [3]; $f = function () use ($m): int { $n int = $m[0]; return $n; }; $m[0] = 8; return $f();', 3],
	'capture_callable' => ['$n = 4; $f = function () use ($n): int { return $n; }; $g = function () use ($f): int { return $f(); }; return $g();', 4],
	'capture_parameter' => ['function calc(int $n): int { $f = function () use ($n): int { return $n + 1; }; return $f(); } return calc(5);', 6],
	'typed_capture' => ['function apply(function<int()> $f): int { return $f(); } $n = 6; return apply(function () use ($n): int { return $n; });', 6],
	'local' => ['$f = function (int $value): int { return $value + 2; }; return $f(5);', 7],
	'copy' => ['$f = function (int $value): int { return $value + 2; }; $g = $f; return $g(6);', 8],
	'nested' => ['$f = function (int $value): int { $g = function (int $inner): int { return $inner + 1; }; return $g($value); }; return $f(8);', 9],
	'void' => ['$f = function (): void { return; }; $f(); return 0;', 0],
	'typed_local' => ['$f function<int(int)> = function (int $x): int { return $x + 1; }; $f = function (int $other): int { return $other + 2; }; $g = $f; return $g(5);', 7],
	'typed_argument' => ['function apply(function<int(int)> $f, int $x): int { return $f($x); } return apply(function (int $n): int { return $n * 2; }, 4);', 8],
	'typed_method' => ['class Ops { public function apply(function<int(int)> $f): int { return $f(4); } public static function call(function<int(int)> $f): int { return $f(3); } } $o = new Ops(); $f = function (int $n): int { return $n + 1; }; $a = $o->apply($f); $b = Ops::call($f); return $a + $b;', 9],
	'typed_struct' => ['struct Ops { public function apply(function<int(int)> $f): int { return $f(4); } } $o = new Ops(); return $o->apply(function (int $n): int { return $n + 2; });', 6],
	'typed_void' => ['$f function<void()> = function (): void { return; }; $f(); return 0;', 0],
	'function_body' => ['function calculate(): int { $f = function (int $n): int { return $n * 2; }; return $f(6); } return calculate();', 12],
	'immediate' => ['return (function (int $n): int { return $n + 3; })(4);', 7],
	'ordered' => ['function next(int &$n): int { $n += 1; return $n; } $f = function (int $a, int $b): int { return $a * 10 + $b; }; $n = 0; return $f(next($n), next($n));', 12],
];
$executions = [];
try {
	foreach ($cases as $name => [$source, $exit]) {
		Compiler_Lifecycle::reset();
		file_put_contents($path, $source);
		$compiler = new Compiler(); $compiler->init([$directory]); $compiler->exec_cpp();
		$text = CPP_Probe::text(Model::$cpp_files);
		if (str_starts_with($name, 'typed_')) {
			if (!str_contains($text, 'std::function')) { throw new \LogicException('Explicit callable boundary was not emitted'); }
		}
		elseif (str_contains($text, 'std::function')) { throw new \LogicException('Concrete closure was erased'); }
		file_put_contents($output . '/' . $name . '.cpp', $text);
		$executions[] = ['path' => $output . '/' . $name . '.cpp', 'exit_code' => $exit];
		$type_count = Model::$type_catalog->registry()->type_count();
		$compiler->exec_cpp();
		if (Model::$type_catalog->registry()->type_count() !== $type_count) { throw new \LogicException('No-op recreated callable types'); }
		if (CPP_Probe::text(Model::$cpp_files) !== $text) { throw new \LogicException('No-op changed closure output'); }
	}
	$invalid = [
		'$f = function () use ($absent): int { return 1; }; return 0;',
		'$n = 1; $f = function () use ($n, $n): int { return $n; }; return 0;',
		'$n = 1; $f = function (int $n) use ($n): int { return $n; }; return 0;',
		'$n = 1; $f = function () use ($n): int { $n = 2; return $n; }; return 0;',
		'$n = 1; $f = function () use ($n): int { $n++; return $n; }; return 0;',
		'function change(int &$n): void { $n = 2; } $n = 1; $f = function () use ($n): void { change($n); }; return 0;',
		'$v vector<int> = [1]; $f = function () use ($v): void { $v[0] = 2; }; return 0;',
		'$d dynamic = {n: 1}; $f = function () use ($d): void { $d = dynamic::{n: 2}; }; return 0;',
		'struct Box { public int $n = 1; } $b = new Box(); $f = function () use ($b): void { $b->n = 2; }; return 0;',
		'class Box {} $b unique<Box> = new Box(); $f = function () use ($b): void { return; }; return 0;',
		'$m mixed = [1]; $f = function () use ($m): void { $m[0] = 2; }; return 0;',
		'$n = 1; $f = function (): int { $g = function () use ($n): int { return $n; }; return $g(); }; return 0;',
		'$f = function (int $a): int { return $a; }; return $f();',
		'$f = function (int $a): int { return $a; }; return $f(1, 2);',
		'$f = function (): int { return 1; }; $f = function (): int { return 2; }; return $f();',
		'$outer = 3; $f = function (): int { return $outer; }; return $f();',
		'$f mixed = function (): int { return 1; }; return 0;',
		'$value = 1; return $value();',
		'$f function<int()>; return 0;',
		'$f function<string()> = function (): int { return 1; }; return 0;',
		'$f function<int(int)> = function (float $n): int { return 1; }; return 0;',
		'$f function<int(int)> = function (): int { return 1; }; return 0;',
		'function make(): function<int()> { return function (): int { return 1; }; } return 0;',
	];
	foreach ($invalid as $source) {
		Compiler_Lifecycle::reset(); file_put_contents($path, $source);
		$compiler = new Compiler(); $compiler->init([$directory]); $compiler->sync([]);
		$rejected = false;
		try { $compiler->prepare(); } catch (\RuntimeException $error) { $rejected = true; }
		if (!$rejected) { throw new \LogicException('Invalid closure program was accepted: ' . $source); }
	}
	Compiler_Lifecycle::reset(); file_put_contents($path, $cases['local'][0]);
	$compiler = new Compiler(); $compiler->init([$directory]); $compiler->exec_cpp();
	file_put_contents($path, str_replace('+ 2', '+ 3', $cases['local'][0])); $compiler->update_cpp([$path]);
	$incremental = CPP_Probe::text(Model::$cpp_files);
	Compiler_Lifecycle::reset(); $compiler = new Compiler(); $compiler->init([$directory]); $compiler->exec_cpp();
	if (CPP_Probe::text(Model::$cpp_files) !== $incremental) { throw new \LogicException('Closure edit disagrees with clean output'); }
	$dependency_source = 'function external(int $n = 1): int { return $n; } $f = function (): int { return external(); }; return $f();';
	Compiler_Lifecycle::reset(); file_put_contents($path, $dependency_source);
	$compiler = new Compiler(); $compiler->init([$directory]); $compiler->exec_cpp();
	file_put_contents($path, str_replace('$n = 1', '$n = 2', $dependency_source)); $compiler->update_cpp([$path]);
	$dependency_output = CPP_Probe::text(Model::$cpp_files);
	Compiler_Lifecycle::reset(); $compiler = new Compiler(); $compiler->init([$directory]); $compiler->exec_cpp();
	if (CPP_Probe::text(Model::$cpp_files) !== $dependency_output) { throw new \LogicException('Anonymous dependency edit left stale C++'); }
	file_put_contents($output . '/dependency_edit.cpp', $dependency_output);
	$executions[] = ['path' => $output . '/dependency_edit.cpp', 'exit_code' => 2];
	$capture_source = 'function calc(int $n): int { $f = function () use ($n): int { return (int)$n; }; return $f(); } return calc(6);';
	Compiler_Lifecycle::reset(); file_put_contents($path, $capture_source);
	$compiler = new Compiler(); $compiler->init([$directory]); $compiler->exec_cpp();
	foreach (Model::collected_files()[0]->anonymous_callables as $identity) { $capture_identity = $identity; }
	$capture_body = $capture_identity->syntax()->require_body()->work();
	$capture_version = $capture_body->version;
	$capture_field = $capture_body->captures[0];
	$source_place = $capture_identity->syntax()->captures[0]->require_preparation()->place;
	if ($source_place->storage() === $capture_field || (new prepared_place($capture_field))->access() !== storage_access::read_only) {
		throw new \LogicException('Capture aliases source storage or lost read-only access');
	}
	$compiler->exec_cpp();
	if ($capture_body->version !== $capture_version) { throw new \LogicException('No-op rebuilt capture environment'); }
	$children = iterator_to_array($capture_identity->syntax()->children(), false);
	if ($children[0] !== $capture_identity->syntax()->captures[0]) { throw new \LogicException('Capture omitted from grammar-ordered inspection'); }
	$capture_changed = str_replace(['calc(int $n)', 'calc(6)'], ['calc(float $n)', 'calc(6.0)'], $capture_source);
	file_put_contents($path, $capture_changed);
	$compiler->update_cpp([$path]);
	if ($capture_body->captures[0]->type->matches($capture_field->type)) { throw new \LogicException('Signature edit retained stale capture input type'); }
	$capture_output = CPP_Probe::text(Model::$cpp_files);
	file_put_contents($path, 'return 0;'); $compiler->update_cpp([$path]);
	if (count($capture_body->captures) !== 0) { throw new \LogicException('Retired body retained capture environment'); }
	file_put_contents($path, $capture_changed);
	Compiler_Lifecycle::reset(); $compiler = new Compiler(); $compiler->init([$directory]); $compiler->exec_cpp();
	if (CPP_Probe::text(Model::$cpp_files) !== $capture_output) { throw new \LogicException('Capture signature edit disagrees with fresh output'); }
	file_put_contents($output . '/capture_signature_edit.cpp', $capture_output);
	$executions[] = ['path' => $output . '/capture_signature_edit.cpp', 'exit_code' => 6];
	foreach (['$n = 1; $f = function () use (&$n): int { return $n; };', '$f = function () use ($this): void { return; };'] as $deferred) {
		file_put_contents($path, $deferred); $rejected = false;
		try { $compiler->update_cpp([$path]); } catch (\RuntimeException $error) { $rejected = true; }
		if (!$rejected) { throw new \LogicException('Deferred capture was accepted'); }
		file_put_contents($path, $capture_changed); $compiler->update_cpp([$path]);
		if (CPP_Probe::text(Model::$cpp_files) !== $capture_output) { throw new \LogicException('Capture failure did not recover'); }
	}
	file_put_contents($output . '/executions.json', json_encode($executions));
	$registry = Model::$type_catalog->registry();
	$integer = Language_Types::integer(Model::$language_scope);
	$boolean = Language_Types::boolean(Model::$language_scope);
	$inputs = [$integer];
	$contract = $registry->define_callable($integer, $inputs);
	if ($registry->define_callable($integer, $inputs) !== $contract
		|| $registry->define_callable($boolean, $inputs) === $contract) { throw new \LogicException('Callable signature interning is not exact'); }
	echo "Closure execution artifacts: PASS\n";
}
finally { Compiler_Lifecycle::reset(); unlink($path); rmdir($directory); }
