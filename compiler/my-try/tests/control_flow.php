<?php

/* Prove structured branches, local identities, completion and incremental recovery. */
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function control_check(bool $condition, string $message): void
{
	if (!$condition) {
		throw new \LogicException($message);
	}
}

function control_program(string $path, string $source): Compiler
{
	file_put_contents($path, $source);
	Compiler_Lifecycle::reset();
	$compiler = new Compiler();
	$compiler->init([dirname($path)]);
	$compiler->sync([]);
	return $compiler;
}

/** Expected semantic failures must withhold output and remain ordinary retryable failures. */
function control_reject(Compiler $compiler, string $diagnostic): void
{
	$matched = false;
	try {
		$compiler->prepare();
	}
	catch (\RuntimeException $error) {
		$matched = str_contains($error->getMessage(), $diagnostic);
	}
	control_check($matched, 'Missing diagnostic: ' . $diagnostic);
	control_check(!Model::$rebuild_required && Model::$prepared_files->is_empty()
		&& Model::$cpp_files->is_empty(), 'Failed branches published output or requested a full rebuild');
}

$directory = sys_get_temp_dir() . '/scpp_control_' . bin2hex(random_bytes(6));
mkdir($directory);
$path = $directory . '/main.phs';
try
{
	$cases = [
		'if_true' => ['$x = 1; if (true) { $x = 7; } return $x;', 7],
		'outer_assignment' => ['$x = 1; if (true) { $x = 2; } return $x;', 2],
		'if_false' => ['$x = 1; if (false) { $x = 7; } return $x;', 1],
		'else' => ['if (false) { return 7; } else { return 3; }', 3],
		'elseif' => ['if (false) { return 1; } elseif (true) { return 2; } else { return 3; }', 2],
		'chain_else' => ['if (false) { return 1; } elseif (false) { return 2; } else { return 3; }', 3],
		'empty' => ['if (false) {} elseif (true) {} else {} return 4;', 4],
		'explicit_cast' => ['if ((bool) 2) { return 5; } return 0;', 5],
		'comparison' => ['$x = 3; if ($x > 2) { return 6; } return 0;', 6],
		'nested' => ['if (true) { if (false) { return 1; } else { return 9; } } return 0;', 9],
		'shadow' => ['$x int = 10; if (true) { $x int = 20; $x = 21; } return $x;', 10],
		'shadow_type' => ['$x int = 10; if (true) { $x bool = false; if ($x) { return 1; } } return $x;', 10],
		'shadow_parameter' => ['function f(int $x): int { { $x int = 20; } return $x; } return f(7);', 7],
		'sibling' => ['if (true) { $x int = 2; } else { $x bool = false; } $x = 9; return $x;', 9],
		'outer_then_shadow' => ['$x int = 1; { $x = 4; $x int = 8; } return $x;', 4],
		'child_outer' => ['$x = 1; { $y = 5; { $y = 7; $x = $y; } } return $x;', 7],
		'full_return' => ['function f(bool $a, bool $b): int { if ($a) { return 1; } elseif ($b) { return 2; } else { return 3; } } return f(false, true);', 2],
		'tail_return' => ['function f(bool $a): int { if ($a) { return 1; } return 8; } return f(false);', 8],
		'early_return' => ['function f(): int { return 6; if (false) { $x = 1; } } return f();', 6],
		'block_return' => ['function f(): int { { return 4; } } return f();', 4],
		'void' => ['function f(bool $a): void { if ($a) { return; } else {} } f(false); return 0;', 0],
		'lazy_conditions' => ['function probe(int &$n, bool $result): bool { $n++; return $result; } $n = 0; if (probe($n, false)) { $n = 90; } elseif (probe($n, true)) { $n += 10; } elseif (probe($n, true)) { $n = 99; } else { $n = 98; } return $n;', 12],
		'lazy_body' => ['$n = 0; if (true) { $n = 7; } else { $n = 1 / 0; } return $n;', 7],
		'logical_condition' => ['$n = 0; if (false && true) { $n = 9; } return $n;', 0],
	];
	$executions = [];
	foreach ($cases as $name => [$source, $expected])
	{
		$compiler = control_program($path, $source);
		$compiler->prepare();
		$compiler->cpp();
		$first = Model::$cpp_files[0]->text;
		$compiler->cpp();
		control_check(Model::$cpp_files[0]->text === $first, 'Repeated emission changed ' . $name);
		if (isset($argv[1])) {
			$output = $argv[1] . '/' . $name . '.cpp';
			file_put_contents($output, $first);
			$executions[] = ['path' => $output, 'exit_code' => $expected];
		}
	}

	foreach ([
		['if (1) {}', 'condition requires canonical bool'],
		['if (false) {} elseif ("x") {}', 'condition requires canonical bool'],
		['if (true) { $x = 1; } return $x;', 'established local declaration'],
		['if (true) { $x = 1; } else { return $x; }', 'established local declaration'],
		['{ $x int = 1; } return $x;', 'established local declaration'],
		['$x int = 1; { $x int = $x + 1; }', 'itself in its initializer'],
		['$x int = $x;', 'itself in its initializer'],
		['{ $x int = 1; $x int = 2; }', 'already declared'],
		['function f(int $x): int { $x int = 1; return $x; }', 'already declared'],
		['function f(bool $a): int { if ($a) { return 1; } }', 'can reach the end'],
		['function f(bool $a): int { if ($a) { return 1; } else {} }', 'can reach the end'],
		['function f(): int { if (true) { return 1; } }', 'can reach the end'],
		['function f(): int { if (true) { return; } else { return 2; } }', 'non-void return requires a value'],
		['function f(): int { return 1; if (false) { return $missing; } }', 'established local declaration'],
		['if (true) {} else { return $missing; }', 'established local declaration'],
		['if (($x = true)) {}', 'established local declaration'],
		['function probe(int &$n): bool { $n++; return true; } $n = 0; if (false && probe($n)) {}', 'order-independent operands'],
	] as [$source, $diagnostic]) {
		$compiler = control_program($path, $source);
		control_reject($compiler, $diagnostic);
	}

	// The bounded grammar requires braces and does not admit declarations in branch bodies.
	foreach (['if (true) return 1;', 'if (true): return 1; endif;',
		'if (true) { function local(): int { return 1; } }', 'if (true) { return 1;'] as $source)
	{
		$rejected = false;
		try {
			control_program($path, $source);
		}
		catch (\RuntimeException $error) {
			$rejected = true;
		}
		control_check($rejected, 'Unsupported or incomplete branch grammar was accepted');
		control_check(Model::$prepared_files->is_empty() && Model::$cpp_files->is_empty(), 'Parse failure published output');
	}

	// Prepared storage identities distinguish outer assignments from typed shadows.
	$compiler = control_program($path, '$x int = 1; if (true) { $x = 2; $x int = 3; $x = 4; } return $x;');
	$compiler->prepare();
	$parsed = Model::syntax_files()[0];
	$statements = $parsed->root->body->statements;
	$outer = $statements[0]->require_preparation();
	$arm = $statements[1];
	$inner = $arm->body->statements;
	control_check($inner[0]->expression->require_preparation()->binding->declaration === $outer->declaration, 'Outer assignment rebound');
	$shadow = $inner[1]->require_preparation();
	control_check($shadow->declaration !== $outer->declaration, 'Shadow reused outer identity');
	control_check($inner[2]->expression->require_preparation()->binding->declaration === $shadow->declaration, 'Inner assignment lost shadow');
	control_check($statements[2]->expression->require_preparation()->declaration === $outer->declaration, 'Shadow escaped');
	control_check($arm->require_preparation()->conversion->context === conversion_context::condition, 'Missing condition decision');
	$children = iterator_to_array($arm->children());
	control_check($children === [$arm->condition, $arm->body], 'Conditional inspection order');
	$compiler->cpp();
	$original = Model::$cpp_files[0]->text;
	file_put_contents($path, "\n\n" . file_get_contents($path));
	$compiler->update_cpp([$path]);
	control_check(Model::$cpp_files[0]->text === $original, 'Moving branch syntax changed output');

	// A condition signature change must invalidate a retained consumer, then recover cleanly.
	$valid = 'function predicate(): bool { return true; } function f(): int { if (predicate()) { return 7; } else { return 8; } } function stable(): int { return 3; } return f();';
	$compiler = control_program($path, $valid);
	$compiler->prepare();
	$compiler->cpp();
	$original = Model::$cpp_files[0]->text;
	$stable = Model::$global_scope->functions_named('stable')[0]->syntax()->body->work();
	$version = $stable->version;
	foreach ([
		str_replace('predicate(): bool { return true;', 'predicate(): int { return 1;', $valid) => 'condition requires canonical bool',
		str_replace('else { return 8; }', 'else {}', $valid) => 'can reach the end',
	] as $invalid => $diagnostic)
	{
		file_put_contents($path, $invalid);
		$compiler->sync([$path]);
		control_reject($compiler, $diagnostic);
		$compiler->sync([]);
		control_reject($compiler, $diagnostic);
		control_check($stable->version === $version, 'Independent body rebuilt');
		file_put_contents($path, $valid);
		$compiler->update_cpp([$path]);
		control_check(Model::$cpp_files[0]->text === $original, 'Recovery differs from original output');
	}
	$incremental = Model::$cpp_files[0]->text;
	$compiler = control_program($path, $valid);
	$compiler->prepare();
	$compiler->cpp();
	control_check(Model::$cpp_files[0]->text === $incremental, 'Clean/incremental mismatch');
	if (isset($argv[1])) {
		file_put_contents($argv[1] . '/executions.json', json_encode($executions, JSON_PRETTY_PRINT));
	}
	echo "Control flow: branches, scopes, completion, lazy execution fixtures and recovery passed\n";
}
finally {
	unlink($path);
	rmdir($directory);
}
