<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
require_once __DIR__ . '/../host/compilation.php';

use scpp\compiler\{flavor, Source_Frontend, source_file, entry_target, Cpp_Emitter, diagnostic, diagnostic_group,
	Function_Preparation, Preparation, Compilation_Inspection, exit_conversion, Project_Compiler, Incremental_Preparation};

function verify(bool $condition, string $message): void
{
	if (!$condition) {
		throw new RuntimeException($message);
	}
}

function declaration(flavor $flavor, string $type, string $name, string $value): string
{
	return match ($flavor) {
		flavor::cpp => "$type $name = $value;",
		flavor::php => '$' . "$name $type = $value;",
		flavor::js => "let $name: $type = $value;",
	};
}

$fixtures = [];
foreach (flavor::cases() as $flavor)
{
	$decl = static fn(string $type, string $name, string $value): string => declaration($flavor, $type, $name, $value);
	$var = static fn(string $name): string => ($flavor === flavor::php ? '$' : '') . $name;
	$write = static fn(string $name, string $value): string => $var($name) . ' = ' . $value . ';';
	$prepare = static fn(string $code) => (new Source_Frontend())->prepare(
		new source_file('assignments', $code, $flavor), new entry_target(31));
	$source = $decl('int', 'number', '3') . 'echo ' . $var('number') . ';'
		. $write('number', '8') . $write('number', $var('number')) . 'echo ' . $var('number') . ';'
		. $decl('bool', 'flag', 'false') . $write('flag', 'true') . $decl('int', 'flag_int', $var('flag'))
		. 'echo ' . $var('flag_int') . ';'
		. $decl('byte', 'small', '8') . $decl('byte', 'other', '9') . $write('small', $var('other'))
		. $decl('int', 'small_int', $var('small')) . 'echo ' . $var('small_int') . ';'
		. $decl('float', 'fraction', '1.0') . $write('fraction', '8.75')
		. $decl('int', 'truncated', $var('fraction')) . 'echo ' . $var('truncated') . ';'
		. $decl('null', 'nothing', 'null') . $write('nothing', 'null')
		. $decl('string', 'text', '"old"') . $decl('string', 'copy', '""')
		. $write('text', '"N\\0W"') . $write('copy', $var('text')) . $write('copy', $var('copy'))
		. $write('text', '""') . 'echo ' . $var('copy') . '; echo ' . $var('text') . '; return ' . $var('number') . ';';
	$entry = $prepare($source);
	$statements = $entry->body()->statements;
	verify($statements[0]->constant() === 3, 'Write mutated initializer facts');
	verify($statements[1]->value->constant() === 3, 'Write changed an earlier read');
	verify($statements[4]->value->constant() === 8, 'Later read retained stale initializer');
	verify($statements[3]->value->constant() === 8, 'Self assignment missed preceding write');
	verify($statements[2]->local_def() === $statements[0], 'Write binding missing');
	verify(Compilation_Inspection::program($entry)['statements'][2]['kind'] === 'assignment', 'Inspection skipped write');
	$before = serialize($entry);
	$cpp = (new Cpp_Emitter())->emit($entry);
	verify(serialize($entry) === $before, 'Lowering mutated prepared facts');
	verify(str_contains($cpp, 'local_0 = 8LL;'), 'Assignment not emitted');
	$fixtures[$flavor->name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex("38198N\0W")];
	$unknown = $prepare('global ' . $decl('int', 'external', '8') . $decl('int', 'value', '1')
		. $write('value', $var('external')) . 'return ' . $var('value') . ';');
	verify($unknown->body()->statements[2]->conversion() === exit_conversion::checked, 'Unknown write lost runtime exit check');
	$restored = $prepare('global ' . $decl('int', 'external', '8') . $decl('int', 'value', $var('external'))
		. $write('value', '8') . 'return ' . $var('value') . ';');
	verify($restored->body()->statements[2]->conversion() === exit_conversion::proven, 'Known write did not restore facts');
	foreach ([
		$write('missing', '8'), $write('later', '8') . $decl('int', 'later', '1'),
		'global ' . $decl('int', 'external', '1') . $write('external', '8'),
		$decl('byte', 'small', '8') . $write('small', '9'),
		$decl('int', 'n', '1') . $write('n', 'true'),
		$decl('float', 'f', '1.0') . $write('f', '8'),
		$decl('string', 's', '""') . $write('s', '8'),
		$decl('int', 'n', '1') . $write('n', '2147483648') . 'return ' . $var('n') . ';',
		$decl('int', 'n', '8') . $write('n', '300') . $decl('byte', 'b', $var('n')),
	] as $invalid) {
		try {
			$prepare($invalid . 'return 8;');
		}
		catch (diagnostic|diagnostic_group $error) {
			continue;
		}
		throw new RuntimeException('Invalid assignment accepted: ' . $invalid);
	}
	Function_Preparation::reset_body($entry->body());
	try {
		$statements[2]->local_def();
		throw new RuntimeException('Reset retained assignment binding');
	}
	catch (LogicException $error) {
		verify($error->getMessage() === 'Assignment binding is not settled', 'Unexpected reset failure');
	}
	$reprepared = (new Preparation())->prepare($entry->file, new entry_target(31));
	verify((new Cpp_Emitter())->emit($reprepared) === $cpp, 'Repreparation changed assignment emission');
}

$directory = sys_get_temp_dir() . '/scpp-assignments-' . bin2hex(random_bytes(8));
mkdir($directory);
try
{
	file_put_contents($directory . '/module.json', '{"entry":"main.php"}');
	file_put_contents($directory . '/main.php', 'show(); return 8;');
	file_put_contents($directory . '/lib.php', 'function show(): void { $n int=1; $n=8; echo $n; }');
	$compiler = new Project_Compiler();
	$session = $compiler->frontend($directory);
	Incremental_Preparation::prepare($session, new entry_target(31));
	$caller = $session->entry->entry->ast;
	$body = $session->scope->resolve('func:show')->body();
	file_put_contents($directory . '/lib.php', 'function show(): void { $n int=1; $n=19; echo $n; }');
	$session = $compiler->frontend($directory, $session);
	verify(Incremental_Preparation::prepare($session, new entry_target(31))['prepared'] === 1, 'Wrong incremental scope');
	verify($session->entry->entry->ast === $caller, 'Caller reparsed');
	$changed = $session->scope->resolve('func:show')->body();
	verify($changed !== $body && $changed->statements[2]->value->constant() === 19, 'Stale assignment cache');
}
finally {
	foreach (glob($directory . '/*') as $path) {
		unlink($path);
	}
	rmdir($directory);
}
if (isset($argv[1])) {
	file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n");
}
echo "Local assignment typing, source-order facts, reset, incremental reuse and pure lowering passed\n";
