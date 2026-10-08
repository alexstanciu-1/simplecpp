<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

$output = $argv[1];
$directory = sys_get_temp_dir() . '/closure-execution-' . bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory . '/main.phs';
$cases = [
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
