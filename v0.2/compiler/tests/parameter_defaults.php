<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';

use scpp\compiler\{flavor, source_file, Source_Frontend, entry_target, Cpp_Emitter, diagnostic, diagnostic_group,
	Project_Compiler, Incremental_Preparation, Function_Preparation, Preparation, Program_Frontend, Cpp_Program};

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

function parameter(flavor $flavor, string $name, string $type, ?string $default = null): string
{
	return match ($flavor) {
		flavor::php => "$type $$name",
		flavor::cpp => "$type $name",
		flavor::js => "$name: $type",
	} . ($default === null ? '' : '=' . $default);
}

/** Require a source diagnostic instead of accepting an invalid default or call. */
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
	$p = static fn(string $name, string $type, ?string $default = null): string => parameter($flavor, $name, $type, $default);
	$fn = static fn(string $name, array $parameters, string $type, string $body): string => definition($flavor, $name, $parameters, $type, $body);
	$prepare = static fn(string $source) => (new Source_Frontend())->prepare(new source_file('defaults', $source, $flavor), new entry_target(31));
	$code = $fn('print', [$p('a', 'string'), $p('b', 'int', '8'), $p('c', 'string', '"!"')], 'void',
		'echo ' . $var('a') . '; echo ' . $var('b') . '; echo ' . $var('c') . ';')
		. $fn('mutable', [$p('text', 'string', '"A\\0B"')], 'void', 'echo ' . $var('text') . '; ' . $var('text') . '="changed";')
		. $fn('mark', [$p('n', 'int')], 'int', 'echo ' . $var('n') . '; return ' . $var('n') . ';')
		. $fn('pair', [$p('a', 'int', '1'), $p('b', 'int', '2')], 'int', 'return ' . $var('b') . ';');
	foreach (['int' => '8', 'int64' => '9', 'bool' => '1', 'byte' => '255', 'float' => '8', 'float64' => '-0.0', 'string' => '"S"', 'null' => 'null'] as $type => $default) {
		$code .= $fn('default_' . $type, [$p('v', $type, $default)], $type, 'return ' . $var('v') . ';');
	}
	$code .= 'print("R"); print("X",9); print("Y",10,"?"); mutable(); mutable();'
		. 'echo pair(mark(3)); echo pair(mark(4),mark(5)); echo default_int64(); echo default_string();'
		. 'default_bool(); default_byte(); default_float(); default_float64(); default_null(); return default_int();';
	$entry = $prepare($code);
	$parameter = $entry->file->functions()[0]->parameters[1];
	verify($parameter->default_value->parent === $parameter, 'Default is not parameter-owned');
	verify(!$parameter->constant_known(), 'Default incorrectly constant-folded parameter reads');
	$before = serialize($entry);
	$cpp = (new Cpp_Emitter())->emit($entry);
	verify(serialize($entry) === $before, 'Lowering mutated facts');
	$fixtures[$flavor->name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex("R8!X9!Y10?A\0BA\0B324559S")];
	foreach ($entry->file->functions() as $function) {
		Function_Preparation::reset($function);
	}
	Function_Preparation::reset_body($entry->file->main()->body());
	$again = (new Preparation())->prepare($entry->file, new entry_target(31));
	verify((new Cpp_Emitter())->emit($again) === $cpp, 'Reset changed defaults');
	foreach ([
		[$fn('bad', [$p('a', 'int', '1'), $p('b', 'int')], 'void', ''), 'Required parameter follows'],
		[$fn('bad', [$p('a', 'byte', '256')], 'void', ''), 'byte range'],
		[$fn('bad', [$p('a', 'string', '1')], 'void', ''), 'conversion'],
		[$fn('bad', [$p('a', 'int', 'null')], 'void', ''), 'conversion'],
		[$fn('bad', [$p('a', 'float', '1e999')], 'void', ''), 'finite range'],
		[$fn('bad', [$p('a', 'int', 'pair()')], 'void', ''), 'require literals'],
		[$fn('bad', [$p('a', 'int', $var('other'))], 'void', ''), 'require literals'],
		[$fn('print', [$p('a', 'int'), $p('b', 'int', '8')], 'void', '') . 'print();', 'Argument count mismatch'],
		[$fn('pair', [$p('a', 'int', '1'), $p('b', 'int', '2')], 'void', '') . 'pair(1,2,3);', 'Argument count mismatch'],
	] as [$invalid, $message]) {
		rejects(fn() => $prepare($invalid . 'return 8;'), $message);
	}
	// Even a supplied argument cannot hide an invalid declaration default.
	rejects(fn() => $prepare($fn('bad', [$p('a', 'byte', '256')], 'void', '') . 'bad(1); return 8;'), 'byte range');

	$directory = sys_get_temp_dir() . '/scpp-defaults-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try {
		file_put_contents($directory . '/module.json', '{}');
		$main = $directory . '/main.' . $flavor->name;
		$lib = $directory . '/lib.' . $flavor->name;
		file_put_contents($main, 'return result();');
		$write = static function (?string $default, string $body = '') use ($lib, $fn, $p, $var): void {
			file_put_contents($lib, $fn('result', [$p('n', 'int', $default)], 'int', $body . 'return ' . $var('n') . ';'));
		};
		$write('8');
		$compiler = new Project_Compiler();
		$session = $compiler->frontend($directory);
		$target = new entry_target(31);
		verify(Incremental_Preparation::prepare($session, $target)['prepared'] === 2, 'Initial preparation');
		$caller = $session->entry->entry->ast;
		$symbol = $session->scope->entry('func:result');
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['reused'] === 2, 'No-op rebuilt');
		$write('123');
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['prepared'] === 2, 'Default edit retained stale caller');
		verify($session->entry->entry->ast === $caller, 'Default edit reparsed unchanged caller');
		verify($session->scope->entry('func:result') === $symbol, 'Default edit replaced stable symbol');
		verify($symbol->declaration()->parameters[0]->default_value->constant() === 123, 'Stale prepared default');
		$write(null);
		$session = $compiler->frontend($directory, $session);
		rejects(fn() => Incremental_Preparation::prepare($session, $target), 'Argument count mismatch');
		$write('8');
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['prepared'] === 2, 'Default repair failed');
		$call = $caller->main()->body()->statements[0]->value;
		verify($call->function_def() === $symbol->declaration(), 'Repaired call retained old declaration');
		verify($call->function_def()->parameters[0]->default_value->constant() === 8, 'Repair retained old default');
		$write('8', 'echo "";');
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['reused'] === 1, 'Body edit invalidated caller');
		verify($session->entry->entry->ast === $caller, 'Repair reparsed caller');
	}
	finally {
		foreach (glob($directory . '/*') as $path) {
			unlink($path);
		}
		rmdir($directory);
	}
}
$multi = (new Program_Frontend())->prepare_sources('main', [
	'main' => new source_file('main', 'echo text(); return result();', flavor::php),
	'cpp' => new source_file('cpp', 'string text(string value="cross") { return value; }', flavor::cpp),
	'js' => new source_file('js', 'function result(value: int=8): int { return value; }', flavor::js),
], new entry_target(31));
$native_files = Cpp_Program::emit($multi)->files;
$fixtures['multifile'] = ['files' => $native_files, 'stdout_hex' => bin2hex('cross')];
if (isset($argv[1])) {
	file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n");
}
echo "Trailing literal defaults, generated conversions, all flavors and incremental edits passed\n";
