<?php

/* Prove structured branches, loops, switches, completion and incremental recovery. */
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
		'switch_basic' => ['$x = 2; switch ($x) { case 1: return 1; case 2: return 2; default: return 3; }', 2],
		'switch_fallthrough' => ['$x = 0; switch (1) { case 1: $x += 2; case 2: $x += 3; default: $x += 4; } return $x;', 9],
		'switch_group' => ['switch (2) { case 1: case 2: return 7; default: return 1; }', 7],
		'switch_default_middle_match' => ['switch (2) { case 1: return 1; default: return 3; case 2: return 2; }', 2],
		'switch_default_middle_fall' => ['$x = 0; switch (8) { case 1: return 1; default: $x += 3; case 2: $x += 2; } return $x;', 5],
		'switch_default_group' => ['switch (9) { default: case 1: return 4; }', 4],
		'switch_no_match' => ['$x = 1; switch (8) { case 1: $x = 7; } return $x;', 1],
		'switch_empty' => ['$x = 0; switch ($x++) {} return $x;', 1],
		'switch_trailing_labels' => ['switch (2) { case 1: return 1; case 2: default: } return 4;', 4],
		'switch_once' => ['function select(int &$n): int { $n++; return 2; } $n = 0; switch (select($n)) { case 1: $n += 9; break; case 2: $n += 2; break; } return $n;', 3],
		'switch_signed_constant' => ['switch (-PHP_INT_MAX) { case -(PHP_INT_MAX): return 5; default: return 1; }', 5],
		'switch_double_sign' => ['switch (2) { case -(-2): return 5; default: return 1; }', 5],
		'switch_maximum' => ['switch (PHP_INT_MAX) { case 9223372036854775807: return 6; default: return 1; }', 6],
		'switch_shadow' => ['$x int = 9; $sum = 0; switch (1) { case 1: $x int = 2; $sum += $x; case 2: $x int = 3; $sum += $x; } return $sum + $x;', 14],
		'switch_break_in_loop' => ['$sum = 0; for ($i = 0; $i < 3; $i++) { switch ($i) { case 1: break; default: $sum++; } $sum += 2; } return $sum;', 8],
		'switch_continue_for' => ['$sum = 0; for ($i = 0; $i < 3; $i++) { switch ($i) { case 1: continue; default: $sum++; } $sum += 2; } return $sum;', 6],
		'switch_continue_do' => ['$i = 0; do { $i++; switch ($i) { default: continue; } $i += 20; } while ($i < 3); return $i;', 3],
		'switch_nested_break' => ['$x = 0; switch (1) { case 1: switch (2) { default: $x++; break; } $x += 2; break; default: $x += 10; } return $x;', 3],
		'switch_loop_inside' => ['$x = 0; switch (1) { default: while (true) { $x++; break; } $x += 2; break; } return $x;', 3],
		'switch_return_fallthrough' => ['function f(int $x): int { switch ($x) { case 1: $x++; default: return $x; } } return f(1);', 2],
		'switch_return_branches' => ['function f(int $x): int { switch ($x) { case 1: if (true) { return 3; } else { return 4; } default: return 5; } } return f(1);', 3],
		'switch_return_loop' => ['function f(): int { for (;;) { switch (1) { default: break; } return 8; } } return f();', 8],
		'while_zero' => ['$n = 2; while (false) { $n++; } return $n;', 2],
		'while_count' => ['$n = 0; while ($n < 4) { $n++; } return $n;', 4],
		'do_once' => ['$n = 0; do { $n++; } while (false); return $n;', 1],
		'do_continue' => ['$n = 0; do { $n++; continue; $n += 10; } while ($n < 3); return $n;', 3],
		'do_break_skips_test' => ['function test(int &$n): bool { $n++; return true; } $n = 0; do { $n += 2; break; } while (test($n)); return $n;', 2],
		'while_continue' => ['$n = 0; $sum = 0; while ($n < 4) { $n++; if ($n === 2) { continue; } $sum += $n; } return $sum;', 8],
		'for_continue_updates' => ['$sum = 0; for ($i = 0; $i < 4; $i++) { if ($i === 2) { continue; } $sum += $i; } return $sum;', 4],
		'for_break_skips_updates' => ['$n = 0; for (; true; $n++) { $n += 3; break; } return $n;', 3],
		'for_empty' => ['$n = 0; for (;;) { $n++; if ($n === 4) { break; } } return $n;', 4],
		'for_typed_shadow' => ['$i int = 9; $sum = 0; for ($i int = 0; $i < 3; $i++) { $sum += $i; } return $i + $sum;', 12],
		'for_existing' => ['$i = 9; for ($i = 0; $i < 3; $i++) {} return $i;', 3],
		'for_lists' => ['$sum = 0; for ($i int = 0, $j int = 1; $sum += 1, $i < 3; $i++, $j += $i) { $sum += $j; } return $sum;', 11],
		'for_chain' => ['$sum = 0; for ($i = $j = 0; $i < 3; $i++, $j++) { $sum += $j; } return $sum;', 3],
		'nested_transfers' => ['$sum = 0; for ($i = 0; $i < 3; $i++) { $j = 0; while ($j < 3) { $j++; if ($j === 1) { continue; } $sum++; break; } $sum += 2; } return $sum;', 9],
		'loop_cast' => ['$n = 2; while ((bool) $n) { $n--; } return $n;', 0],
		'do_return' => ['function f(): int { do { return 7; } while (false); } return f();', 7],
		'for_return' => ['function f(): int { for (;;) { return 6; } } return f();', 6],
		'unreachable_break' => ['function f(): int { for (;;) { return 6; break; } } return f();', 6],
		'do_arm_return' => ['function f(bool $b): int { do { if ($b) { return 3; } else { return 4; } } while (false); } return f(false);', 4],
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
		['switch (true) {}', 'selector requires canonical int'],
		['switch ("1") {}', 'selector requires canonical int'],
		['switch (1) { case true: break; }', 'case requires canonical int'],
		['$x = 1; switch (1) { case $x: break; }', 'supported constant integer expression'],
		['switch (1) { case 1 + 1: break; }', 'supported constant integer expression'],
		['switch (1) { case PHP_INT_MAX: case 9223372036854775807: break; }', 'duplicate case value'],
		['switch (1) { case -0: break; case +0: break; }', 'duplicate case value'],
		['switch (1) { default: break; default: break; }', 'duplicate default'],
		['switch (1) { default: continue; }', 'enclosing legal target'],
		['switch (1) { case 1: $x = 2; case 2: return $x; }', 'established local declaration'],
		['switch (1) { case 1: $x = 2; } return $x;', 'established local declaration'],
		['switch (1) { case 1: break; default: return $missing; }', 'established local declaration'],
		['function f(int $x): int { switch ($x) { case 1: return 1; } }', 'can reach the end'],
		['function f(int $x): int { switch ($x) { case 1: break; default: return 1; } }', 'can reach the end'],
		['function f(int $x): int { switch ($x) { default: return 1; case 2: } }', 'can reach the end'],
		['function f(): int { do { switch (1) { default: continue; } return 1; } while (false); }', 'can reach the end'],
		['while (1) {}', 'condition requires canonical bool'],
		['do {} while (1);', 'condition requires canonical bool'],
		['for (; 1;) {}', 'condition requires canonical bool'],
		['break;', 'enclosing legal target'],
		['continue;', 'enclosing legal target'],
		['while (false) {} break;', 'enclosing legal target'],
		['function f(): void { continue; } while (false) {}', 'enclosing legal target'],
		['for ($i = 0; $i < 1; $i++) {} return $i;', 'established local declaration'],
		['do { $x = false; } while ($x);', 'established local declaration'],
		['for (; false; $x++) { $x = 0; }', 'established local declaration'],
		['for (; false; $x = 1) {}', 'established local declaration'],
		['for ($i int = 0, $i int = 1;;) { break; }', 'already declared'],
		['function f(): int { while (true) { return 1; } }', 'can reach the end'],
		['function f(): int { do { break; } while (true); }', 'can reach the end'],
		['function f(): int { do { continue; } while (false); }', 'can reach the end'],
		['function f(): int { for (;;) { break; } }', 'can reach the end'],
		['function f(): int { do { return 1; } while ($missing); }', 'established local declaration'],
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
	foreach (['switch (1) { $x = 2; case 1: break; }', 'switch (1) { case 1: return 1;',
		'switch (1) { default: function local(): int { return 1; } }',
		'while (true) break;', 'do {} while (true)', 'for (;;) { break 2; }',
		'for (;;) { continue 2; }', 'foreach ($items as $item) {}',
		'if (true) return 1;', 'if (true): return 1; endif;',
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

	// Case grouping, named inspection edges and distinct break/continue targets.
	$switch_source = 'function f(): int { for ($i = 0; $i < 2; $i++) { switch ($i) { case 0: case +1: break; default: continue; } } return 4; } return f();';
	$compiler = control_program($path, $switch_source);
	$compiler->prepare();
	$switch_body = Model::$global_scope->functions_named('f')[0]->syntax()->body;
	$enclosing_loop = $switch_body->statements[0];
	$selection = $enclosing_loop->body->statements[0];
	$first_group = $selection->groups[0];
	$default_group = $selection->groups[1];
	control_check(count($selection->groups) === 2 && count($first_group->labels) === 2, 'Consecutive case labels did not share one group');
	control_check($first_group->body instanceof block_node, 'Switch group replaced the shared statement body');
	control_check(weakref_get($first_group->body->statements[0]->require_preparation()->target) === $selection, 'Switch break targets the loop');
	control_check(weakref_get($default_group->body->statements[0]->require_preparation()->target) === $enclosing_loop, 'Switch intercepted loop continue');
	control_check($first_group->labels[1]->require_preparation()->decimal === '1', 'Case sign was not normalized');
	control_check($default_group->labels[0]->require_preparation()->decimal === null, 'Default has a fabricated value');
	control_check(iterator_to_array($selection->children()) === [$selection->selector, $first_group, $default_group], 'Switch inspection order');
	control_check(iterator_to_array($first_group->children()) === [$first_group->labels[0], $first_group->labels[1], $first_group->body], 'Case group inspection order');
	control_check(iterator_to_array($first_group->labels[0]->children()) === [$first_group->labels[0]->value], 'Case value inspection');
	control_check(iterator_to_array($default_group->labels[0]->children()) === [], 'Default inspection must be empty');
	$compiler->cpp();
	$switch_original = Model::$cpp_files[0]->text;
	file_put_contents($path, "\n\n" . $switch_source);
	$compiler->update_cpp([$path]);
	control_check(Model::$global_scope->functions_named('f')[0]->syntax()->body === $switch_body, 'Moving unchanged switch lost body identity');
	control_check(Model::$cpp_files[0]->text === $switch_original, 'Moving switch changed generated output');

	// Signature, label and completion failures withhold output and recover incrementally.
	$switch_valid = 'function selector(): int { return 1; } function f(): int { switch (selector()) { case 1: return 7; default: return 8; } } function stable(): int { return 3; } return f();';
	$compiler = control_program($path, $switch_valid);
	$compiler->prepare();
	$compiler->cpp();
	$switch_original = Model::$cpp_files[0]->text;
	$switch_stable = Model::$global_scope->functions_named('stable')[0]->syntax()->body->work();
	$switch_version = $switch_stable->version;
	foreach ([
		str_replace('selector(): int { return 1;', 'selector(): bool { return true;', $switch_valid) => 'selector requires canonical int',
		str_replace('default:', 'case +1:', $switch_valid) => 'duplicate case value',
		str_replace('default: return 8;', 'default: break;', $switch_valid) => 'can reach the end',
		str_replace('default: return 8;', 'default: continue;', $switch_valid) => 'enclosing legal target',
	] as $invalid => $diagnostic)
	{
		file_put_contents($path, $invalid);
		$compiler->sync([$path]);
		control_reject($compiler, $diagnostic);
		$compiler->sync([]);
		control_reject($compiler, $diagnostic);
		control_check($switch_stable->version === $switch_version, 'Switch failure rebuilt independent body');
		file_put_contents($path, $switch_valid);
		$compiler->update_cpp([$path]);
		control_check(Model::$cpp_files[0]->text === $switch_original, 'Switch repair differs from original output');
	}
	file_put_contents($path, str_replace('case 1:', 'case :', $switch_valid));
	$parse_failed = false;
	try { $compiler->sync([$path]); }
	catch (\RuntimeException $error) { $parse_failed = true; }
	control_check($parse_failed && !Model::$rebuild_required && Model::$cpp_files->is_empty(), 'Malformed switch did not fail cleanly');
	file_put_contents($path, $switch_valid);
	$compiler->update_cpp([$path]);
	control_check(Model::$cpp_files[0]->text === $switch_original, 'Malformed switch repair changed output');
	$compiler = control_program($path, $switch_valid);
	$compiler->prepare();
	$compiler->cpp();
	control_check(Model::$cpp_files[0]->text === $switch_original, 'Clean/incremental switch mismatch');

	// Named loop edges, prepared transfer identities and independent lazy cursors.
	$loop_source = 'function f(): int { for ($i = 0; $i < 2; $i++) { while (true) { break; } continue; } do { return 4; } while (false); } return f();';
	$compiler = control_program($path, $loop_source);
	$compiler->prepare();
	$function_body = Model::$global_scope->functions_named('f')[0]->syntax()->body;
	$loop = $function_body->statements[0];
	$nested = $loop->body->statements[0];
	control_check(weakref_get($nested->body->statements[0]->require_preparation()->target) === $nested, 'Break lost nearest loop');
	control_check(weakref_get($loop->body->statements[1]->require_preparation()->target) === $loop, 'Continue lost outer loop');
	control_check(iterator_to_array($loop->children()) === [$loop->initialization[0], $loop->conditions[0], $loop->updates[0], $loop->body], 'For inspection order');
	control_check(iterator_to_array($nested->children()) === [$nested->condition, $nested->body], 'While inspection order');
	$post_test = $function_body->statements[1];
	control_check(iterator_to_array($post_test->children()) === [$post_test->body, $post_test->condition], 'Do-while inspection order');
	control_check(iterator_to_array($loop->children()) === iterator_to_array($loop->children()), 'Loop inspection cannot be repeated');
	control_check(is_subclass_of(foreach_node::class, loop_node::class), 'Future foreach lost its shared loop foundation');
	$compiler->cpp();
	$original = Model::$cpp_files[0]->text;
	file_put_contents($path, "\n\n" . $loop_source);
	$compiler->update_cpp([$path]);
	control_check(Model::$global_scope->functions_named('f')[0]->syntax()->body === $function_body, 'Moved unchanged loop body lost identity');
	control_check(Model::$cpp_files[0]->text === $original, 'Moving loop syntax changed output');

	// Loop condition dependencies, transfer cleanup and completion recover without a rebuild.
	$valid_loop = 'function predicate(): bool { return false; } function f(): int { do { return 7; } while (predicate()); } function stable(): int { return 3; } return f();';
	$compiler = control_program($path, $valid_loop);
	$compiler->prepare();
	$compiler->cpp();
	$original_loop = Model::$cpp_files[0]->text;
	$stable_loop = Model::$global_scope->functions_named('stable')[0]->syntax()->body->work();
	$stable_version = $stable_loop->version;
	foreach ([
		str_replace('predicate(): bool { return false;', 'predicate(): int { return 0;', $valid_loop) => 'condition requires canonical bool',
		str_replace('do { return 7; }', 'do { break; }', $valid_loop) => 'can reach the end',
		str_replace('do { return 7; } while (predicate());', 'break; return 7;', $valid_loop) => 'enclosing legal target',
	] as $invalid => $diagnostic)
	{
		file_put_contents($path, $invalid);
		$compiler->sync([$path]);
		control_reject($compiler, $diagnostic);
		$compiler->sync([]);
		control_reject($compiler, $diagnostic);
		control_check($stable_loop->version === $stable_version, 'Loop failure rebuilt independent body');
		file_put_contents($path, $valid_loop);
		$compiler->update_cpp([$path]);
		control_check(Model::$cpp_files[0]->text === $original_loop, 'Loop repair changed output');
	}
	$compiler = control_program($path, $valid_loop);
	$compiler->prepare();
	$compiler->cpp();
	control_check(Model::$cpp_files[0]->text === $original_loop, 'Clean/incremental loop mismatch');

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
