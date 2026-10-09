<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

$output = $argv[1];
$directory = sys_get_temp_dir() . '/callable-storage-' . bin2hex(random_bytes(6)); mkdir($directory);
$path = $directory . '/main.phs';
$cases = [
	'vector' => ['$items vector<function<int(int)>> = [fn($n) => $n + 1]; return $items[0](4);', 5],
	'hash' => ['$items hash<function<int(int)>> = {next: fn($n) => $n + 2}; return $items["next"](4);', 6],
	'append' => ['$items vector<function<int(int)>> = []; $items[] = fn($n) => $n + 3; return $items[0](4);', 7],
	'write' => ['$items hash<function<int(int)>> = {}; $items["next"] = fn($n) => $n + 4; return $items["next"](4);', 8],
	'copy' => ['$items vector<function<int()>> = [fn() => 3]; $copy = $items; $items[0] = fn() => 9; return $copy[0]();', 3],
	'nested' => ['$items vector<hash<function<int()>>> = [{run: fn() => 7}]; return $items[0]["run"]();', 7],
	'foreach' => ['$items vector<function<int()>> = [fn() => 2, fn() => 3]; $sum = 0; foreach ($items as $f) { $n = $f(); $sum += $n; } return $sum;', 5],
	'presence' => ['$f function<int()>; $items hash<function<int()>> = {absent: $f, present: fn() => 1}; if (isset($items["absent"])) { return 1; } if (isset($items["missing"])) { return 2; } if (!isset($items["present"])) { return 3; } return 0;', 0],
	'vector_presence' => ['$f function<int()>; $items vector<function<int()>> = [$f]; return isset($items[0]) ? 9 : 0;', 0],
	'lazy_presence' => ['class Log { public static int $n = 0; } function key(): int { Log::$n++; return 0; } $items hash<vector<function<int()>>> = {}; $has = isset($items["missing"][key()]); return Log::$n;', 0],
	'remove' => ['$items hash<function<int()>> = {run: fn() => 1}; unset($items["run"]); return isset($items["run"]) ? 9 : 0;', 0],
	'container_boundary' => ['function make(): vector<function<int()>> { return [fn() => 7]; } function use_items(vector<function<int()>> $items): int { return $items[0](); } return use_items(make());', 7],
	'callee_snapshot' => ['class C { public function<int(int)> $handler; } function mark(C $c): int { $c->handler = fn($n) => 9; return 2; } $c = new C(); $c->handler = fn($n) => $n + 1; return ($c->handler)(mark($c));', 3],
];
foreach (['class', 'struct'] as $kind) {
	$prefix = $kind . ' C { public function<int(int)> $handler; public static function<int(int)> $other; public function handler(int $n): int { return $n + 1; } } ';
	$cases[$kind . '_property'] = [$prefix . '$c = new C(); $c->handler = fn($n) => $n + 2; return ($c->handler)(4);', 6];
	$cases[$kind . '_method'] = [$prefix . '$c = new C(); $c->handler = fn($n) => $n + 2; return $c->handler(4);', 5];
	$cases[$kind . '_absent'] = [$prefix . '$c = new C(); if ($c->handler) { return 9; } return 0;', 0];
	$cases[$kind . '_static'] = [$prefix . 'C::$other = fn($n) => $n + 3; return (C::$other)(4);', 7];
	$cases[$kind . '_constructor'] = [$kind . ' C { public function<int(int)> $handler; public function __construct(set:$handler) {} } $c = new C(fn($n) => $n + 4); return ($c->handler)(4);', 8];
	$cases[$kind . '_data'] = ['data ' . $kind . ' C { public function<int(int)> $handler; } $c C = {handler: fn($n) => $n + 5}; return ($c->handler)(4);', 9];
	$cases[$kind . '_data_absent'] = ['data ' . $kind . ' C { public function<int(int)> $handler; } $c C = {}; return !$c->handler ? 0 : 9;', 0];
	$cases[$kind . '_absent_invoke'] = [$prefix . '$c = new C(); $unused = ($c->handler)(4); return 99;', 0];
	$cases[$kind . '_container_property'] = [$kind . ' C { public vector<function<int(int)>> $handlers; } $c = new C(); $c->handlers = [fn($n) => $n + 1]; return $c->handlers[0](4);', 5];
}
$executions = [];
try {
	foreach ($cases as $name => [$source, $exit]) {
		Compiler_Lifecycle::reset(); file_put_contents($path, $source);
		$compiler = new Compiler(); $compiler->init([$directory]);
		try { $compiler->exec_cpp(); } catch (\RuntimeException $error) { throw new \RuntimeException($name . ': ' . $error->getMessage()); }
		$text = CPP_Probe::text(Model::$cpp_files); $compiler->exec_cpp();
		if (CPP_Probe::text(Model::$cpp_files) !== $text) { throw new \LogicException('Callable storage no-op changed output'); }
		if (str_ends_with($name, '_absent_invoke')) {
			$text = str_replace('int main(int argc, char** argv)', 'int main(int argc, char** argv) try', $text);
			$end = strrpos($text, '// SCPP-END-ARTIFACT');
			if ($end === false) { $end = strlen($text); }
			$text = substr_replace($text, "catch (const std::bad_function_call &) { return 0; }\n", $end, 0);
		}
		file_put_contents($output . '/' . $name . '.cpp', $text);
		$executions[] = ['path' => $output . '/' . $name . '.cpp', 'exit_code' => $exit];
	}
	foreach ([
		'$items vector<function<int()>> = [fn(): string => "wrong"]; return 0;',
		'$items hash<function<int()>> = {}; $items["run"] = fn(): string => "wrong"; return 0;',
		'$n = 1; $items vector<function<int()>> = [function () use (&$n): int { return $n; }]; return 0;',
		'$n = 1; $items vector<function<int()>> = []; $items[] = function () use (&$n): int { return $n; }; return 0;',
		'$n = 1; $borrow = function () use (&$n): int { return $n; }; $items vector<function<int()>> = [fn(): int => $borrow()]; return 0;',
		'class C { public function<int()> $handler; } $c = new C(); $n = 1; $c->handler = function () use (&$n): int { return $n; }; return 0;',
		'class C { public function<int()> $handler; public int $n = 1; public function set(): void { $this->handler = fn(): int => $this->n; } } return 0;',
		'class C { public function<int()> $handler = fn(): int => 1; } return 0;',
		'class C { public static function<int()> $handler = fn(): int => 1; } return 0;',
		'$items vector<function<int()>> = [fn() => 1]; return empty($items[0]);',
		'$f nullable<function<int()>>; return 0;',
		'$items mixed = {}; $items["run"] = fn(): int => 1; return 0;',
		'$items dynamic = {}; $items["run"] = fn(): int => 1; return 0;',
	] as $source) {
		Compiler_Lifecycle::reset(); file_put_contents($path, $source);
		$compiler = new Compiler(); $compiler->init([$directory]); $compiler->sync([]); $rejected = false;
		try { $compiler->prepare(); } catch (\RuntimeException $error) { $rejected = true; }
		if (!$rejected) { throw new \LogicException('Invalid callable storage accepted: ' . $source); }
	}
	Compiler_Lifecycle::reset();
	$provider = $directory . '/holder.phs';
	$declaration = 'class Holder { public function<int(int)> $handler; }';
	file_put_contents($provider, $declaration);
	file_put_contents($path, '$c = new Holder(); $c->handler = fn($n) => $n; return ($c->handler)(4);');
	$compiler = new Compiler(); $compiler->init([$directory]); $compiler->select_entry('main', 'main.phs'); $compiler->exec_cpp();
	$before = CPP_Probe::text(Model::$cpp_files);
	file_put_contents($provider, str_replace('int(int)', 'int(int, int)', $declaration)); $rejected = false;
	try { $compiler->update_cpp([$provider]); } catch (\RuntimeException $error) { $rejected = true; }
	if (!$rejected) { throw new \LogicException('Changed property signature accepted'); }
	file_put_contents($provider, $declaration); $compiler->update_cpp([$provider]);
	if (CPP_Probe::text(Model::$cpp_files) !== $before) { throw new \LogicException('Property signature recovery differs'); }
	Compiler_Lifecycle::reset(); $compiler = new Compiler(); $compiler->init([$directory]); $compiler->select_entry('main', 'main.phs'); $compiler->exec_cpp();
	if (CPP_Probe::text(Model::$cpp_files) !== $before) { throw new \LogicException('Fresh property signature output differs'); }
	file_put_contents($output . '/property_recovery.cpp', $before);
	$executions[] = ['path' => $output . '/property_recovery.cpp', 'exit_code' => 4];
	unlink($provider);
	file_put_contents($output . '/executions.json', json_encode($executions));
	echo "Callable property/container storage: PASS\n";
}
finally { Compiler_Lifecycle::reset(); unlink($path); rmdir($directory); }
