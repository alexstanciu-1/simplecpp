<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
require_once __DIR__ . '/../host/syntax.php';
require_once __DIR__ . '/../host/compilation.php';

use scpp\compiler\{flavor, source_file, Source_Frontend, entry_target, Cpp_Emitter, diagnostic, diagnostic_group,
	Project_Compiler, Incremental_Preparation, Function_Preparation, Preparation, Program_Frontend, Cpp_Program,
	Syntax_Inspection, Compilation_Inspection};

function verify(bool $ok, string $message): void
{
	if (!$ok) {
		throw new RuntimeException($message);
	}
}

function definition(flavor $flavor, string $name, string $parameters, string $type, string $body): string
{
	return ($flavor === flavor::cpp ? "$type $name($parameters)" : "function $name($parameters): $type") . '{' . $body . '}';
}

function local(flavor $flavor, string $name, string $type, string $value): string
{
	return match ($flavor) {
		flavor::php => "$$name $type=$value;",
		flavor::cpp => "$type $name=$value;",
		flavor::js => "let $name: $type=$value;",
	};
}

/** Require a source diagnostic for invalid loop transfers and completion failures. */
function rejects(Closure $action, string $message): void
{
	try {
		$action();
	}
	catch (diagnostic|diagnostic_group $error) {
		verify(str_contains($error->getMessage(), $message), $error->getMessage());
		return;
	}
	throw new RuntimeException('Expected diagnostic: ' . $message);
}

$fixtures = [];
foreach (flavor::cases() as $flavor)
{
	$var = static fn(string $name): string => ($flavor === flavor::php ? '$' : '') . $name;
	$fn = static fn(string $name, string $parameters, string $type, string $body): string => definition($flavor, $name, $parameters, $type, $body);
	$decl = static fn(string $name, string $type, string $value): string => local($flavor, $name, $type, $value);
	$prepare = static fn(string $source) => (new Source_Frontend())->prepare(new source_file('transfers', $source, $flavor), new entry_target(31));
	$entry = $prepare('while (true) { break; } return 8;');
	verify($entry->body()->statement_visits === 3 && $entry->body()->expression_visits === 2, 'Transfers need no extra preparation traversal');
	$code = $fn('test', '', 'bool', 'echo "T"; return false;')
		. $fn('choose', '', 'int', 'do { if (true) { break; } else { return 2; } } while (test()); return 8;')
		. $fn('nested', '', 'int', 'do { while (true) { break; } return 8; } while (false);')
		. $decl('i', 'int', '0')
		. 'while (' . $var('i') . ' < 4) {' . $var('i') . '=' . $var('i') . '+1;'
		. 'if (' . $var('i') . ' == 2) { continue; }'
		. 'if (' . $var('i') . ' == 4) { break; } echo ' . $var('i') . ';}'
		. 'do { echo "D"; continue; } while (test());'
		. 'do { echo "B"; break; } while (test());'
		. $var('i') . '=0; while (' . $var('i') . '<2) {' . $var('i') . '=' . $var('i') . '+1;'
		. $decl('j', 'int', '0') . 'do {' . $var('j') . '=' . $var('j') . '+1;'
		. 'if (' . $var('j') . ' == 1) { continue; } else { break; }} while (true); echo ' . $var('i') . ';}'
		. 'echo choose(); return nested();';
	$entry = $prepare($code);
	$before = serialize($entry);
	$cpp = (new Cpp_Emitter())->emit($entry);
	verify(serialize($entry) === $before, 'Lowering mutated facts');
	Syntax_Inspection::file($entry->file);
	Compilation_Inspection::body($entry->body());
	verify(str_contains($cpp, 'continue;') && str_contains($cpp, 'break;'), 'Native transfer lowering');
	$fixtures[$flavor->name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('13DTB128')];
	Function_Preparation::reset_body($entry->body());
	$again = (new Preparation())->prepare($entry->file, new entry_target(31));
	verify((new Cpp_Emitter())->emit($again) === $cpp, 'Reset/reprepare differs');
	foreach (['break;', 'continue;', 'if (false) { break; }', 'if (true) { continue; }'] as $bad) {
		rejects(fn() => $prepare($bad . 'return 8;'), 'requires an enclosing loop');
	}
	foreach (['break 2;', 'continue 1;', 'break label;', 'continue label;'] as $bad) {
		rejects(fn() => $prepare('while (true) {' . $bad . '} return 8;'), 'only unlabelled');
	}
	foreach (['break;', 'continue;', 'if (true) { break; } else { continue; }'] as $transfer) {
		rejects(fn() => $prepare('while (true) {' . $transfer . 'echo 1;} return 8;'), 'after unconditional control transfer');
	}
	foreach (['break;', 'continue;', 'if (true) { break; } else { return 8; }',
		'if (true) { continue; } else { return 8; }', 'if (true) { break; } return 8;'] as $body) {
		rejects(fn() => $prepare($fn('bad', '', 'int', 'do {' . $body . '} while (false);') . 'return 8;'), 'Non-void function requires');
	}
	rejects(fn() => $prepare('do { break; } while (false);'), 'Entry slice requires');
	rejects(fn() => $prepare($fn('bad', '', 'void', 'break;') . 'while (true) { bad(); break; } return 8;'), 'requires an enclosing loop');
	// Continue rechecks the condition and must not retain a stale narrow conversion proof.
	$failure = $decl('x', 'int', '1') . $decl('iteration', 'int', '0') . 'while (' . $var('iteration') . '<2) {'
		. $var('iteration') . '=' . $var('iteration') . '+1;'
		. $decl('narrow', 'byte', $var('x')) . $var('x') . '=300; continue; } return 8;';
	$fixtures[$flavor->name . '_continue_range'] = ['cpp' => (new Cpp_Emitter())->emit($prepare($failure)),
		'stdout_hex' => '', 'catch_out_of_range' => true];
	$directory = sys_get_temp_dir() . '/scpp-transfers-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try {
		file_put_contents($directory . '/module.json', '{}');
		$main = $directory . '/main.' . $flavor->name;
		file_put_contents($main, 'do { continue; } while (predicate()); return 8;');
		$lib = $directory . '/lib.' . $flavor->name;
		$write = static function (string $type, string $value) use ($lib, $fn): void {
			file_put_contents($lib, $fn('predicate', '', $type, 'return ' . $value . ';'));
		};
		$write('bool', 'false');
		$compiler = new Project_Compiler();
		$session = $compiler->frontend($directory);
		$target = new entry_target(31);
		Incremental_Preparation::prepare($session, $target);
		$caller = $session->entry->entry->ast;
		$tokens = $caller->tokens;
		$write('null', 'null');
		$session = $compiler->frontend($directory, $session);
		rejects(fn() => Incremental_Preparation::prepare($session, $target), 'Unsupported condition type');
		$write('bool', 'false');
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['prepared'] === 2, 'Transfer recovery failed');
		verify(($session->entry->entry->ast === $caller) && ($caller->tokens === $tokens), 'Caller syntax replaced');
		// Editing the caller reparses only that unit; the unchanged callee AST remains shared.
		$callee = $session->scope->entry('func:predicate')->declaration();
		file_put_contents($main, 'do { break; } while (predicate()); return 8;');
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['prepared'] === 1, 'Transfer edit preparation');
		verify($session->scope->entry('func:predicate')->declaration() === $callee, 'Transfer edit reparsed callee');
		file_put_contents($main, 'break; return 8;');
		$session = $compiler->frontend($directory, $session);
		rejects(fn() => Incremental_Preparation::prepare($session, $target), 'requires an enclosing loop');
		file_put_contents($main, 'while (true) { break; } return 8;');
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['prepared'] === 1, 'Outside-loop recovery');
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['reused'] === 2, 'No-op rebuilt');
	}
	finally {
		foreach (glob($directory . '/*') as $path) {
			unlink($path);
		}
		rmdir($directory);
	}
}
if (isset($argv[1])) {
	file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n");
}
echo "Loop transfers, completion, single-pass preparation and incremental recovery passed\n";
