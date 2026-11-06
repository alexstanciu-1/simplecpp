<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
require_once __DIR__ . '/../host/syntax.php';
require_once __DIR__ . '/../host/compilation.php';

use scpp\compiler\{flavor, source_file, Source_Frontend, entry_target, Cpp_Emitter, diagnostic, diagnostic_group,
	Project_Compiler, Incremental_Preparation, Function_Preparation, Preparation, Program_Frontend, Cpp_Program,
	Syntax_Inspection, Compilation_Inspection, ast_update_statement, update_operator};

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

/** Check conversion failures at the source boundary, never accept an internal compiler exception. */
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
	$prepare = static fn(string $source) => (new Source_Frontend())->prepare(new source_file('updates', $source, $flavor), new entry_target(31));
	$x = $var('x');
	$forms = ['++' . $x, $x . '++', '--' . $x, $x . '--'];
	$entry = $prepare($decl('x', 'int', '10') . $forms[0] . '; return 8;');
	$update = $entry->body()->statements[1];
	verify(($update instanceof ast_update_statement) && ($update->operator === update_operator::increment) && !$update->postfix, 'Prefix syntax intent');
	verify($update->local_def() === $entry->body()->statements[0], 'Shared storage binding');
	verify($entry->body()->expression_visits === 2, 'Update fabricated an expression traversal');
	$direct = (new Cpp_Emitter())->emit($entry);
	verify(str_contains($direct, '++local_0;') && !str_contains($direct, 'compound_previous') && !str_contains($direct, '([&]'), 'Update must lower directly');
	$ref = match ($flavor) {
		flavor::php => 'int &$v',
		flavor::cpp => 'int &v',
		flavor::js => '&v: int',
	};
	$param = match ($flavor) {
		flavor::php => 'int $v',
		flavor::cpp => 'int v',
		flavor::js => 'v: int',
	};
	$code = $decl('x', 'int64', '10');
	foreach ($forms as $form) {
		$code .= $form . '; echo ' . $x . '; echo ",";';
	}
	$expected = '11,12,11,10,';
	foreach ($forms as $index => $form) {
		$increment = $index < 2;
		$code .= $x . '=' . ($increment ? '0' : '4') . '; for (' . $form . ';'
			. $x . ($increment ? '<3' : '>1') . ';' . $form . ') { echo ' . $x . ';} echo ' . $x . ';';
		$expected .= $increment ? '123' : '321';
	}
	$code .= $fn('copy', $param, 'int', '++' . $var('v') . '; return ' . $var('v') . ';')
		. $fn('mutate', $ref, 'void', $var('v') . '++; return;')
		. $fn('leave', $ref, 'void', 'for (' . $var('v') . '=0;true;' . $var('v') . '++) { return; } return;')
		. $x . '=1; echo copy(' . $x . '); echo ' . $x . '; mutate(' . $x . '); echo ' . $x . ';'
		. 'for (' . $x . '=0;' . $x . '<4;' . $x . '++) { if (' . $x . '==1) { continue; } if (' . $x . '==3) { break; } echo ' . $x . ';} echo ' . $x . ';'
		. 'leave(' . $x . '); echo ' . $x . '; for (++' . $x . ';false;' . $x . '++) {} echo ' . $x . ';'
		. 'for (' . $decl('outer', 'int', '0') . $var('outer') . '<2;++' . $var('outer') . ') {'
		. 'for (' . $decl('inner', 'int', '0') . $var('inner') . '<2;' . $var('inner') . '++) { continue; }'
		. $decl('outer', 'int', '50') . $var('outer') . '++; echo ' . $var('outer') . ';}'
		. $x . '=0; while (' . $x . '<2) { ++' . $x . '; } do { --' . $x . '; } while (' . $x . '>0); echo ' . $x . ';'
		. 'for (' . $x . '=0;' . $x . '<2;' . $x . '++) {' . $x . '+=1;} echo ' . $x . ';'
		. $x . '=0; for (' . $x . '+=1;' . $x . '<3;' . $x . '++) {} echo ' . $x . ';'
		. 'for (' . $x . '--;' . $x . '<4;' . $x . '+=1) {} echo ' . $x . '; return 8;';
	$entry = $prepare($code);
	$before = serialize($entry);
	$cpp = (new Cpp_Emitter())->emit($entry);
	verify(serialize($entry) === $before, 'Lowering mutated update facts');
	Syntax_Inspection::file($entry->file);
	Compilation_Inspection::body($entry->body());
	$fixtures[$flavor->name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex($expected . '2120230151510234')];
	Function_Preparation::reset_body($entry->body());
	$again = (new Preparation())->prepare($entry->file, new entry_target(31));
	verify((new Cpp_Emitter())->emit($again) === $cpp, 'Update reset/reprepare differs');
	foreach (['bool' => 'true', 'string' => '"a"', 'null' => 'null'] as $type => $value) {
		foreach ($forms as $form) {
			rejects(fn() => $prepare($decl('x', $type, $value) . $form . '; return 8;'), 'Unsupported update type');
		}
	}
	foreach ($forms as $form) {
		rejects(fn() => $prepare($form . '; return 8;'), 'existing local');
	}
	foreach (['echo ' . $x . '++;', 'echo ++' . $x . ';', '++' . $x . '++;', '++1;', 'copy()++;', '++(int)' . $x . ';'] as $invalid) {
		rejects(fn() => $prepare($decl('x', 'int', '1') . $invalid . 'return 8;'), 'Expected');
	}
	$constant = $flavor === flavor::js ? 'const VALUE: int = 1;' : 'const int VALUE = 1;';
	rejects(fn() => $prepare($constant . $var('VALUE') . '++; return 8;'), $flavor === flavor::php ? 'existing local' : 'constant');
	$global = match ($flavor) {
		flavor::php => 'global $x int=1;',
		flavor::cpp => 'global int x=1;',
		flavor::js => 'global let x:int=1;',
	};
	rejects(fn() => $prepare($global . $x . '++; return 8;'), 'global writes are deferred');
	rejects(fn() => $prepare('for (' . $decl('i', 'int', '0') . 'false;' . $var('inside') . '++) {'
		. $decl('inside', 'int', '0') . '} return 8;'), 'existing local');
	rejects(fn() => $prepare('for (' . $decl('i', 'int', '0') . 'false;' . $var('i') . '++) {} ' . $var('i') . '++; return 8;'), 'existing local');
	foreach (['255' => $x . '++', '0' => '--' . $x] as $initial => $form) {
		$source = $decl('x', 'int', (string) $initial) . $form . ';' . $decl('narrow', 'byte', $x) . 'return 8;';
		$fixtures[$flavor->name . '_range_' . $initial] = ['cpp' => (new Cpp_Emitter())->emit($prepare($source)),
			'stdout_hex' => '', 'catch_out_of_range' => true];
	}
	$directory = sys_get_temp_dir() . '/scpp-updates-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try {
		file_put_contents($directory . '/module.json', '{}');
		$main = $directory . '/main.' . $flavor->name;
		$inferred = match ($flavor) {
			flavor::php => '$x=source();',
			flavor::cpp => 'auto x=source();',
			flavor::js => 'let x=source();',
		};
		file_put_contents($main, $inferred . $x . '++; echo ' . $x . '; return 8;');
		$lib = $directory . '/lib.' . $flavor->name;
		file_put_contents($lib, $fn('source', '', 'int', 'return 1;'));
		$compiler = new Project_Compiler();
		$session = $compiler->frontend($directory);
		$target = new entry_target(31);
		Incremental_Preparation::prepare($session, $target);
		$caller = $session->entry->entry->ast;
		$tokens = $caller->tokens;
		file_put_contents($lib, $fn('source', '', 'bool', 'return true;'));
		$session = $compiler->frontend($directory, $session);
		rejects(fn() => Incremental_Preparation::prepare($session, $target), 'Unsupported update type');
		file_put_contents($lib, $fn('source', '', 'int64', 'return 10;'));
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['prepared'] === 2, 'Update recovery failed');
		verify(($session->entry->entry->ast === $caller) && ($caller->tokens === $tokens), 'Caller syntax replaced');
		$sources = [];
		foreach ($session->files() as $path => $file) {
			$sources[$path] = $file->source();
		}
		$program = (new Program_Frontend())->prepare_sources($session->entry_key(), $sources, $target);
		$native_files = Cpp_Program::emit($program)->files;
		$fixtures[$flavor->name . '_recovery'] = ['files' => $native_files, 'stdout_hex' => bin2hex('11')];
		file_put_contents($lib, $fn('source', '', 'int64', 'return 100;'));
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['reused'] === 1, 'Body-only edit invalidated caller');
		file_put_contents($main, $inferred . '--' . $x . '; echo ' . $x . '; return 8;');
		touch($main, time() + 2);
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['prepared'] === 1, 'Same-size operator edit not prepared');
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
echo "Generated int64 updates, shared storage writes and incremental recovery passed\n";
