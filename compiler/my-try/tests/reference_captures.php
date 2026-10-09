<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

function capture_check(bool $condition, string $message): void
{
	if (!$condition) { throw new \LogicException($message); }
}

$output = $argv[1];
$directory = sys_get_temp_dir() . '/reference-captures-' . bin2hex(random_bytes(6)); mkdir($directory);
$path = $directory . '/main.phs'; $executions = [];
$cases = [
	'borrow' => ['$n = 1; $f = function () use (&$n): void { $n++; }; $n = 4; $f(); return $n;', 5],
	'copy' => ['$n = 1; $f = function () use (&$n): void { $n++; }; $g = $f; $f(); $g(); return $n;', 3],
	'inner_copy' => ['$n = 1; $f = function () use (&$n): void { $n++; }; if (true) { $g = $f; $g(); } return $n;', 2],
	'alias' => ['$n = 1; $a =& $n; $f = function () use (&$a): void { $a = 7; }; $f(); return $n;', 7],
	'parameter' => ['function calc(int &$n): void { $f = function () use (&$n): void { $n += 4; }; $f(); } $n = 1; calc($n); return $n;', 5],
	'value_parameter' => ['function calc(int $n): int { $f = function () use (&$n): void { $n += 4; }; $f(); return $n; } return calc(1);', 5],
	'readonly' => ['function calc(const int &$n, int &$other): int { $f = function () use (&$n): int { return $n; }; $other = 8; return $f(); } $n = 1; return calc($n, $n);', 8],
	'modes' => ['$n = 1; $v = 3; $f = function () use (&$n, $v): void { $n += $v; }; $v = 9; $f(); return $n;', 4],
	'vector' => ['$v vector<int> = [1]; $f = function () use (&$v): void { $v[] = 7; }; $f(); return $v[1];', 7],
	'transitive_copy' => ['$n = 1; $f = function () use (&$n): void { $n++; }; $g = function () use ($f): void { $copy = $f; $copy(); }; $outer_copy = $g; $outer_copy(); return $n;', 2],
	'transitive_arrow' => ['$n = 1; $f = function () use (&$n): int { return ++$n; }; $g = fn(): int => $f(); return $g();', 2],
	'snapshot_arrow' => ['$n = 1; $f = function () use (&$n): int { $g = fn(): int => $n; $n = 9; return $g(); }; return $f();', 1],
	'unique' => ['class Box { public int $n = 6; } $b unique<Box> = new Box(); $f = function () use (&$b): int { return $b->n; }; $g = $f; return $g();', 6],
	'unique_move' => ['class Box { public int $n = 6; } $b unique<Box> = new Box(); $f = function () use (&$b): int { $owned unique<Box> = move($b); return $owned->n; }; $n = $f(); return isset($b) ? 99 : $n;', 6],
	'clean' => ['$n = 5; $f = function () use (&$n): void { clean($n); }; $f(); return $n;', 0],
	'forward' => ['function change(int &$n): void { $n = 7; } $n = 1; $f = function () use (&$n): void { $a =& $n; change($a); }; $f(); return $n;', 7],
	'for' => ['$total = 0; for ($i = 0; $i < 3; $i++) { $n = $i; $f = function () use (&$n): int { return ++$n; }; $part = $f(); $total += $part; } return $total;', 6],
	'for_header' => ['$total = 0; for ($i = 0; $i < 3; $i++) { $f = function () use (&$i): int { return $i; }; $part = $f(); $total += $part; } return $total;', 3],
	'foreach' => ['$v vector<int> = [1, 2]; $total = 0; foreach ($v as $n) { $f = function () use (&$n): int { return ++$n; }; $part = $f(); $total += $part; } return $total;', 5],
	'switch' => ['$total = 0; switch (1) { case 1: $n = 4; $f = function () use (&$n): int { return ++$n; }; $total = $f(); break; default: break; } return $total;', 5],
	'while' => ['$n = 0; while ($n < 3) { $f = function () use (&$n): void { $n++; }; $f(); } return $n;', 3],
	'dowhile' => ['$n = 0; do { $f = function () use (&$n): void { $n++; }; $f(); } while ($n < 3); return $n;', 3],
	'invocation_local' => ['$outer = function (int $n): int { $f = function () use (&$n): int { return ++$n; }; $g = $f; return $g(); }; return $outer(3);', 4],
	'borrow_callable' => ['$f = fn(): int => 4; $g = function () use (&$f): int { $copy = $f; return $copy(); }; return $g();', 4],
];
$invalid = [
	'$n = 1; $f function<int()> = function () use (&$n): int { return $n; }; return 0;',
	'$n = 1; $f = function () use (&$n): int { return $n; }; $g function<int()> = fn(): int => $f(); return 0;',
	'function apply(function<int()> $f): int { return $f(); } $n = 1; $f = function () use (&$n): int { return $n; }; return apply($f);',
	'$n = 1; $f = function () use ($n): int { $g = function () use (&$n): int { return $n; }; return $g(); }; return 0;',
	'$n = 1; $f = function () use (&$n): int { $g = function () use (&$n): int { return $n; }; return $g(); }; return 0;',
	'function calc(const int &$n): void { $f = function () use (&$n): void { $n++; }; } return 0;',
	'function calc(const vector<int> &$v): void { $f = function () use (&$v): void { $v[] = 7; }; } return 0;',
	'class Box { public int $n = 1; } function calc(const Box &$b): void { $f = function () use (&$b): void { $b->n = 7; }; } return 0;',
	'struct Box { public int $n = 1; } $b = new Box(); $a =& $b->n; $f = function () use (&$a): int { return $a; }; return 0;',
	'$n = 1; $f = function &(int &$v) use (&$n): int { return $n; }; return 0;',
	'$n = 1; $f = function () use (&$n, $n): int { return $n; }; return 0;',
	'$f = function () use (&$missing): void { return; }; return 0;',
	'class Box {} $b = new Box(); $f = function () use (&$b): void { unset($b); }; return 0;',
];
try {
	foreach ($cases as $name => [$source, $exit]) {
		Compiler_Lifecycle::reset(); file_put_contents($path, $source);
		$compiler = new Compiler(); $compiler->init([$directory]); $compiler->exec_cpp();
		$text = CPP_Probe::text(Model::$cpp_files);
		capture_check(!str_contains($text, 'std::function'), 'Borrowing closure was erased');
		file_put_contents($output . '/' . $name . '.cpp', $text);
		$executions[] = ['path' => $output . '/' . $name . '.cpp', 'exit_code' => $exit];
		$compiler->exec_cpp(); capture_check(CPP_Probe::text(Model::$cpp_files) === $text, 'No-op changed C++');
	}
	foreach ($invalid as $source) {
		Compiler_Lifecycle::reset(); file_put_contents($path, $source);
		$compiler = new Compiler(); $compiler->init([$directory]); $compiler->sync([]); $rejected = false;
		try { $compiler->prepare(); } catch (\RuntimeException $error) { $rejected = true; }
		capture_check($rejected, 'Invalid borrow accepted: ' . $source);
	}
	// Ordinary blocks share collected lookup scopes, but must have distinct semantic lifetimes.
	Compiler_Lifecycle::reset();
	file_put_contents($path, 'if (true) { $n = 3; $f = function () use (&$n): int { return $n; }; $g = $f; } $later = 0; $h = function () use (&$later): int { return $later; }; return 0;');
	$compiler = new Compiler(); $compiler->init([$directory]); $compiler->exec_cpp();
	foreach (Model::collected_files()[0]->anonymous_callables as $identity) {
		$capture = $identity->syntax()->require_body()->work()->captures[0];
		if ($identity->syntax()->captures[0]->variable->name === 'n') { $inner_borrow = $capture->borrow; }
		else { $outer_borrow = $capture->borrow; }
	}
	capture_check(weakref_get(weakref_get($inner_borrow->place->storage()->declaration)->scope)
		=== weakref_get(weakref_get($outer_borrow->place->storage()->declaration)->scope), 'Fixture must share a collected scope');
	capture_check(!Storage_Lifetimes::outlives($inner_borrow->lifetime, $outer_borrow->lifetime), 'Block origin escaped into later outer storage');
	capture_check(!Storage_Lifetimes::outlives($outer_borrow->lifetime, $inner_borrow->lifetime), 'Later outer storage incorrectly existed at block entry');
	$source = '$n = 3; $f = function () use ($n): int { return $n; }; $n = 7; return $f();';
	Compiler_Lifecycle::reset(); file_put_contents($path, $source);
	$compiler = new Compiler(); $compiler->init([$directory]); $compiler->exec_cpp();
	$source = str_replace('use ($n)', 'use (&$n)', $source); file_put_contents($path, $source); $compiler->update_cpp([$path]);
	$incremental = CPP_Probe::text(Model::$cpp_files);
	foreach (Model::collected_files()[0]->anonymous_callables as $identity) { $borrow_work = $identity->syntax()->require_body()->work(); }
	$borrow = $borrow_work->captures[0]->borrow;
	capture_check($borrow !== null && $identity->syntax()->captures[0]->mode === passing_mode::reference, 'Borrow model missing');
	$version = $borrow_work->version; $compiler->exec_cpp();
	capture_check($version === $borrow_work->version && $borrow === $borrow_work->captures[0]->borrow, 'No-op replaced lifetime facts');
	Compiler_Lifecycle::reset(); $compiler = new Compiler(); $compiler->init([$directory]); $compiler->exec_cpp();
	capture_check(CPP_Probe::text(Model::$cpp_files) === $incremental, 'Capture-mode edit differs from fresh output');
	file_put_contents($output . '/mode_edit.cpp', $incremental);
	$executions[] = ['path' => $output . '/mode_edit.cpp', 'exit_code' => 7];
	$source = $cases['parameter'][0]; file_put_contents($path, $source); $compiler->update_cpp([$path]);
	$before = CPP_Probe::text(Model::$cpp_files);
	foreach (Model::collected_files()[0]->anonymous_callables as $identity) { $retained = $identity->syntax()->require_body()->work(); }
	$old_lifetime = $retained->captures[0]->borrow->lifetime;
	file_put_contents($path, str_replace('calc(int &', 'calc(const int &', $source)); $rejected = false;
	try { $compiler->update_cpp([$path]); } catch (\RuntimeException $error) { $rejected = true; }
	capture_check($rejected, 'Readonly edit retained mutable borrow');
	file_put_contents($path, $source); $compiler->update_cpp([$path]);
	capture_check(CPP_Probe::text(Model::$cpp_files) === $before, 'Readonly repair differs from fresh output');
	capture_check($retained->captures[0]->borrow->lifetime !== $old_lifetime, 'Rebuild retained obsolete invocation region');
	file_put_contents($path, 'return 0;'); $compiler->update_cpp([$path]);
	capture_check(count($retained->captures) === 0, 'Retirement retained lifetime origins');
	file_put_contents($output . '/executions.json', json_encode($executions));
	echo "Reference capture artifacts: PASS\n";
}
finally { Compiler_Lifecycle::reset(); unlink($path); rmdir($directory); }
