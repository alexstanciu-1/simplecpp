<?php

/* Alias/return source behavior, preparation rejection, retained provenance and recovery. */
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

function references_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

/** Fresh source-to-C++ proof, with no executable output published on rejection. */
function references_source(string $source): string
{
	Compiler_Lifecycle::reset();
	$input_module = new module('/references', '/references', '/references');
	$input = new file();
	$input->path = 'main.phs';
	$input->content = $source;
	Source_Registry::add($input_module, $input);
	Model::$modules->add($input_module->name, $input_module);
	(new Compiler(new cpp_lowering_options(false)))->exec_cpp();
	return CPP_Probe::text(Model::$cpp_files);
}

$cases = [
	'branch_returns' => 'function &adjust(int &$x, bool $inc): int { if ($inc) { $a =& $x; $a++; return $a; } return $x; } $n = 1; $a =& adjust($n, true); $a += 2; $b =& adjust($n, false); $b++; if ($n !== 5) { return 1; } return 0;',
	'exhaustive_returns' => 'function &pick(int &$x, int $kind): int { if ($kind === 0) { return $x; } elseif ($kind === 1) { $a =& $x; return $a; } else { return $x; } } $n = 0; for ($i = 0; $i < 3; $i++) { $a =& pick($n, $i); $a++; } if ($n !== 3) { return 1; } return 0;',
	'loop_returns' => 'function &find(int &$x, bool $run): int { while ($run) { $a =& $x; return $a; } return $x; } function &once(int &$x): int { do { return $x; } while (false); } $n = 1; $a =& find($n, true); $a++; $b =& find($n, false); $b++; $c =& once($n); $c++; if ($n !== 4) { return 1; } return 0;',
	'switch_returns' => 'function &select(int &$x, int $kind): int { switch ($kind) { case 0: case 1: $a =& $x; return $a; default: return $x; } } $n = 0; for ($i = 0; $i < 3; $i++) { $a =& select($n, $i); $a++; } if ($n !== 3) { return 1; } return 0;',
	'branch_forward_returns' => 'function &id(int &$x): int { return $x; } function &forward(int &$x, bool $first): int { if ($first) { $a =& id($x); return $a; } return id($x); } $n = 0; $a =& forward($n, true); $a++; $b =& forward($n, false); $b++; if ($n !== 2) { return 1; } return 0;',
	'return_before_dead_code' => 'function &id(int &$x): int { return $x; $x = 99; } $n = 1; $a =& id($n); $a++; if ($n !== 2) { return 1; } return 0;',
	'branch_alias' => '$x = 1; $y = 10; if (true) { $a int =& $x; $b =& $a; $b += 2; } elseif (false) { $a =& $y; $a = 99; } else { $a =& $y; $a = 100; } if (false) { $a =& $x; $a = 99; } elseif (true) { $a =& $y; $a += 3; } else { $a =& $x; $a = 100; } if (false) {} else { $local = 4; $a =& $local; $a++; $y += $a; } if ($x !== 3 || $y !== 18) { return 1; } return 0;',
	'loop_alias' => '$n = 0; $i = 0; while ($i < 4) { $a =& $n; $i++; if ($i === 2) { continue; } $a += $i; if ($i === 3) { break; } } if ($n !== 4) { return 1; } do { $a =& $n; $a++; } while ($n < 6); for ($j = 0; $j < 3; $j++) { $a int =& $n; $local = $j; $b =& $local; $b++; $a += $b; } if ($n !== 12) { return 2; } return 0;',
	'foreach_local_alias' => '$items vector<int> = [1, 2, 3]; $sum = 0; foreach ($items as $item) { $a =& $item; $a += 10; $total =& $sum; $total += $a; } $first = $items[0]; if ($sum !== 36 || $first !== 1) { return 1; } return 0;',
	'switch_group_alias' => '$x = 1; $y = 10; switch (1) { case 1: $a =& $x; $a++; case 2: $a =& $y; $a += 3; break; default: $a =& $x; $a = 99; } if ($x !== 2 || $y !== 13) { return 1; } return 0;',
	'branch_const_alias' => 'struct P { int $n; } function read(const P &$p): int { $answer = 0; if (true) { $a =& $p->n; $b int =& $a; $answer = $b; } return $answer; } $p P; $p->n = 7; $r = read($p); if ($r !== 7) { return 1; } return 0;',
	'branch_shadow_alias' => '$x = 1; $a = 2; if (true) { $a int =& $x; $a = 3; } if ($a !== 2 || $x !== 3) { return 1; } return 0;',
	'branch_call_alias' => 'function &id(int &$x): int { $x += 1; return $x; } $x = 1; if (false) { $a =& id($x); $a = 99; } else { $a =& id($x); $a += 3; } if ($x !== 5) { return 1; } return 0;',
	'const_noncopyable_record' => 'struct P { unique<int> $child; } function absent(const P &$p): bool { $a =& $p->child; return !isset($a); } $p P; if (!absent($p)) { return 1; } return 0;',
	'const_scalar' => 'function read(const int &$x): int { $a =& $x; $b int =& $a; $copy = $b; $copy += 1; return $copy; } $n = 4; $r = read($n); if ($r !== 5) { return 1; } if ($n !== 4) { return 2; } return 0;',
	'const_forward' => 'function read(const int &$x): int { return $x; } function forward(const int &$x): int { $a =& $x; return read($a); } struct P { int $x; } function field(const P &$p): int { $a =& $p->x; return forward($a); } $p P; $p->x = 7; $a = field($p); $b = read($p->x); if ($a !== 7 || $b !== 7) { return 1; } return 0;',
	'const_containers' => 'function first(const vector<int> &$v): int { $a =& $v; $copy = $a; $copy[0] = 9; return $a[0]; } function keyed(const hash<int> &$h): int { return $h["n"]; } function boxed(const mixed &$m): int { if (!isset($m["n"])) { return 99; } return $m["n"]; } function dynamic_read(const dynamic &$d): int { if (!isset($d["n"])) { return 99; } return $d["n"]; } $v vector<int> = [1]; $h hash<int> = {n: 2}; $m mixed = {n: 3}; $d dynamic = {n: 4}; $a = first($v); $b = keyed($h); $c = boxed($m); $e = dynamic_read($d); if ($a !== 1 || $b !== 2 || $c !== 3 || $e !== 4) { return 1; } return 0;',
	'const_iteration' => 'function sum(const vector<int> &$v): int { $total = 0; foreach ($v as $n) { $total += $n; } return $total; } $v vector<int> = [1, 2, 3]; $n = sum($v); if ($n !== 6) { return 1; } return 0;',
	'const_live_alias' => 'function read(const int &$view, int &$writer): int { $writer += 1; return $view; } $n = 3; $r = read($n, $n); if ($r !== 4) { return 1; } if ($n !== 4) { return 2; } return 0;',
	'const_ownership' => 'struct P { unique<int> $child; } function present(const unique<P> &$p): bool { $a =& $p; return isset($a); } function observe(const shared<P> &$s): weak<P> { return weak($s); } function acquire(const weak<P> &$w): shared<P> { return lock($w); } $u unique<P> = new P(); if (!present($u)) { return 1; } $s shared<P> = new P(); $w = observe($s); $held = acquire($w); $s = null; if (!$held) { return 2; } $held = null; if (acquire($w)) { return 3; } return 0;',
	'const_wrappers' => 'function read(const ?int &$n, const result<int> &$r, const string &$s): int { $v = 0; if (!take($v, $n)) { return 99; } $rv int = $r; return $v + $rv + (int)$s; } $n ?int = 1; $r result<int> = 2; $s = "3"; $v = read($n, $r, $s); if ($v !== 6) { return 1; } return 0;',
	'forward_chain' => 'function &forward(int &$x): int { $a =& middle($x); return $a; } function &middle(int &$x): int { return identity($x); } function &identity(int &$x): int { return $x; } $n = 1; $a =& forward($n); $a = 7; $copy = forward($n); $a = 8; if ($n !== 8 || $copy !== 7) { return 1; } return 0;',
	'forward_local_use' => 'function &forward(int &$x): int { $local = 1; $a =& identity($local); $a = 9; $x = $local; return identity($x); } function &identity(int &$x): int { return $x; } $n = 1; $a =& forward($n); if ($n !== 9) { return 1; } $a = 7; if ($n !== 7) { return 2; } return 0;',
	'forward_vector' => 'function &forward(vector<int> &$x): vector<int> { return identity($x); } function &identity(vector<int> &$x): vector<int> { return $x; } $v vector<int> = [1]; $a =& forward($v); $a[] = 2; $copy = forward($v); $a[0] = 8; $n = $v[0]; $c = $copy[0]; if ($n !== 8 || $c !== 1) { return 1; } return 0;',
	'value_recursion' => 'function sum(int $n): int { if ($n === 0) { return 0; } $v = sum($n - 1); return $n + $v; } function &identity(int &$x): int { $v = sum(3); $x = $v; return $x; } $n = 0; $a =& identity($n); if ($a !== 6) { return 1; } return 0;',
	'local_chain' => '$x int = 10; $a =& $x; $b int =& $a; $b = 20; $copy = $a; $a++; if ($x !== 21 || $copy !== 20) { return 1; } return 0;',
	'parameter_alias' => 'function change(int &$x): void { $a =& $x; $a += 2; } function copy(int $x): int { $a =& $x; $a = 8; return $x; } $n = 3; change($n); $v = copy($n); if ($n !== 5 || $v !== 8) { return 1; } return 0;',
	'field_alias' => 'struct Inner { int $n; } struct Box { Inner $inner; } $box Box; $a =& $box->inner->n; $a = 7; $n = $box->inner->n; if ($n !== 7) { return 1; } return 0;',
	'lexical_alias' => '$x = 1; { $a =& $x; $a = 2; $x int = 3; $a = 4; if ($x !== 3) { return 1; } } if ($x !== 4) { return 2; } return 0;',
	'containers' => '$v vector<int> = [1]; $a =& $v; $a[] = 2; $copy = $a; $a[0] = 3; $h hash<int> = {n: 1}; $b =& $h; $b["n"] = 4; $m mixed = {n: 1}; $c =& $m; $c["n"] = 5; $v0 = $v[0]; $c0 = $copy[0]; $v1 = $v[1]; $hn = $h["n"]; if ($v0 !== 3 || $c0 !== 1 || $v1 !== 2 || $hn !== 4) { return 1; } $n int = $m["n"]; if ($n !== 5) { return 2; } return 0;',
	'dynamic_binding' => '$d dynamic = {n: 1}; $copy = $d; $a =& $d; $a["n"] = 2; $a = dynamic::{n: 9}; $old int = $copy["n"]; $fresh int = $d["n"]; if ($old !== 2 || $fresh !== 9) { return 1; } return 0;',
	'reference_return' => 'function &id(int &$x): int { return $x; } function &alias_id(int &$x): int { $a =& $x; $b int =& $a; return $b; } $n = 10; $a =& id($n); $a = 20; $copy = id($n); $b =& alias_id($n); $b = 30; if ($n !== 30 || $copy !== 20) { return 1; } return 0;',
	'return_field_argument' => 'function &id(int &$x): int { return $x; } function change(int &$x): void { $x = 8; } struct Box { int $n; } $b Box; $a =& id($b->n); $a = 4; change(id($b->n)); $n = $b->n; if ($a !== 8 || $n !== 8) { return 1; } return 0;',
	'return_containers' => 'function &id(vector<int> &$x): vector<int> { $a =& $x; return $a; } $v vector<int> = [1]; $a =& id($v); $a[] = 2; $copy = id($v); $a[0] = 3; $v0 = $v[0]; $c0 = $copy[0]; $c1 = $copy[1]; if ($v0 !== 3 || $c0 !== 1 || $c1 !== 2) { return 1; } return 0;',
	'argument_order' => 'function step(int &$n): int { $n += 1; return $n; } function &select(int $before, int &$n, int $after): int { if ($before !== 1 || $after !== 2) { $n = 99; } return $n; } $n = 0; $a =& select(step($n), $n, step($n)); if ($a !== 2) { return 1; } $a = 7; if ($n !== 7) { return 2; } return 0;',
	'return_wrappers' => 'function &text(string &$x): string { return $x; } function &maybe(?int &$x): ?int { return $x; } function &object(dynamic &$x): dynamic { return $x; } function &boxed(mixed &$x): mixed { return $x; } $s = "1"; $a =& text($s); $a = "2"; $n ?int = null; $b =& maybe($n); $b = 8; $d dynamic = {n: 1}; $before = $d; $c =& object($d); $c = dynamic::{n: 2}; $m mixed = 1; $e =& boxed($m); $e = 7; $sv int = (int)$s; $nv int = $n; $dv int = $d["n"]; $old int = $before["n"]; $mv int = $m; if ($sv !== 2 || $nv !== 8 || $dv !== 2 || $old !== 1 || $mv !== 7) { return 1; } return 0;',
];
$output = $argv[1] ?? sys_get_temp_dir() . '/scpp_references_' . bin2hex(random_bytes(6));
if (!is_dir($output)) {
	mkdir($output, 0777, true);
}
$executions = [];
foreach ($cases as $name => $source) {
	$cpp = references_source($source);
	$path = $output . '/' . $name . '.cpp';
	file_put_contents($path, $cpp);
	$executions[] = ['path' => $path, 'exit_code' => 0];
}

// Source-visible names remain distinct while alias chains share the exact original place.
references_source('function &id(int &$x): int { $a =& $x; $b int =& $a; return $b; } return 0;');
$function = Model::$global_scope->functions_named('id')[0]->syntax();
$statements = $function->body->statements;
$first = $statements[0]->expression->require_assignment_preparation()->binding;
$second = $statements[1]->require_preparation();
$returned = $statements[2]->require_preparation();
references_check($first->alias_target === $second->alias_target && $second->alias_target === $returned->reference_target,
	'Alias chain lost shared place identity');
references_check($returned->reference_target->storage() === $function->require_preparation()->parameters[0], 'Return lost parameter origin');
references_check($first->declaration !== $second->declaration && $returned->conversion === null, 'Alias declaration identity or no-copy return was lost');

$rejections = [
	['function bad(const int $x): void {}', 'fixed reference'],
	['function bad(int &$x = 1): void {}', 'reference parameter defaults'],
	['function bad(const int &...$x): void {}', 'variadic parameters'],
	['function &bad(const int &$x): int { return $x; }', 'const-reference returns'],
	['function read(const int &$x): int { return $x; } $n = read(1);', 'stable storage'],
	['function read(const float &$x): float { return $x; } $n = 1; $v = read($n);', 'exact parameter type'],
	['function read(const int &$x): int { return $x; } $v vector<int> = [1]; $n = read($v[0]);', 'stable storage'],
	['function bad(const int &$x): void { $x = 2; }', 'read-only'],
	['function bad(const int &$x): void { $a =& $x; $b int =& $a; $b = 2; }', 'read-only'],
	['function bad(const int &$x): void { $x++; }', 'read-only'],
	['function bad(const int &$x): void { $x += 1; }', 'read-only'],
	['function bad(const string &$x): void { $x .= "a"; }', 'read-only'],
	['function bad(const int &$x): void { clean($x); }', 'read-only'],
	['function bad(const ?int &$x): void { unset($x); }', 'read-only'],
	['function bad(const unique<int> &$x): void { $v = move($x); }', 'read-only'],
	['function bad(const int &$x): void { $n ?int = 1; take($x, $n); }', 'read-only'],
	['function bad(const error &$e): void { $r result<int> = 1; $v = 0; take($v, $e, $r); }', 'read-only'],
	['function bad(const int &$x): void { $v vector<int> = [1]; foreach ($v as $x) {} }', 'read-only'],
	['function change(int &$x): void { $x = 1; } function bad(const int &$x): void { change($x); }', 'read-only'],
	['function change(int &$x): void { $x = 1; } function bad(const int &$x): void { $a =& $x; change($a); }', 'read-only'],
	['struct P { int $x; } function bad(const P &$p): void { $p->x = 1; }', 'read-only'],
	['struct P { int $x; } function bad(const P &$p): void { $a =& $p->x; $a++; }', 'read-only'],
	['struct P { int $x; } function change(int &$x): void {} function bad(const P &$p): void { change($p->x); }', 'read-only'],
	['$x = 1; $a =& $x; $a =& $x;', 'new local'],
	['$x = 1; $a = 2; $a =& $x;', 'new local'],
	['$x = 1; $a float =& $x;', 'exact storage type'],
	['$a =& 1;', 'stable storage'],
	['$a =& $a;', 'initializer'],
	['$x = 1; { $x int =& $x; }', 'initializer'],
	['$x = 1; if (true) { $a =& $x; } return $a;', 'established local'],
	['$x = 1; if (true) { $a =& $x; } else { $a = 2; } return $a;', 'established local'],
	['$x = 1; if (true) { $a =& $x; } elseif ($a) {}', 'established local'],
	['$x = 1; while (false) { $a =& $x; } return $a;', 'established local'],
	['$x = 1; for ($a int =& $x; false;) {}', 'loop-header'],
	['$x = 1; for ($i = 0; $i < 1; $a++) { $a =& $x; }', 'established local'],
	['$x = 1; do { $a =& $x; } while ($a);', 'established local'],
	['$x = 1; switch ($x) { case 1: $a =& $x; case 2: $a++; }', 'established local'],
	['$x = 1; $a = 2; if (true) { $a =& $x; }', 'new local'],
	['$x = 1; while (false) { $a =& $x; $a =& $x; }', 'new local'],
	['$x = 1; if (true) { $a =& $later; $later = 2; }', 'established local'],
	['function bad(const int &$x): void { if (true) { $a =& $x; $a++; } }', 'read-only'],
	['$v vector<int> = [1]; foreach ($v as $n) { $a =& $v[0]; }', 'stable storage'],
	['function &bad(int &$x): int { if (true) { $a =& $x; } return $a; }', 'established local'],
	['$x = 1; $y = 2; $z = ($a =& $x);', 'standalone local'],
	['$x = 1; $y = ($a =& $x);', 'standalone local'],
	['$x = 1; $a int = ($b =& $x);', 'standalone local'],
	['$v vector<int> = [1]; $a =& $v[0];', 'stable storage'],
	['$v mixed = {n: 1}; $a =& $v["n"];', 'stable storage'],
	['$v dynamic = {n: 1}; $a =& $v["n"];', 'stable storage'],
	['struct B { int $n; } function make(): B { $b B; return $b; } $a =& make()->n;', 'stable storage'],
	['$x = 1; { $a =& $x; } return $a;', 'established local'],
	['function &bad(): int { $x = 1; return $x; }', 'exactly one reference parameter'],
	['function &bad(int &$x, int &$y): int { return $x; }', 'exactly one reference parameter'],
	['function &bad(int &$x): float { return $x; }', 'exact parameter storage type'],
	['function &bad(int &$x): int { $local = 1; return $local; }', 'cannot escape'],
	['function &bad(int &$x, int $y): int { return $y; }', 'cannot escape'],
	['function &bad(int &$x): int { return 1; }', 'whole reference parameter'],
	['struct B { int $n; } function &bad(B &$x): B { return $x->n; }', 'cannot escape'],
	['function &bad(vector<int> &$x): vector<int> { return $x[0]; }', 'whole reference parameter'],
	['function &bad(int &$x): int { $y = 1; $a =& $y; return $a; }', 'cannot escape'],
	['function &bad(int &$x, bool $c): int { if ($c) { return $x; } }', 'reach the end'],
	['function &bad(int &$x): int {}', 'reach the end'],
	['function &bad(int &$x): int { while (false) { return $x; } }', 'reach the end'],
	['function &bad(int &$x): int { switch ($x) { case 1: return $x; } }', 'reach the end'],
	['function &bad(int &$x): int { if (true) { $local = 1; return $local; } return $x; }', 'cannot escape'],
	['function &bad(int &$x, int $y): int { if (true) { return $y; } return $x; }', 'cannot escape'],
	['function &bad(int &$x): int { if (true) { return 1; } return $x; }', 'whole reference parameter'],
	['function &bad(int &$x): int { if (true) { return; } return $x; }', 'whole reference parameter'],
	['function &bad(int &$x): int { return $x; $local = 1; return $local; }', 'cannot escape'],
	['function &bad(int &$x): int { return; }', 'whole reference parameter'],
	['function &id(int &$x): int { return $x; } $a =& id(1);', 'stable storage'],
	['function &id(int &$x): int { return $x; } function &bad(int &$x): int { $local = 1; return id($local); }', 'cannot escape'],
	['function &id(int &$x): int { return $x; } function &bad(int &$x): int { $local = 1; $a =& id($local); return $a; }', 'cannot escape'],
	['function &id(int &$x): int { return $x; } function &bad(int &$x, int $copy): int { return id($copy); }', 'cannot escape'],
	['function &bad(int &$x): int { return bad($x); }', 'recursive reference-return cycles'],
	['function &a(int &$x): int { return b($x); } function &b(int &$x): int { return a($x); }', 'recursive reference-return cycles'],
	['function &a(int &$x): int { $v = b($x); return $x; } function b(int &$x): int { return a($x); }', 'recursive reference-return cycles'],
	['function &a(int &$x): int { if (false) { $v = a($x); } return $x; }', 'recursive reference-return cycles'],
];
foreach (['vector<int>', 'hash<int>', 'mixed', 'dynamic'] as $family) {
	$rejections[] = ['function bad(const ' . $family . ' &$x): void { $x[0] = 1; }', 'read-only'];
	$rejections[] = ['function bad(const ' . $family . ' &$x): void { $a =& $x; $a[] = 1; }', 'read-only'];
}
foreach (['hash<int>', 'mixed', 'dynamic'] as $family) {
	$rejections[] = ['function bad(const ' . $family . ' &$x): void { unset($x["n"]); }', 'read-only'];
}
$rejections[] = ['function bad(const dynamic &$x): void { $x["a"]["b"] = 1; }', 'read-only'];
foreach ($rejections as [$source, $message])
{
	$failed = false;
	try {
		references_source($source);
	}
	catch (\RuntimeException $error) {
		$failed = str_contains($error->getMessage(), $message);
		if (!$failed) {
			throw new \LogicException('Wrong rejection for ' . $source . ': ' . $error->getMessage());
		}
	}
	references_check($failed && Model::$cpp_files->is_empty(), 'Accepted invalid reference: ' . $source);
}

// Changing only a callee body must recheck its lifetime even if its contract is unchanged.
$directory = sys_get_temp_dir() . '/scpp_reference_incremental_' . bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory . '/main.phs';
try
{
	$valid = 'function &id(int &$x): int { if (true) { $a =& $x; return $a; } return $x; } $n = 1; $a =& id($n); $a = 7; return $n;';
	file_put_contents($path, $valid);
	Compiler_Lifecycle::reset();
	$compiler = new Compiler(new cpp_lowering_options(false));
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	$original = CPP_Probe::text(Model::$cpp_files);
	foreach ([str_replace('$a =& $x;', '$local = 2; $a =& $local;', $valid),
		str_replace('return $x;', '', $valid),
		str_replace('function &id', 'function id', $valid),
		str_replace('$a =& $x;', '$a =& id($x);', $valid)] as $invalid)
	{
		file_put_contents($path, $invalid);
		foreach ([[$path], []] as $changes) {
			$compiler->sync($changes);
			$failed = false;
			try {
				$compiler->prepare();
			}
			catch (\RuntimeException $error) {
				$failed = true;
			}
			references_check($failed && Model::$cpp_files->is_empty(), 'Incremental lifetime/contract change published output');
			$blocked = false;
			try {
				$compiler->cpp();
			}
			catch (\RuntimeException $error) {
				$blocked = true;
			}
			references_check($blocked && Model::$cpp_files->is_empty(), 'Emission bypassed failed reference preparation');
		}
		file_put_contents($path, $valid);
		$compiler->update_cpp([$path]);
		references_check(CPP_Probe::text(Model::$cpp_files) === $original, 'Incremental reference recovery changed output');
	}
	Compiler_Lifecycle::reset();
	$clean = new Compiler(new cpp_lowering_options(false));
	$clean->init([$directory]);
	$clean->exec_cpp();
	references_check(CPP_Probe::text(Model::$cpp_files) === $original, 'Clean/incremental reference output differs');

	$const_source = 'function read(const int &$x): int { return $x; } function forward(const int &$x): int { $a =& $x; return read($a); } $n = 7; return forward($n);';
	file_put_contents($path, $const_source);
	$clean->update_cpp([$path]);
	$const_output = CPP_Probe::text(Model::$cpp_files);
	$forward = Model::$global_scope->functions_named('forward')[0]->syntax();
	$forward_contract = $forward->require_preparation()->parameters[0]->contract;
	$forward_alias = $forward->body->statements[0]->expression->require_assignment_preparation()->binding;
	references_check($forward_contract->mode === passing_mode::reference
		&& $forward_contract->access === storage_access::read_only, 'Const changed passing mode rather than access');
	references_check($forward_alias->alias_target->access() === storage_access::read_only, 'Const alias lost access');
	$const_invalid = str_replace('function read(const int', 'function read(int', $const_source);
	file_put_contents($path, $const_invalid);
	foreach ([[$path], []] as $changes) {
		$failed = false;
		try { $clean->update_cpp($changes); }
		catch (\RuntimeException $error) { $failed = str_contains($error->getMessage(), 'read-only'); }
		references_check($failed && Model::$cpp_files->is_empty(), 'Const-only callee change reused invalid argument facts');
	}
	file_put_contents($path, $const_source);
	$clean->update_cpp([$path]);
	references_check(CPP_Probe::text(Model::$cpp_files) === $const_output, 'Const repair changed output');
	Compiler_Lifecycle::reset_preparation();
	$clean->prepare();
	$clean->cpp();
	references_check(CPP_Probe::text(Model::$cpp_files) === $const_output, 'Const cleanup/reprepare changed output');
	$mutable_source = str_replace('const int', 'int', $const_source);
	file_put_contents($path, $mutable_source);
	$clean->update_cpp([$path]);
	$mutable_output = CPP_Probe::text(Model::$cpp_files);
	references_check($mutable_output !== $const_output, 'Const removal did not refresh output');
	references_check($mutable_output === references_source($mutable_source), 'Const removal differs from fresh output');
	references_check($const_output === references_source($const_source), 'Const output differs from fresh output');

	// Failed body-local bindings must not leave scoped alias facts or header policy behind.
	$block_source = '$n = 1; while ($n < 3) { if (true) { $a =& $n; $a++; } } return $n;';
	file_put_contents($path, $block_source);
	Compiler_Lifecycle::reset();
	$block_compiler = new Compiler(new cpp_lowering_options(false));
	$block_compiler->init([$directory]);
	$block_compiler->exec_cpp();
	$block_output = CPP_Probe::text(Model::$cpp_files);
	foreach ([str_replace('$a =& $n', '$a =& 1', $block_source),
		str_replace('return $n', 'return $a', $block_source)] as $invalid)
	{
		file_put_contents($path, $invalid);
		foreach ([[$path], []] as $changes) {
			$failed = false;
			try { $block_compiler->update_cpp($changes); }
			catch (\RuntimeException $error) { $failed = true; }
			references_check($failed && Model::$cpp_files->is_empty(), 'Invalid block alias published incremental output');
		}
		file_put_contents($path, $block_source);
		$block_compiler->update_cpp([$path]);
		references_check(CPP_Probe::text(Model::$cpp_files) === $block_output, 'Block alias recovery changed output');
	}
	Compiler_Lifecycle::reset_preparation();
	$block_compiler->prepare();
	$block_compiler->cpp();
	references_check(CPP_Probe::text(Model::$cpp_files) === $block_output, 'Block alias cleanup changed output');
	references_check($block_output === references_source($block_source), 'Block alias incremental/fresh output differs');
}
finally {
	unlink($path);
	rmdir($directory);
}
file_put_contents($output . '/executions.json', json_encode($executions, JSON_PRETTY_PRINT));
$programs = ['valid' => [], 'rejected' => []];
foreach ($cases as $name => $source) {
	$programs['valid'][$name] = [$source, 0];
}
foreach ($rejections as [$source, $message]) {
	$programs['rejected'][] = $source;
}
file_put_contents($output . '/programs.json', json_encode($programs, JSON_PRETTY_PRINT));
echo 'References: ' . count($cases) . ' programs, ' . count($rejections) . " rejections, provenance and incremental recovery passed\n";
