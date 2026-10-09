<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

$output = $argv[1];
$directory = sys_get_temp_dir() . '/callable-reference-returns-' . bin2hex(random_bytes(6)); mkdir($directory);
$path = $directory . '/main.phs';
$cases = [
	'alias' => ['$f function<int&(int&)> = fn&(int &$n): int => $n; $n = 4; $alias =& $f($n); $alias++; return $n;', 5],
	'contextual' => ['$f function<int&(int&)> = fn&(&$n) => $n; $n = 4; $alias =& $f($n); $alias += 2; return $n;', 6],
	'copy' => ['$f function<int&(int&)> = fn&(&$n) => $n; $n = 4; $copy = $f($n); $copy++; return $n;', 4],
	'factory' => ['function make(): function<int&(int&)> { return fn&(&$n) => $n; } $n = 4; $alias =& make()($n); $alias += 3; return $n;', 7],
	'copy_reset' => ['$f function<int&(int&)> = fn&(&$n) => $n; $g = $f; $n = 4; $alias =& $g($n); clean($f); clean($g); $alias++; return $n;', 5],
	'forward' => ['function &forward(function<int&(int&)> $f, int &$n): int { return $f($n); } $n = 3; $alias =& forward(fn&(&$n) => $n, $n); $alias += 4; return $n;', 7],
	'named_origin' => ['function &select(int &$n): int { return $n; } $f function<int&(int&)> = fn&(&$n) => select($n); $n = 4; $alias =& $f($n); $alias++; return $n;', 5],
	'nonzero_origin' => ['$f function<int&(bool, int&)> = function &(bool $flag, int &$n): int { if ($flag) { $n++; } return $n; }; $n = 4; $alias =& $f(true, $n); $alias++; return $n;', 6],
	'variadic' => ['$f function<int&(int&, int...)> = function &(&$n, ...$values) { foreach ($values as $v) { $n += $v; } return $n; }; $n = 1; $alias =& $f($n, 2, 3); $alias++; return $n;', 7],
	'container' => ['$items vector<function<int&(int&)>> = [fn&(&$n) => $n]; $n = 4; $alias =& $items[0]($n); $alias++; return $n;', 5],
	'hash' => ['$items hash<function<int&(int&)>> = {select: fn&(&$n) => $n}; $n = 4; $alias =& $items["select"]($n); $alias++; return $n;', 5],
	'vector_origin' => ['$f function<vector<int>&(vector<int>&)> = fn&(&$n) => $n; $n vector<int> = [1]; $alias =& $f($n); $alias[] = 2; return $n[1];', 2],
	'unique_origin' => ['struct Item { public int $n = 4; } $f function<unique<Item>&(unique<Item>&)> = fn&(&$n) => $n; $n unique<Item> = new Item(); $alias =& $f($n); return isset($alias) ? 4 : 1;', 4],
	'value_recursion' => ['class Calls { public static function<int(int)> $f; } function recur(int $n): int { return $n > 0 ? (Calls::$f)($n - 1) : 2; } Calls::$f = fn(int $n): int => recur($n); return recur(2);', 2],
];
foreach (['class', 'struct'] as $kind) {
	$cases[$kind] = [$kind . ' C { public function<int&(int&)> $f; public static function<int&(int&)> $g; } $c = new C(); $c->f = fn&(&$n) => $n; C::$g = fn&(&$n) => $n; $n = 3; $a =& ($c->f)($n); $b =& (C::$g)($n); $a++; $b++; return $n;', 5];
}
$executions = [];
try {
	foreach ($cases as $name => [$source, $exit]) {
		Compiler_Lifecycle::reset(); file_put_contents($path, $source);
		$compiler = new Compiler(); $compiler->init([$directory]);
		try { $compiler->exec_cpp(); } catch (\RuntimeException $error) { throw new \RuntimeException($name . ': ' . $error->getMessage()); }
		$text = CPP_Probe::text(Model::$cpp_files); $compiler->exec_cpp();
		if (CPP_Probe::text(Model::$cpp_files) !== $text) { throw new \LogicException('Reference callable no-op changed output'); }
		file_put_contents($output . '/' . $name . '.cpp', $text);
		$executions[] = ['path' => $output . '/' . $name . '.cpp', 'exit_code' => $exit];
	}
	foreach ([
		'$f function<int&()>; return 0;',
		'$f function<int&(int&, int&)>; return 0;',
		'$f function<int&(const int&)>; return 0;',
		'$f function<float&(int&)>; return 0;',
		'$f function<int&(int&)> = fn(&$n) => $n; return 0;',
		'$f function<int(int&)> = fn&(&$n) => $n; return 0;',
		'$f function<int&(int&)> = function &(&$n) { $local = 1; return $local; }; return 0;',
		'$f function<int&(int&)> = fn&(&$n) => $n; $f(1); return 0;',
		'function &bad(int &$n): int { $f function<int&(int&)> = fn&(&$v) => $v; $local = 1; return $f($local); } return 0;',
		'$n = 1; $f function<int&(int&)> = function &(int &$v) use (&$n): int { return $v; }; return 0;',
		'$n = 1; $f function<int&(int&)> = function &(int &$v) use ($n): int { return $n; }; return 0;',
		'struct Item {} $f function<unique<Item>&(unique<Item>&)> = fn&(&$n) => $n; $n unique<Item> = new Item(); $copy = $f($n); return 0;',
		'class Calls { public static function<int&(int&)> $f; } function &recur(int &$n): int { return (Calls::$f)($n); } Calls::$f = fn&(int &$n): int => recur($n); return 0;',
		'class Calls { public static function<void()> $f; } function &recur(int &$n): int { (Calls::$f)(); return $n; } Calls::$f = function (): void { $n = 1; recur($n); }; return 0;',
		// Conservative policy: even an unassigned compatible closure is a possible target.
		'class Calls { public static function<int&(int&)> $f; } function &recur(int &$n): int { return (Calls::$f)($n); } Calls::$f = fn&(int &$n): int => $n; $unused = fn&(int &$n): int => recur($n); return 0;',
	] as $source) {
		Compiler_Lifecycle::reset(); file_put_contents($path, $source);
		$compiler = new Compiler(); $compiler->init([$directory]); $compiler->sync([]); $rejected = false;
		try { $compiler->prepare(); } catch (\RuntimeException $error) { $rejected = true; }
		if (!$rejected) { throw new \LogicException('Invalid reference callable accepted: ' . $source); }
	}
	// Signature-compatible erased edges are rebuilt after body-only edits too.
	Compiler_Lifecycle::reset();
	$cycle_source = 'class Calls { public static function<int&(int&)> $f; } function &recur(int &$n): int { return (Calls::$f)($n); } Calls::$f = fn&(int &$n): int => $n; $n = 3; $a =& recur($n); $a++; return $n;';
	file_put_contents($path, $cycle_source);
	$compiler = new Compiler(); $compiler->init([$directory]); $compiler->exec_cpp();
	$acyclic = CPP_Probe::text(Model::$cpp_files);
	file_put_contents($path, str_replace('=> $n;', '=> recur($n);', $cycle_source));
	for ($attempt = 0; $attempt < 2; $attempt++) {
		$diagnostic = '';
		try { $compiler->update_cpp([$path]); } catch (\RuntimeException $error) { $diagnostic = $error->getMessage(); }
		if (!str_contains($diagnostic, 'recursive reference-return cycles')) { throw new \LogicException('Expected erased-cycle rejection: ' . $diagnostic); }
	}
	file_put_contents($path, $cycle_source); $compiler->update_cpp([$path]);
	if (CPP_Probe::text(Model::$cpp_files) !== $acyclic) { throw new \LogicException('Erased-cycle repair differs'); }
	Compiler_Lifecycle::reset(); $compiler = new Compiler(); $compiler->init([$directory]); $compiler->exec_cpp();
	if (CPP_Probe::text(Model::$cpp_files) !== $acyclic) { throw new \LogicException('Fresh erased-cycle output differs'); }
	file_put_contents($output . '/cycle_recovery.cpp', $acyclic); $executions[] = ['path' => $output . '/cycle_recovery.cpp', 'exit_code' => 4];
	Compiler_Lifecycle::reset();
	$provider = $directory . '/api.phs';
	$api = 'function make(): function<int&(int&)> { return fn&(&$n) => $n; }';
	file_put_contents($provider, $api); file_put_contents($path, '$n = 1; $f = make(); $a =& $f($n); $a += 4; return $n;');
	$compiler = new Compiler(); $compiler->init([$directory]); $compiler->select_entry('main', 'main.phs'); $compiler->exec_cpp();
	$before = CPP_Probe::text(Model::$cpp_files);
	file_put_contents($provider, str_replace('int&(', 'int(', $api)); $rejected = false;
	try { $compiler->update_cpp([$provider]); } catch (\RuntimeException $error) { $rejected = true; }
	if (!$rejected) { throw new \LogicException('Return marker edit accepted'); }
	file_put_contents($provider, $api); $compiler->update_cpp([$provider]);
	if (CPP_Probe::text(Model::$cpp_files) !== $before) { throw new \LogicException('Return marker recovery differs'); }
	Compiler_Lifecycle::reset(); $compiler = new Compiler(); $compiler->init([$directory]); $compiler->select_entry('main', 'main.phs'); $compiler->exec_cpp();
	if (CPP_Probe::text(Model::$cpp_files) !== $before) { throw new \LogicException('Fresh reference callable output differs'); }
	file_put_contents($output . '/recovery.cpp', $before); $executions[] = ['path' => $output . '/recovery.cpp', 'exit_code' => 5];
	unlink($provider);
	$registry = Model::$type_catalog->registry(); $integer = Language_Types::integer(Model::$language_scope);
	$input = new callable_parameter(); $input->type = $integer; $input->mode = passing_mode::reference;
	$value = $registry->define_callable($integer, [$input]);
	$reference = $registry->define_callable($integer, [$input], new reference_return_contract(0));
	if (($value === $reference) || ($registry->define_callable($integer, [$input], new reference_return_contract(0)) !== $reference)) {
		throw new \LogicException('Reference return interning identity is not exact');
	}
	file_put_contents($output . '/executions.json', json_encode($executions));
	echo "Callable reference returns: PASS\n";
}
finally { Compiler_Lifecycle::reset(); unlink($path); rmdir($directory); }
