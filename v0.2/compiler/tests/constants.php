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

/** Required type spelling is flavor-specific; names are never dollar-prefixed. */
function constant_decl(flavor $flavor, string $name, string $type, string $literal): string
{
	return $flavor === flavor::js ? "const $name: $type=$literal;" : "const $type $name=$literal;";
}

$fixtures = [];
foreach (flavor::cases() as $flavor)
{
	$var = static fn(string $name): string => ($flavor === flavor::php ? '$' : '') . $name;
	$fn = static fn(string $name, string $parameters, string $type, string $body): string => definition($flavor, $name, $parameters, $type, $body);
	$decl = static fn(string $name, string $type, string $value): string => local($flavor, $name, $type, $value);
	$const = static fn(string $name, string $type, string $value): string => constant_decl($flavor, $name, $type, $value);
	$prepare = static fn(string $source) => (new Source_Frontend())->prepare(new source_file('constants', $source, $flavor), new entry_target(31));
	$code = 'echo LIMIT; echo TEXT;'
		. $const('LIMIT', 'int', '8') . $const('TEXT', 'string', '"a\0b"')
		. $const('FLAG', 'bool', '1') . $const('RATE', 'float', '-1.5') . $const('BYTE', 'byte', '8')
		. $const('NIL', 'null', 'null') . $const('MIN', 'int64', '-9223372036854775808')
		. $const('NARROW', 'int', '8.75')
		. $fn('label', '', 'string', 'return TEXT;')
		. $decl('b', 'byte', 'BYTE') . $decl('n', 'null', 'NIL')
		. 'if (FLAG && RATE && (BYTE == ' . $var('b') . ')) { echo "Y"; }'
		. 'echo MIN; echo label(); echo NARROW; return LIMIT;';
	$entry = $prepare($code);
	verify(count($entry->file->constants()) === 8, 'Constant inventory');
	verify($entry->body()->statements[0]->value->constant() === 8, 'Constant occurrence fact');
	$first = $entry->file->constants()[0];
	verify(($first->parent === $entry->file) && ($first->initializer->parent === $first), 'Constant ownership');
	verify($first->symbol_key() === 'const:LIMIT', 'Constant identity');
	$before = serialize($entry);
	$cpp = (new Cpp_Emitter())->emit($entry);
	verify(serialize($entry) === $before, 'Lowering mutated facts');
	verify(count(Syntax_Inspection::file($entry->file)['constants']) === 8, 'Syntax inspection');
	verify(count(Compilation_Inspection::program($entry)['constants']) === 8, 'Prepared inspection');
	$fixtures[$flavor->name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex("8a\0bY-9223372036854775808a\0b8")];
	Function_Preparation::reset_body($entry->body());
	$again = (new Preparation())->prepare($entry->file, new entry_target(31));
	verify((new Cpp_Emitter())->emit($again) === $cpp, 'Reset/reprepare differs');
	$ref = match ($flavor) {
		flavor::php => 'int &$v',
		flavor::cpp => 'int &v',
		flavor::js => '&v: int',
	};
	rejects(fn() => $prepare($const('VALUE', 'int', '8') . $fn('mutate', $ref, 'void', '') . 'mutate(VALUE); return 8;'), 'Constant cannot bind');
	if ($flavor !== flavor::php) {
		rejects(fn() => $prepare($const('VALUE', 'int', '8') . 'VALUE=9; return 8;'), 'Cannot assign to a constant');
	}
	foreach (['1+2', 'OTHER', 'read()'] as $bad) {
		rejects(fn() => $prepare($const('VALUE', 'int', $bad) . 'return 8;'), 'Constant initializer requires a literal');
	}
	rejects(fn() => $prepare($const('VALUE', 'byte', '256') . 'return 8;'), 'range');
	rejects(fn() => $prepare($const('VALUE', 'int', '9223372036854775808') . 'return 8;'), 'range');
	rejects(fn() => $prepare($const('VALUE', 'vector', '8') . 'return 8;'), 'Unknown or unsupported constant type');
	rejects(fn() => $prepare($fn('bad', '', 'void', $const('VALUE', 'int', '8')) . 'return 8;'), 'Constant declarations are only allowed');
	rejects(fn() => $prepare($const('VALUE', 'int', '8') . $const('VALUE', 'int', '9') . 'return 8;'), 'Duplicate constant');
	// A PHP variable spelling stays separate; cpp/js locals shadow bare constant references.
	$shadow = $const('VALUE', 'int', '8') . $decl('VALUE', 'int', '9')
		. 'echo ' . $var('VALUE') . '; return 8;';
	$fixtures[$flavor->name . '_shadow'] = ['cpp' => (new Cpp_Emitter())->emit($prepare($shadow)), 'stdout_hex' => bin2hex('9')];
	$global = match ($flavor) {
		flavor::php => 'global $VALUE int=9;',
		flavor::cpp => 'global int VALUE=9;',
		flavor::js => 'global let VALUE: int=9;',
	};
	$coexist = $const('VALUE', 'int', '8') . $global . 'echo ' . $var('VALUE') . '; return 8;';
	if ($flavor === flavor::php) {
		$coexist = $const('VALUE', 'int', '8') . $global . 'echo $VALUE; echo VALUE; return 8;';
	}
	$fixtures[$flavor->name . '_global'] = ['cpp' => (new Cpp_Emitter())->emit($prepare($coexist)),
		'stdout_hex' => bin2hex($flavor === flavor::php ? '98' : '9')];
	$sep = match ($flavor) {
		flavor::php => '\\',
		flavor::cpp => '::',
		flavor::js => '.',
	};
	$root = $flavor === flavor::cpp ? '::' : '\\';
	$library = 'namespace Lib {' . $const('VALUE', 'int', '8') . $const('TEXT', 'string', '"ok"') . '}';
	$main = 'namespace App { use const Lib' . $sep . '{VALUE as LIMIT, TEXT};'
		. 'echo TEXT; echo ' . $root . 'Lib' . $sep . 'VALUE; return LIMIT; }';
	$program = (new Program_Frontend())->prepare_sources('main', [
		'main' => new source_file('main', $main, $flavor), 'lib' => new source_file('lib', $library, $flavor),
	], new entry_target(31));
	$native_files = Cpp_Program::emit($program)->files;
	$fixtures[$flavor->name . '_imports'] = ['files' => $native_files, 'stdout_hex' => bin2hex('ok8')];
	$plain = str_replace('use const ', 'use ', $main);
	$plain_program = (new Program_Frontend())->prepare_sources('main', [
		'main' => new source_file('main', $plain, $flavor), 'lib' => new source_file('lib', $library, $flavor),
	], new entry_target(31));
	verify(Cpp_Program::emit($plain_program)->files === Cpp_Program::emit($program)->files, 'Plain symbol imports differ');
	rejects(fn() => $prepare('echo UNKNOWN; return 8;'), 'Unknown');
	rejects(fn() => $prepare($const('VALUE', 'int', '"bad"') . 'return 8;'), 'conversion');
	$duplicates = (new Source_Frontend())->collect(new source_file('duplicates',
		$const('VALUE', 'int', '1') . $const('VALUE', 'int', '2') . $const('VALUE', 'int', '3') . 'return 8;', $flavor));
	verify(count($duplicates->diagnostics) === 2, 'All duplicate diagnostics must be retained');
	$both_sources = [
		'main' => new source_file('main', 'use Lib' . $sep . 'VALUE as BOTH; echo BOTH; echo BOTH(); return 8;', $flavor),
		'lib' => new source_file('lib', 'namespace Lib {' . $const('VALUE', 'int', '8') . $fn('VALUE', '', 'int', 'return 9;') . '}', $flavor),
	];
	$both = (new Program_Frontend())->prepare_sources('main', $both_sources, new entry_target(31));
	verify($both->entry->body()->statements[0]->value->constant() === 8, 'Plain import lost constant kind');
	verify($both->entry->body()->statements[1]->value->function_def()->short_name === 'VALUE', 'Plain import lost function kind');
	$conflict = 'use const Lib' . $sep . '{VALUE as SAME,TEXT as SAME}; return 8;';
	rejects(fn() => (new Program_Frontend())->prepare_sources('main', [
		'main' => new source_file('main', $conflict, $flavor), 'lib' => new source_file('lib', $library, $flavor),
	], new entry_target(31)), 'Conflicting constant import');
	$directory = sys_get_temp_dir() . '/scpp-constants-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try {
		file_put_contents($directory . '/module.json', '{}');
		file_put_contents($directory . '/main.' . $flavor->name, 'use const VALUE as READ; echo READ; return 8;');
		$lib = $directory . '/lib.' . $flavor->name;
		$compiler = new Project_Compiler();
		$target = new entry_target(31);
		file_put_contents($lib, $const('VALUE', 'int', '8'));
		$session = $compiler->frontend($directory);
		Incremental_Preparation::prepare($session, $target);
		$caller = $session->entry->entry->ast;
		$tokens = $caller->tokens;
		$handle = $session->scope->entry('const:VALUE');
		file_put_contents($lib, $const('VALUE', 'int', '888'));
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['prepared'] === 1, 'Value change did not invalidate reader');
		verify($caller->main()->body()->statements[0]->value->constant() === 888, 'Stale constant value');
		file_put_contents($lib, $const('VALUE', 'bool', 'false'));
		$session = $compiler->frontend($directory, $session);
		rejects(fn() => Incremental_Preparation::prepare($session, $target), 'Echo currently supports');
		file_put_contents($lib, $const('VALUE', 'int64', '8'));
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['prepared'] === 1, 'Type recovery');
		file_put_contents($lib, '');
		$session = $compiler->frontend($directory, $session);
		rejects(fn() => Incremental_Preparation::prepare($session, $target), 'Unknown');
		verify($handle->declaration() === null, 'Removed constant not tombstoned');
		file_put_contents($lib, $const('VALUE', 'int', '88'));
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['prepared'] === 1, 'Removal recovery');
		verify(($session->scope->entry('const:VALUE') === $handle) && ($handle->declaration() !== null), 'Stable handle changed');
		verify(($session->entry->entry->ast === $caller) && ($caller->tokens === $tokens), 'Caller syntax replaced');
		file_put_contents($lib, $const('VALUE', 'int', '88') . $const('OTHER', 'int', '999'));
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['reused'] === 1, 'Unrelated constant invalidated reader');
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['reused'] === 1, 'No-op rebuilt');
	}
	finally {
		foreach (glob($directory . '/*') as $path) {
			unlink($path);
		}
		rmdir($directory);
	}
}
$program = (new Program_Frontend())->prepare_sources('main', [
	'main' => new source_file('main', 'use const Lib\{VALUE,TEXT}; echo TEXT; return VALUE;', flavor::php),
	'int' => new source_file('int', 'namespace Lib { const int VALUE=8; }', flavor::cpp),
	'text' => new source_file('text', 'namespace Lib { const TEXT: string="mixed"; }', flavor::js),
], new entry_target(31));
$native_files = Cpp_Program::emit($program)->files;
$fixtures['mixed'] = ['files' => $native_files, 'stdout_hex' => bin2hex('mixed')];

if (isset($argv[1])) {
	file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n");
}
echo "Typed literal constants, imports, immutable values and incremental identity passed\n";
