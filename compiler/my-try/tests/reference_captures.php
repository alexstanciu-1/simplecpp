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
	'receiver_class' => ['class Box { private int $n = 1; public function run(): int { $f = function () use ($this): int { return ++$this->n; }; $g = $f; $g(); return $f(); } } $b = new Box(); return $b->run();', 3],
	'receiver_struct' => ['struct Box { private int $n = 1; public function run(): int { $f = fn(): int => ++$this->n; return $f(); } } $b = new Box(); return $b->run();', 2],
	'receiver_nested' => ['class Box { private function value(): int { return 7; } public function run(): int { $f = function () use ($this): int { $g = fn(): int => $this->value(); return $g(); }; return $f(); } } $b = new Box(); return $b->run();', 7],
	'receiver_transitive' => ['class Box { public int $n = 4; public function run(): int { $f = fn(): int => $this->n; $g = fn(): int => $f(); return $g(); } } $b = new Box(); return $b->run();', 4],
	'receiver_protected' => ['class Base { protected int $n = 6; } class Box extends Base { public function run(): int { $f = fn(): int => $this->n; return $f(); } } $b = new Box(); return $b->run();', 6],
	'receiver_trait' => ['trait Run { public function run(): int { $f = fn(): int => $this->n; return $f(); } } class Box { use Run; private int $n = 8; } $b = new Box(); return $b->run();', 8],
	'receiver_self' => ['class Box { private const N = 9; public function run(): int { $f = function () use ($this): int { return self::N; }; return $f(); } } $b = new Box(); return $b->run();', 9],
	'receiver_nested_explicit' => ['class Box { public int $n = 5; public function run(): int { $f = fn(): int => (function () use ($this): int { return $this->n; })(); return $f(); } } $b = new Box(); return $b->run();', 5],
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
	'owned_nested' => ['$n = 1; $f = function () use ($n): int { $g = function () use (&$n): int { return $n; }; $copy = $g; return $copy(); }; $n = 9; return $f();', 1],
	'reborrow_nested' => ['$n = 1; $f = function () use (&$n): int { $g = function () use (&$n): int { return ++$n; }; return $g(); }; $copy = $f; $copy(); return $f();', 3],
	'reborrow_three' => ['$n = 1; $f = function () use (&$n): int { $g = function () use (&$n): int { $h = function () use (&$n): int { return ++$n; }; return $h(); }; return $g(); }; return $f();', 2],
	'owned_alias' => ['$n = 4; $f = function () use ($n): int { $a =& $n; $g = function () use (&$a): int { return $a; }; return $g(); }; return $f();', 4],
	'field_alias' => ['struct Box { public int $n = 1; } $b = new Box(); $a =& $b->n; $f = function () use (&$a): int { return ++$a; }; $g = $f; $g(); return $f();', 3],
	'nested_field' => ['struct Cell { public int $n = 1; } struct Box { public Cell $cell; } $b = new Box(); $a =& $b->cell->n; $f = function () use (&$a): void { $a = 7; }; $f(); return $b->cell->n;', 7],
	'captured_field' => ['struct Box { public int $n = 4; } $b = new Box(); $f = function () use ($b): int { $a =& $b->n; $g = function () use (&$a): int { return $a; }; return $g(); }; $b->n = 9; return $f();', 4],
	'borrowed_field' => ['struct Box { public int $n = 4; } $b = new Box(); $f = function () use (&$b): int { $a =& $b->n; $g = function () use (&$a): int { return ++$a; }; return $g(); }; $f(); return $b->n;', 5],
	'parameter_field' => ['struct Box { public int $n = 4; } function run(Box &$b): void { $a =& $b->n; $f = function () use (&$a): void { $a++; }; $f(); } $b = new Box(); run($b); return $b->n;', 5],
	'readonly_field' => ['struct Box { public int $n = 4; } function run(const Box &$b): int { $a =& $b->n; $f = function () use (&$a): int { return $a; }; return $f(); } $b = new Box(); return run($b);', 4],
];
$invalid = [
	'class Box { public function run(): void { $f = function () use (&$this): void {}; } } return 0;',
	'class Box { public function run(): void { $f function<int()> = fn(): int => $this->n; } public int $n = 1; } return 0;',
	'class Box { public static function run(): void { $f = function () use ($this): void {}; } } Box::run(); return 0;',
	'class Box { public function run(): void { $f = function (): int { return $this->n; }; } public int $n = 1; } return 0;',
	'class Box { public function run(): void { $copy = $this; } } return 0;',
	'class Box { public int $n = 1; public function run(): void { $f = fn(): int => $this->n; $g function<int()> = fn(): int => $f(); } } return 0;',
	'class Box { public int $n = 1; public function run(): void { $f = function (): int { $g = fn(): int => $this->n; return $g(); }; } } return 0;',
	'$n = 1; $f function<int()> = function () use (&$n): int { return $n; }; return 0;',
	'$n = 1; $f = function () use (&$n): int { return $n; }; $g function<int()> = fn(): int => $f(); return 0;',
	'function apply(function<int()> $f): int { return $f(); } $n = 1; $f = function () use (&$n): int { return $n; }; return apply($f);',
	'$n = 1; $f = function () use ($n): int { $g = function () use (&$n): int { return ++$n; }; return $g(); }; return 0;',
	'$n = 1; $f = function () use ($n): function<int()> { return function () use (&$n): int { return $n; }; }; return 0;',
	'function calc(const int &$n): void { $f = function () use (&$n): void { $n++; }; } return 0;',
	'function calc(const vector<int> &$v): void { $f = function () use (&$v): void { $v[] = 7; }; } return 0;',
	'class Box { public int $n = 1; } function calc(const Box &$b): void { $f = function () use (&$b): void { $b->n = 7; }; } return 0;',
	'class Box { public int $n = 1; } $b = new Box(); $a =& $b->n; $f = function () use (&$a): int { return $a; }; return 0;',
	'struct Box { public int $n = 1; } $b = new Box(); $a =& $b->n; $f function<int()> = function () use (&$a): int { return $a; }; return 0;',
	'struct Box { public int $n = 1; } $b = new Box(); $f = function () use ($b): void { $a =& $b->n; $g = function () use (&$a): void { $a++; }; $g(); }; return 0;',
	'$v vector<int> = [1]; $a =& $v[0]; $f = function () use (&$a): int { return $a; }; return 0;',
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
	// Owned fields have invocation-local origins; reborrowing preserves the original lifetime.
	foreach (['owned_nested', 'reborrow_nested'] as $name) {
		Compiler_Lifecycle::reset(); file_put_contents($path, $cases[$name][0]);
		$compiler = new Compiler(); $compiler->init([$directory]); $compiler->exec_cpp();
		$outer_capture = null; $inner_capture = null;
		foreach (Model::collected_files()[0]->anonymous_callables as $identity) {
			$candidate = $identity->syntax()->require_body()->work()->captures[0];
			if ($outer_capture === null) { $outer_capture = $candidate; }
			else { $inner_capture = $candidate; }
		}
		capture_check($inner_capture->borrow->place->root_storage() === $outer_capture, 'Nested borrow lost captured storage provenance');
		if ($name === 'reborrow_nested') {
			capture_check($inner_capture->borrow->lifetime === $outer_capture->borrow->lifetime, 'Reborrow shortened or extended original lifetime');
		}
		else {
			capture_check($outer_capture->borrow === null, 'Owned capture became a borrow');
			capture_check($inner_capture->access === storage_access::read_only, 'Owned capture borrow became writable');
			$unrelated = new storage_lifetime(new storage_lifetime_region(null, 0), 0);
			capture_check(!Storage_Lifetimes::outlives($inner_capture->borrow->lifetime, $unrelated), 'Captured field escaped invocation');
		}
	}
	// Change the outer capture's ownership while retaining a nested reference capture.
	$nested_source = $cases['reborrow_nested'][0];
	Compiler_Lifecycle::reset(); file_put_contents($path, $nested_source);
	$compiler = new Compiler(); $compiler->init([$directory]); $compiler->exec_cpp();
	$nested_before = CPP_Probe::text(Model::$cpp_files);
	file_put_contents($path, preg_replace('/use \(&\$n\)/', 'use ($n)', $nested_source, 1)); $rejected = false;
	try { $compiler->update_cpp([$path]); } catch (\RuntimeException $error) { $rejected = true; }
	capture_check($rejected, 'Owned capture edit retained writable nested borrow');
	file_put_contents($path, $nested_source); $compiler->update_cpp([$path]);
	capture_check(CPP_Probe::text(Model::$cpp_files) === $nested_before, 'Nested capture repair changed output');
	Compiler_Lifecycle::reset(); $compiler = new Compiler(); $compiler->init([$directory]); $compiler->exec_cpp();
	capture_check(CPP_Probe::text(Model::$cpp_files) === $nested_before, 'Nested capture repair differs from fresh');
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
	$source = $cases['receiver_class'][0]; file_put_contents($path, $source); $compiler->update_cpp([$path]);
	foreach (Model::collected_files()[0]->anonymous_callables as $identity) { $receiver_work = $identity->syntax()->require_body()->work(); }
	$receiver_capture = $receiver_work->captures[0];
	capture_check($receiver_capture->receiver() !== null, 'Receiver capture lost its storage role');
	$receiver_lifetime = $receiver_capture->borrow->lifetime;
	$source = str_replace('run(): int', 'run(int $unused = 0): int', $source);
	file_put_contents($path, $source); $compiler->update_cpp([$path]);
	$receiver_text = CPP_Probe::text(Model::$cpp_files);
	capture_check($receiver_work->captures[0]->borrow->lifetime !== $receiver_lifetime, 'Receiver signature edit retained old invocation lifetime');
	file_put_contents($path, str_replace('use ($this)', 'use (&$this)', $source)); $rejected = false;
	try { $compiler->update_cpp([$path]); } catch (\RuntimeException $error) { $rejected = true; }
	capture_check($rejected, 'Invalid receiver mode accepted after incremental edit');
	file_put_contents($path, $source); $compiler->update_cpp([$path]);
	capture_check(CPP_Probe::text(Model::$cpp_files) === $receiver_text, 'Receiver mode recovery changed output');
	Compiler_Lifecycle::reset(); $compiler = new Compiler(); $compiler->init([$directory]); $compiler->exec_cpp();
	capture_check(CPP_Probe::text(Model::$cpp_files) === $receiver_text, 'Receiver incremental/fresh output differs');
	file_put_contents($output . '/receiver_edit.cpp', $receiver_text);
	$executions[] = ['path' => $output . '/receiver_edit.cpp', 'exit_code' => 3];
	file_put_contents($output . '/executions.json', json_encode($executions));
	echo "Reference capture artifacts: PASS\n";
}
finally { Compiler_Lifecycle::reset(); unlink($path); rmdir($directory); }
