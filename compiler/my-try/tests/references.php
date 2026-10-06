<?php

/* Alias/return source behavior, preparation rejection, retained provenance and recovery. */
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

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
	(new Compiler())->exec_cpp();
	return Model::$cpp_files[0]->text;
}

$cases = [
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
	['$x = 1; $a =& $x; $a =& $x;', 'new local'],
	['$x = 1; $a = 2; $a =& $x;', 'new local'],
	['$x = 1; $a float =& $x;', 'exact storage type'],
	['$a =& 1;', 'stable storage'],
	['$a =& $a;', 'initializer'],
	['$x = 1; { $x int =& $x; }', 'initializer'],
	['$x = 1; if (true) { $a =& $x; }', 'conditional or loop-scoped'],
	['$x = 1; if (false) { $x = 2; } else { $a =& $x; }', 'conditional or loop-scoped'],
	['$x = 1; while (false) { $a =& $x; }', 'conditional or loop-scoped'],
	['$x = 1; for ($a int =& $x; false;) {}', 'conditional or loop-scoped'],
	['$x = 1; switch ($x) { case 1: $a =& $x; }', 'conditional or loop-scoped'],
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
	['function &bad(int &$x): int { if (true) { return $x; } return $x; }', 'one final unconditional'],
	['function &bad(int &$x): int { return $x; $x = 2; }', 'one final unconditional'],
	['function &bad(int &$x): int { return; }', 'whole reference parameter'],
	['function &id(int &$x): int { return $x; } $a =& id(1);', 'stable storage'],
	['function &id(int &$x): int { return $x; } function &bad(int &$x): int { return id($x); }', 'forwarding is deferred'],
	['function &id(int &$x): int { return $x; } function &bad(int &$x): int { $a =& id($x); return $a; }', 'forwarding is deferred'],
];
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
	$valid = 'function &id(int &$x): int { $a =& $x; return $a; } $n = 1; $a =& id($n); $a = 7; return $n;';
	file_put_contents($path, $valid);
	Compiler_Lifecycle::reset();
	$compiler = new Compiler();
	$compiler->init([$directory]);
	$compiler->exec_cpp();
	$original = Model::$cpp_files[0]->text;
	foreach ([str_replace('$a =& $x;', '$local = 2; $a =& $local;', $valid), str_replace('function &id', 'function id', $valid)] as $invalid)
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
		}
		file_put_contents($path, $valid);
		$compiler->update_cpp([$path]);
		references_check(Model::$cpp_files[0]->text === $original, 'Incremental reference recovery changed output');
	}
	Compiler_Lifecycle::reset();
	$clean = new Compiler();
	$clean->init([$directory]);
	$clean->exec_cpp();
	references_check(Model::$cpp_files[0]->text === $original, 'Clean/incremental reference output differs');
}
finally {
	unlink($path);
	rmdir($directory);
}
file_put_contents($output . '/executions.json', json_encode($executions, JSON_PRETTY_PRINT));
echo 'References: ' . count($cases) . ' programs, ' . count($rejections) . " rejections, provenance and incremental recovery passed\n";
