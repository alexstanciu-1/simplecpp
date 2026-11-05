<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';

use scpp\compiler\{flavor, source_file, Source_Frontend, entry_target, Cpp_Emitter, diagnostic, diagnostic_group,
	Project_Compiler, Incremental_Preparation, Function_Preparation, Preparation, Program_Frontend, Cpp_Program, passing_mode};

function verify(bool $ok, string $message): void
{
	if (!$ok) {
		throw new RuntimeException($message);
	}
}

/** @param string[] $parameters */
function definition(flavor $flavor, string $name, array $parameters /** list<string> */, string $type, string $body): string
{
	$parameters = implode(',', $parameters);
	return ($flavor === flavor::cpp ? "$type $name($parameters)" : "function $name($parameters): $type") . '{' . $body . '}';
}

/** Spell the same passing mode with each flavor's parameter-name placement. */
function parameter(flavor $flavor, string $name, string $type, bool $reference = false): string
{
	$marker = $reference ? '&' : '';
	return match ($flavor) {
		flavor::php => "$type $marker$$name",
		flavor::cpp => "$type $marker$name",
		flavor::js => "$marker$name: $type",
	};
}

function local(flavor $flavor, string $name, string $type, string $value): string
{
	return match ($flavor) {
		flavor::php => "$$name $type=$value;",
		flavor::cpp => "$type $name=$value;",
		flavor::js => "let $name: $type=$value;",
	};
}

/** Require a compiler diagnostic, never defer invalid binding to Clang. */
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
	$p = static fn(string $name, string $type, bool $ref = false): string => parameter($flavor, $name, $type, $ref);
	$fn = static fn(string $name, array $parameters, string $type, string $body): string => definition($flavor, $name, $parameters, $type, $body);
	$decl = static fn(string $name, string $type, string $value): string => local($flavor, $name, $type, $value);
	$prepare = static fn(string $source) => (new Source_Frontend())->prepare(new source_file('references', $source, $flavor), new entry_target(31));
	$defs = $fn('change', [$p('v', 'int', true)], 'int', $var('v') . '=12; return 5;')
		. $fn('forward', [$p('v', 'int', true)], 'void', 'change(' . $var('v') . ');')
		. $fn('value_copy', [$p('v', 'int')], 'void', 'forward(' . $var('v') . ');')
		. $fn('live', [$p('a', 'int', true), $p('b', 'int')], 'void', 'echo ' . $var('a') . '; echo ' . $var('b') . ';')
		. $fn('snapshot', [$p('a', 'int'), $p('b', 'int')], 'void', 'echo ' . $var('a') . '; echo ' . $var('b') . ';')
		. $fn('alias', [$p('a', 'int', true), $p('b', 'int', true)], 'void', $var('a') . '=6; ' . $var('b') . '=7; echo ' . $var('a') . ';')
		. $fn('text', [$p('s', 'string', true)], 'string', $var('s') . '="new"; return ' . $var('s') . ';')
		. $fn('optional', [$p('v', 'int', true), $p('n', 'int') . '=8'], 'void', $var('v') . '=' . $var('n') . ';');
	$code = $defs . $decl('x', 'int64', '8') . 'value_copy(' . $var('x') . '); echo ' . $var('x') . ';'
		. 'forward(' . $var('x') . '); echo ' . $var('x') . ';'
		. $var('x') . '=8; live(' . $var('x') . ',change(' . $var('x') . '));'
		. $var('x') . '=8; snapshot(' . $var('x') . ',change(' . $var('x') . '));'
		. 'alias(' . $var('x') . ',' . $var('x') . ');'
		. $decl('s', 'string', '"old"') . $decl('copy', 'string', 'text(' . $var('s') . ')')
		. $var('s') . '="later"; echo ' . $var('copy') . '; echo ' . $var('s') . ';'
		. 'global ' . $decl('g', 'int', '1') . 'forward(' . $var('g') . '); echo ' . $var('g') . ';'
		. 'optional(' . $var('x') . '); return ' . $var('x') . ';';
	$entry = $prepare($code);
	$parameter = $entry->file->functions()[0]->parameters[0];
	verify($parameter->mode === passing_mode::reference, 'Missing passing mode');
	verify($parameter->root()->tokens->text_at($parameter->name_position()) === $var('v'), 'Reference parameter name position');
	$before = serialize($entry);
	$cpp = (new Cpp_Emitter())->emit($entry);
	verify(serialize($entry) === $before, 'Lowering mutated preparation');
	$fixtures[$flavor->name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('812125857newlater12')];
	foreach ($entry->file->functions() as $function) {
		Function_Preparation::reset($function);
	}
	Function_Preparation::reset_body($entry->file->main()->body());
	$again = (new Preparation())->prepare($entry->file, new entry_target(31));
	verify((new Cpp_Emitter())->emit($again) === $cpp, 'Reset changed reference lowering');
	// Whole objects of every currently supported type bind without conversion.
	$all_types = '';
	foreach (['int' => '8', 'bool' => 'false', 'byte' => '8', 'float' => '1.5', 'string' => '"S"', 'null' => 'null'] as $type => $literal) {
		$all_types .= $fn('bind_' . $type, [$p('v', $type, true)], $type, 'return ' . $var('v') . ';')
			. $decl('v_' . $type, $type, $literal) . 'bind_' . $type . '(' . $var('v_' . $type) . ');';
	}
	$fixtures[$flavor->name . '_types'] = ['cpp' => (new Cpp_Emitter())->emit($prepare($all_types . 'return 8;')), 'stdout_hex' => ''];
	foreach ([
		[$defs . 'change(8); return 8;', 'stable variable storage'],
		[$defs . $decl('x', 'int', '8') . 'change(change(' . $var('x') . ')); return 8;', 'stable variable storage'],
		[$defs . $decl('x', 'byte', '8') . 'change(' . $var('x') . '); return 8;', 'exact storage type'],
		[$fn('bad', [$p('x', 'int', true) . '=8'], 'void', '') . 'return 8;', 'Reference parameter defaults'],
		[$fn('bad', [$p('x', 'const int', true)], 'void', '') . 'return 8;', 'Expected'],
	] as [$invalid, $message]) {
		rejects(fn() => $prepare($invalid), $message);
	}
	// A changed local and two aliased reference parameters must not reuse old range proofs.
	$set = $fn('large', [$p('v', 'int', true)], 'int', $var('v') . '=300; return 1;');
	$failures = [
		'stale_local' => $set . $decl('x', 'int', '8') . 'large(' . $var('x') . ');' . $decl('b', 'byte', $var('x')),
		'aliased_parameters' => $fn('bad', [$p('a', 'int', true), $p('b', 'int', true)], 'void',
			$var('a') . '=8;' . $var('b') . '=300;' . $decl('narrow', 'byte', $var('a')))
			. $decl('x', 'int', '1') . 'bad(' . $var('x') . ',' . $var('x') . ');',
	];
	foreach ($failures as $label => $source) {
		$fixtures[$flavor->name . '_' . $label] = ['cpp' => (new Cpp_Emitter())->emit($prepare($source . 'echo "WRONG"; return 0;')),
			'stdout_hex' => '', 'catch_out_of_range' => true];
	}
	// A later known assignment restores a local range fact after an unknown reference write.
	$restored = $prepare($set . $decl('x', 'int', '8') . 'large(' . $var('x') . ');' . $var('x') . '=8;'
		. $decl('b', 'byte', $var('x')) . 'return 8;');
	verify($restored->body()->locals[3]->initializer->constant_known(), 'Known assignment did not restore local facts');

	$directory = sys_get_temp_dir() . '/scpp-references-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try {
		file_put_contents($directory . '/module.json', '{}');
		file_put_contents($directory . '/main.' . $flavor->name, $decl('x', 'int', '8') . 'f(' . $var('x') . '); return ' . $var('x') . ';');
		$lib = $directory . '/lib.' . $flavor->name;
		$write = static function (bool $ref, string $body) use ($lib, $fn, $p): void {
			file_put_contents($lib, $fn('f', [$p('v', 'int', $ref)], 'void', $body));
		};
		$write(false, '');
		$compiler = new Project_Compiler();
		$session = $compiler->frontend($directory);
		$target = new entry_target(31);
		Incremental_Preparation::prepare($session, $target);
		$caller = $session->entry->entry->ast;
		$symbol = $session->scope->entry('func:f');
		verify($caller->main()->body()->statements[2]->value->constant_known(), 'Value call lost local fact');
		$write(true, '');
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['prepared'] === 2, 'Passing-mode change did not invalidate caller');
		verify($session->entry->entry->ast === $caller && $session->scope->entry('func:f') === $symbol, 'Mode edit replaced caller or symbol');
		verify(!$caller->main()->body()->statements[2]->value->constant_known(), 'Reference call retained local constant');
		$write(true, $var('v') . '=12;');
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['reused'] === 1, 'Body-only edit invalidated caller');
		$write(false, '');
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['prepared'] === 2, 'Mode repair failed');
		verify($caller->main()->body()->statements[2]->value->constant_known(), 'Value-mode repair lost fact');
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['reused'] === 2, 'No-op rebuilt');
		file_put_contents($directory . '/main.' . $flavor->name, 'f(8); return 8;');
		$session = $compiler->frontend($directory, $session);
		Incremental_Preparation::prepare($session, $target);
		$literal_caller = $session->entry->entry->ast;
		$write(true, '');
		$session = $compiler->frontend($directory, $session);
		rejects(fn() => Incremental_Preparation::prepare($session, $target), 'stable variable storage');
		$write(false, '');
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['prepared'] === 2, 'Invalid reference binding did not recover');
		verify($session->entry->entry->ast === $literal_caller, 'Binding recovery reparsed caller');
	}
	finally {
		foreach (glob($directory . '/*') as $path) {
			unlink($path);
		}
		rmdir($directory);
	}
}
$multi = (new Program_Frontend())->prepare_sources('main', [
	'main' => new source_file('main', '$s string="old"; relay($s); echo $s; return 8;', flavor::php),
	'cpp' => new source_file('cpp', 'void relay(string &value) { replace(value); }', flavor::cpp),
	'js' => new source_file('js', 'function replace(&value: string): void { value="cross"; }', flavor::js),
], new entry_target(31));
$native_files = Cpp_Program::emit($multi)->files;
$fixtures['multifile'] = ['files' => $native_files, 'stdout_hex' => bin2hex('cross')];
if (isset($argv[1])) {
	file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n");
}
echo "Mutable reference parameters, exact bindings, aliases, snapshots and incremental modes passed\n";
