<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

function contextual_check(bool $condition, string $message): void
{
	if (!$condition) { throw new \LogicException($message); }
}

$output = $argv[1];
$directory = sys_get_temp_dir() . '/contextual-closures-' . bin2hex(random_bytes(6)); mkdir($directory);
$path = $directory . '/main.phs';
$provider = $directory . '/api.phs';
$cases = [
	'local' => ['$f function<int(int)> = function ($n) { return $n + 1; }; return $f(4);', 5],
	'arrow' => ['$f function<int(int)> = fn($n) => $n + 2; return $f(4);', 6],
	'return_only' => ['$f function<int(int)> = fn(int $n) => $n + 2; return $f(4);', 6],
	'parameter_only' => ['$f function<int(int)> = fn($n): int => $n + 2; return $f(4);', 6],
	'assignment' => ['$f function<int(int)> = fn($n) => $n; $f = fn($n) => $n + 3; return $f(4);', 7],
	'argument' => ['function apply(function<int(int)> $f): int { return $f(4); } return apply(fn($n) => $n + 4);', 8],
	'return' => ['function make(int $n): function<int(int)> { return fn($v) => $v + $n; } $f = make(3); return $f(4);', 7],
	'nested' => ['$make function<function<int(int)>(int)> = fn($base) => fn($n) => $base + $n; $f = $make(3); return $f(5);', 8],
	'method' => ['class Box { public function apply(function<int(int)> $f): int { return $f(4); } public static function make(): function<int(int)> { return fn($n) => $n + 1; } } $b = new Box(); $f = Box::make(); return $b->apply(fn($n) => $f($n));', 5],
	'struct' => ['struct Box { public function make(): function<int()> { $n = 6; return function () use ($n) { return $n; }; } } $b = new Box(); $f = $b->make(); return $f();', 6],
	'void' => ['$f function<void()> = function () { return; }; $f(); return 0;', 0],
];
$invalid = [
	'$f = fn($n) => $n; return 0;',
	'$f = fn($n): int => $n; return 0;',
	'$f = fn(int $n) => $n; return 0;',
	'$f function<int(int)> = fn(float $n) => 1; return 0;',
	'$f function<int(int)> = fn($n): string => "x"; return 0;',
	'$f function<int(int)> = fn() => 1; return 0;',
	'$f function<int(int)> = fn($n, $other) => $n; return 0;',
	'$f function<int(int)> = fn(&$n) => $n; return 0;',
	'$f function<int(int)> = fn($n = 3) => $n; return 0;',
	'$n = 1; $f function<int()> = function () use (&$n) { return $n; }; return 0;',
	'class Box { public int $n = 1; public function make(): function<int()> { return fn() => $this->n; } } return 0;',
];
$executions = [];
try {
	foreach ($cases as $name => [$source, $exit]) {
		Compiler_Lifecycle::reset(); file_put_contents($path, $source);
		$compiler = new Compiler(); $compiler->init([$directory]); $compiler->exec_cpp();
		$text = CPP_Probe::text(Model::$cpp_files);
		$compiler->exec_cpp(); contextual_check(CPP_Probe::text(Model::$cpp_files) === $text, 'No-op changed contextual output');
		file_put_contents($output . '/' . $name . '.cpp', $text);
		$executions[] = ['path' => $output . '/' . $name . '.cpp', 'exit_code' => $exit];
	}
	foreach ($invalid as $source) {
		Compiler_Lifecycle::reset(); file_put_contents($path, $source);
		$compiler = new Compiler(); $compiler->init([$directory]); $compiler->sync([]); $rejected = false;
		try { $compiler->prepare(); } catch (\RuntimeException $error) { $rejected = true; }
		contextual_check($rejected, 'Invalid contextual closure accepted: ' . $source);
	}
	Compiler_Lifecycle::reset(); file_put_contents($path, 'return apply(fn($n) => $n);');
	$api = 'function apply(function<int(int)> $f): int { return $f(4); }';
	file_put_contents($provider, $api);
	$compiler = new Compiler(); $compiler->init([$directory]); $compiler->select_entry('main', 'main.phs'); $compiler->exec_cpp();
	foreach (Model::collected_files() as $collection) {
		foreach ($collection->anonymous_callables as $identity) { $retained = $identity; }
	}
	$syntax = $retained->syntax(); $signature_work = $retained->signature_work;
	contextual_check($syntax->signature_syntax->return_type === null, 'Preparation fabricated return syntax');
	contextual_check($syntax->signature_syntax->parameters[0]->type_syntax === null, 'Preparation fabricated parameter syntax');
	$version = $signature_work->version; $compiler->exec_cpp();
	contextual_check($signature_work->version === $version, 'No-op rebuilt contextual signature');
	$float_api = str_replace('$f(4)', '$f(4.0)', str_replace('int', 'float', $api));
	file_put_contents($provider, $float_api); $compiler->update_cpp([$provider]);
	contextual_check($retained->syntax() === $syntax, 'Provider edit replaced anonymous source identity');
	contextual_check($signature_work->version > $version, 'Provider edit did not update contextual signature');
	$updated = CPP_Probe::text(Model::$cpp_files);
	file_put_contents($provider, str_replace('function<int(int)>', 'function<int(int, int)>', $api)); $rejected = false;
	try { $compiler->update_cpp([$provider]); } catch (\RuntimeException $error) { $rejected = true; }
	contextual_check($rejected, 'Changed contextual arity was accepted');
	file_put_contents($provider, $float_api); $compiler->update_cpp([$provider]);
	contextual_check(CPP_Probe::text(Model::$cpp_files) === $updated, 'Contextual recovery differs');
	Compiler_Lifecycle::reset(); $compiler = new Compiler(); $compiler->init([$directory]); $compiler->select_entry('main', 'main.phs'); $compiler->exec_cpp();
	contextual_check(CPP_Probe::text(Model::$cpp_files) === $updated, 'Fresh/incremental contextual output differs');
	file_put_contents($output . '/provider_edit.cpp', $updated);
	$executions[] = ['path' => $output . '/provider_edit.cpp', 'exit_code' => 4];
	foreach (Model::collected_files() as $collection) {
		foreach ($collection->anonymous_callables as $identity) { $retired_signature = $identity->signature_work; }
	}
	file_put_contents($path, 'return 0;'); $compiler->update_cpp([$path]);
	contextual_check($retired_signature->expected_type === null && $retired_signature->facts === null, 'Retirement retained contextual signature inputs');
	file_put_contents($output . '/executions.json', json_encode($executions));
	echo "Contextual closures: PASS\n";
}
finally { Compiler_Lifecycle::reset(); unlink($path); if (file_exists($provider)) { unlink($provider); } rmdir($directory); }
