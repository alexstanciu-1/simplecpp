<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

$output = $argv[1];
$directory = sys_get_temp_dir() . '/closure-execution-' . bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory . '/main.phs';
$cases = [
	'reference_return' => ['$f = function &(int &$n): int { return $n; }; $n = 3; $a =& $f($n); $a = 7; return $n;', 7],
	'reference_return_alias_copy' => ['$f = function &(int &$n): int { $a =& $n; return $a; }; $g = $f; $n = 3; $a =& $g($n); $a = 8; return $n;', 8],
	'reference_return_value_copy' => ['$f = fn&(int &$n): int => $n; $n = 3; $copy = $f($n); $copy = 8; return $n;', 3],
	'reference_return_immediate' => ['$n = 3; $a = & (fn&(int &$n): int => $n)($n); $a = 6; return $n;', 6],
	'reference_return_capture' => ['$increment = 4; $f = function &(int &$n) use ($increment): int { $n += $increment; return $n; }; $n = 3; $a =& $f($n); $a++; return $n;', 8],
	'reference_return_forward' => ['function &identity(int &$n): int { return $n; } $f = fn&(int &$n): int => identity($n); $g = fn&(int &$n): int => $f($n); $n = 3; $a =& $g($n); $a = 9; return $n;', 9],
	'reference_return_named_forward' => ['function &select(int &$n): int { $f = fn&(int &$v): int => $v; return $f($n); } $n = 3; $a =& select($n); $a = 8; return $n;', 8],
	'reference_return_branches' => ['$f = function &(int &$n, bool $yes = true): int { if ($yes) { return $n; } $a =& $n; return $a; }; $n = 3; $a =& $f($n, false); $a = 4; return $n;', 4],
	'reference_return_pack' => ['$f = function &(int &$n, int ...$values): int { foreach ($values as $v) { $n += $v; } return $n; }; $n = 1; $a =& $f($n, 2, 3); $a++; return $n;', 7],
	'reference_return_vector' => ['$f = fn&(vector<int> &$v): vector<int> => $v; $v vector<int> = [2]; $a =& $f($v); $a[] = 7; $copy = $f($v); $copy[0] = 9; return $v[0] + $a[1];', 9],
	'reference_return_unused_recursive_closure' => ['function &select(int &$n): int { $unused = fn&(int &$v): int => select($v); return $n; } $n = 3; $a =& select($n); $a = 6; return $n;', 6],
	'closure_default' => ['$f = function (int $n = 4): int { return $n; }; $g = $f; return $g();', 4],
	'closure_default_override' => ['$f = function (int $n = 4): int { return $n; }; return $f(7);', 7],
	'closure_reference' => ['$f = function (int &$n): void { $a =& $n; $a += 3; }; $n = 4; $f($n); return $n;', 7],
	'closure_readonly_reference' => ['$f = function (const int &$read, int &$write): int { $write = 9; return $read; }; $n = 4; return $f($n, $n);', 9],
	'closure_reference_snapshot' => ['$f = function (int $copy, int &$write): int { $write = 9; return $copy; }; $n = 4; return $f($n, $n);', 4],
	'closure_capture_reference_parameter' => ['$f = function (int &$n): int { $g = fn(): int => $n; $n = 9; return $g(); }; $n = 4; return $f($n);', 4],
	'closure_reference_container' => ['$f = function (vector<int> &$v): void { $v[] = 8; }; $v vector<int> = []; $f($v); return $v[0];', 8],
	'closure_variadic' => ['$f = function (int $start = 2, int ...$values): int { foreach ($values as $v) { $start += $v; } return $start; }; return $f(1, 3, 5);', 9],
	'closure_empty_variadic' => ['$f = function (int $start = 2, int ...$values): int { foreach ($values as $v) { $start += $v; } return $start; }; return $f();', 2],
	'closure_ref_pack_order' => ['function bump(int &$n): int { $n++; return $n; } $f = function (int &$n, int ...$v): int { $n = 9; return $v[0] * 10 + $v[1]; }; $n = 0; return $f($n, bump($n), bump($n));', 12],
	'arrow_default' => ['$base = 2; $f = fn(int $n = 4): int => $n + $base; return $f();', 6],
	'arrow_reference' => ['$f = fn(int &$n): int => ++$n; $n = 4; $f($n); return $n;', 5],
	'arrow_readonly_reference' => ['$f = fn(const int &$n): int => $n; $n = 5; return $f($n);', 5],
	'arrow_variadic' => ['$f = fn(int ...$v): int => $v[0] + $v[1] + $v[2]; return $f(2, 3, 4);', 9],
	'arrow_empty_variadic' => ['$f = fn(int $start = 2, int ...$v): int => empty($v) ? $start : 0; return $f();', 2],
	'arrow_basic' => ['$factor = 3; $f = fn(int $x): int => $x * $factor; $factor = 8; return $f(2);', 6],
	'arrow_repeat' => ['$n = 3; $f = fn(): int => $n + $n; return $f();', 6],
	'arrow_callable' => ['$n = 2; $f = fn(): int => $n; $g = fn(): int => $f(); return $g();', 2],
	'arrow_shadow' => ['$n = 99; $f = fn(int $n): int => $n + 1; return $f(4);', 5],
	'arrow_nested' => ['$n = 5; $f = fn(): int => (fn(): int => $n)(); return $f();', 5],
	'arrow_nested_parameter' => ['$n = 99; $f = fn(int $n): int => (fn(): int => $n)( ); return $f(7);', 7],
	'arrow_inner_shadow' => ['$f = fn(): int => (fn(int $n): int => $n)(6); return $f();', 6],
	'arrow_explicit_nested' => ['$n = 4; $f = fn(): int => (function () use ($n): int { return $n; })(); return $f();', 4],
	'arrow_inside_explicit' => ['$n = 4; $f = function () use ($n): int { $g = fn(): int => $n; return $g(); }; return $f();', 4],
	'arrow_inner_local' => ['$f = fn(): int => (function (): int { $n = 8; return (fn(): int => $n)(); })(); return $f();', 8],
	'arrow_precedence' => ['$n = 2; $f = fn(int $x): int => $x > 0 ? $x + $n : 9; return $f(1);', 3],
	'arrow_shared' => ['class Box { public int $n = 3; public function next(): int { $this->n++; return $this->n; } } $b = new Box(); $f = fn(): int => $b->next(); $f(); return $b->n;', 4],
	'arrow_vector' => ['$v vector<int> = [3]; $f = fn(): int => $v[0]; $v[0] = 7; return $f();', 3],
	'typed_arrow' => ['function apply(function<int(int)> $f): int { return $f(4); } $n = 2; return apply(fn(int $x): int => $x + $n);', 6],
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
		'$f = function &(): int { $n = 3; return $n; }; return 0;',
		'$f = fn&(int &$a, int &$b): int => $a; return 0;',
		'$f = fn&(const int &$n): int => $n; return 0;',
		'$f = fn&(int &$n): float => $n; return 0;',
		'$f = function &(int &$n): int { $local = 2; $a =& $local; return $a; }; return 0;',
		'$f = fn&(int &$n, int $value): int => $value; return 0;',
		'$f = fn&(int &$n): int => $n + 1; return 0;',
		'$n = 3; $f = function &(int &$value) use ($n): int { return $n; }; return 0;',
		'$n = 3; $f = fn&(int &$value): int => $n; return 0;',
		'$n = 3; $f = function &(int &$value) use ($n): int { $a =& $n; return $a; }; return 0;',
		'class Box { public int $n = 1; } $b = new Box(); $f = fn&(int &$value): int => $b->n; return 0;',
		'struct Box { public int $n = 1; } $f = fn&(Box &$b): Box => $b->n; return 0;',
		'$f = function &(int &$n, bool $yes): int { if ($yes) { return $n; } }; return 0;',
		'$f = fn&(int &$n): int => $n; $a =& $f(3); return 0;',
		'$f = fn&(int &$n): int => $n; $n = 3.0; $a =& $f($n); return 0;',
		'$f function<int(int)> = fn&(int &$n): int => $n; return 0;',
		'function &escape(int &$n): int { $f = fn&(int &$v): int => $v; $local = 3; return $f($local); } return 0;',
		'$f function<int(int)> = function (int $n = 4): int { return $n; }; return 0;',
		'$f function<int(vector<int>)> = fn(int ...$v): int => 0; return 0;',
		'$f function<int(int)> = fn(int &$n): int => $n; return 0;',
		'$f function<int(int)> = fn(const int &$n): int => $n; return 0;',
		'$f = fn(int &$n): int => $n; return $f(4);',
		'$f = fn(const int &$n): int => $n; return $f(4);',
		'$f = fn(int &$n): int => $n; $n = 1.0; return $f($n);',
		'$f = fn(const int &$n): int => ++$n; return 0;',
		'$f = function (const int &$n): void { $a =& $n; $a = 9; }; return 0;',
		'$f = fn(int ...$v, int $n): int => $n; return 0;',
		'$f = fn(int &...$v): int => 0; return 0;',
		'$f = fn(int $n = 1, int $required): int => $required; return 0;',
		'$f = fn(int &$n = 1): int => $n; return 0;',
		'function noop(): void { return; } $f = fn(): void => noop(); return 0;',
		'function noop(): void { return; } function outer(): void { return noop(); } return 0;',
		'$f = fn(): int => $missing; return 0;',
		'$n = 1; $f = fn(): int => $n++; return 0;',
		'$n = 1; $f = function (): int { return (fn(): int => $n)(); }; return 0;',
		'$n = 1; $f = fn(): int => (function (): int { return $n; })(); return 0;',
		'class Box {} $b unique<Box> = new Box(); $f = fn(): bool => isset($b); return 0;',
		'$f = fn(): int => $this->n; return 0;',
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
		'if (true) { $g = function (): int { return 1; }; } return $g();',
		'$items mixed = []; $items[] = function (): int { return 1; }; return 0;',
		'$items dynamic = []; $items[] = function (): int { return 1; }; return 0;',
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
	$source_place = $capture_identity->syntax()->captures[0]->variable->require_preparation()->place;
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
	foreach (['$f = function () use ($this): void { return; };',
		'$n = 1; $f = fn() use ($n): int => $n;', '$f = fn() => 1;', '$f = fn($n): int => $n;',
		'$a = 1; $f = function () use ($a) { return $a; };',
		'$a = 1; $f function<int()> = function () use ($a) { return $a; };'] as $deferred) {
		file_put_contents($path, $deferred); $rejected = false;
		try { $compiler->update_cpp([$path]); } catch (\RuntimeException $error) { $rejected = true; }
		if (!$rejected) { throw new \LogicException('Deferred capture was accepted'); }
		file_put_contents($path, $capture_changed); $compiler->update_cpp([$path]);
		if (CPP_Probe::text(Model::$cpp_files) !== $capture_output) { throw new \LogicException('Capture failure did not recover'); }
	}
	$arrow_source = 'function calc(int $n): int { $m = 7; $f = fn(): int => (int)$n; return $f(); } return calc(4);';
	Compiler_Lifecycle::reset(); file_put_contents($path, $arrow_source);
	$compiler = new Compiler(); $compiler->init([$directory]); $compiler->exec_cpp();
	foreach (Model::collected_files()[0]->anonymous_callables as $identity) { $arrow_syntax = $identity->syntax(); }
	if ($arrow_syntax->syntax_kind !== closure_syntax_kind::arrow || count($arrow_syntax->captures) !== 0
		|| !($arrow_syntax->require_body()->statements[0] instanceof return_node)
		|| count($arrow_syntax->require_body()->work()->captures) !== 1) {
		throw new \LogicException('Arrow normalization or semantic capture ownership diverged');
	}
	foreach (['calc(int $n)' => 'calc(float $n)', '(int)$n' => '(int)$m'] as $before => $after) {
		$arrow_source = str_replace([$before, 'calc(4)'], [$after, 'calc(4.0)'], $arrow_source);
		file_put_contents($path, $arrow_source); $compiler->update_cpp([$path]);
		$arrow_output = CPP_Probe::text(Model::$cpp_files);
		Compiler_Lifecycle::reset(); $compiler = new Compiler(); $compiler->init([$directory]); $compiler->exec_cpp();
		if (CPP_Probe::text(Model::$cpp_files) !== $arrow_output) { throw new \LogicException('Arrow capture edit disagrees with fresh output'); }
	}
	file_put_contents($output . '/arrow_capture_edit.cpp', $arrow_output);
	$executions[] = ['path' => $output . '/arrow_capture_edit.cpp', 'exit_code' => 7];
	$default_source = $cases['closure_default'][0];
	// Both directions through anonymous callables participate in the existing cycle policy.
	$reference_source = $cases['reference_return_named_forward'][0];
	Compiler_Lifecycle::reset(); file_put_contents($path, $reference_source);
	$compiler = new Compiler(); $compiler->init([$directory]); $compiler->exec_cpp();
	$reference_output = CPP_Probe::text(Model::$cpp_files);
	foreach ([
		'function &select(int &$n): int { $f = fn&(int &$v): int => select($v); return $f($n); } return 0;',
		'function helper(int &$n): int { $f = function &(int &$v): int { helper($v); return $v; }; return $f($n); } return 0;',
	] as $recursive_source) {
		file_put_contents($path, $recursive_source); $rejected = false;
		try { $compiler->update_cpp([$path]); }
		catch (\RuntimeException $error) {
			if (!str_contains($error->getMessage(), 'recursive reference-return cycles')) { throw $error; }
			$rejected = true;
		}
		if (!$rejected) { throw new \LogicException('Anonymous reference-return cycle was accepted'); }
		file_put_contents($path, $reference_source); $compiler->update_cpp([$path]);
		if (CPP_Probe::text(Model::$cpp_files) !== $reference_output) { throw new \LogicException('Reference cycle repair left stale output'); }
	}
	// Removing the return-reference marker must invalidate aliases, then recover.
	file_put_contents($path, str_replace('fn&', 'fn', $reference_source)); $rejected = false;
	try { $compiler->update_cpp([$path]); } catch (\RuntimeException $error) { $rejected = true; }
	if (!$rejected) { throw new \LogicException('Reference-return edit left a stale place'); }
	file_put_contents($path, $reference_source); $compiler->update_cpp([$path]);
	if (CPP_Probe::text(Model::$cpp_files) !== $reference_output) { throw new \LogicException('Reference-return repair disagrees with fresh output'); }
	Compiler_Lifecycle::reset(); file_put_contents($path, $default_source);
	$compiler = new Compiler(); $compiler->init([$directory]); $compiler->exec_cpp();
	file_put_contents($path, str_replace('$n = 4', '$n = 6', $default_source)); $compiler->update_cpp([$path]);
	$default_output = CPP_Probe::text(Model::$cpp_files);
	Compiler_Lifecycle::reset(); $compiler = new Compiler(); $compiler->init([$directory]); $compiler->exec_cpp();
	if (CPP_Probe::text(Model::$cpp_files) !== $default_output) { throw new \LogicException('Closure default edit disagrees with fresh output'); }
	file_put_contents($output . '/closure_default_edit.cpp', $default_output);
	$executions[] = ['path' => $output . '/closure_default_edit.cpp', 'exit_code' => 6];
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
