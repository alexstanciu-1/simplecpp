<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
require_once __DIR__ . '/../host/syntax.php';
require_once __DIR__ . '/../host/compilation.php';

use scpp\compiler\{flavor, source_file, Source_Frontend, entry_target, Cpp_Emitter, diagnostic, diagnostic_group,
	Program_Frontend, Cpp_Program, Function_Preparation, Preparation, Syntax_Inspection, Compilation_Inspection,
	Project_Compiler, Incremental_Preparation, ast_parameter_declaration, ast_call_expression, value_conversion};

function verify(bool $ok, string $message): void
{
	if (!$ok) {
		throw new RuntimeException($message);
	}
}

/** @param string[] $types */
function function_text(flavor $flavor, string $name, array $types /** list<string> */, string $result, string $body): string
{
	$parameters = [];
	foreach ($types as $index => $type) {
		$parameters[] = match ($flavor) {
			flavor::php => $type . ' $p' . $index,
			flavor::cpp => $type . ' p' . $index,
			flavor::js => 'p' . $index . ': ' . $type,
		};
	}
	$parameters = implode(', ', $parameters);
	return ($flavor === flavor::cpp ? "$result $name($parameters)" : "function $name($parameters): $result") . '{' . $body . '}';
}

function local(flavor $flavor, string $name, string $value, ?string $type = null): string
{
	return match ($flavor) {
		flavor::php => '$' . $name . ($type === null ? '' : ' ' . $type) . "=$value;",
		flavor::cpp => ($type ?? 'auto') . " $name=$value;",
		flavor::js => 'let ' . $name . ($type === null ? '' : ': ' . $type) . "=$value;",
	};
}

/** Require source diagnostics for invalid function contracts. */
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
	$fn = static fn(string $name, array $types, string $result, string $body): string => function_text($flavor, $name, $types, $result, $body);
	$var = static fn(string $name): string => ($flavor === flavor::php ? '$' : '') . $name;
	$decl = static fn(string $name, string $value, ?string $type = null): string => local($flavor, $name, $value, $type);
	$prepare = static fn(string $code) => (new Source_Frontend())->prepare(new source_file('typed', $code, $flavor), new entry_target(31));
	$definitions = '';
	foreach (['int', 'int64', 'bool', 'byte', 'float', 'float64', 'string', 'null'] as $type) {
		$definitions .= $fn('id_' . $type, [$type], $type, 'return ' . $var('p0') . ';');
	}
	$definitions .= $fn('mark', ['int'], 'int', 'echo ' . $var('p0') . '; return ' . $var('p0') . ';')
		. $fn('choose', ['int', 'int'], 'int', 'return ' . $var('p1') . ';')
		. $fn('change', ['string'], 'string', $var('p0') . '="new"; return ' . $var('p0') . ';')
		. $fn('print_string', [], 'string', 'echo "S"; return "N\\0W";')
		. $fn('byte_return', ['int'], 'byte', 'return ' . $var('p0') . ';')
		. $fn('update', ['int'], 'int', $var('p0') . '=8; return ' . $var('p0') . ';')
		. $fn('discard', ['null'], 'void', 'return;');
	$source = $definitions . 'echo choose(mark(1),mark(2));'
		. $decl('b', 'id_bool(1)') . $decl('bi', $var('b'), 'int') . 'echo ' . $var('bi') . ';'
		. $decl('byte', 'id_byte(9)') . $decl('bytei', $var('byte'), 'int') . 'echo ' . $var('bytei') . ';'
		. $decl('f', 'id_float64(id_float(8))') . $decl('fi', $var('f'), 'int') . 'echo ' . $var('fi') . ';'
		. $decl('n', 'id_null(null)', 'null') . 'discard(' . $var('n') . ');'
		. $decl('original', '"old"') . $decl('changed', 'change(' . $var('original') . ')')
		. 'echo ' . $var('original') . '; echo ' . $var('changed') . '; echo print_string();'
		. $decl('retbyte', 'byte_return(8)') . $decl('reti', $var('retbyte'), 'int')
		. 'id_string("discarded"); return id_int64(update(' . $var('reti') . '));';
	$entry = $prepare($source);
	$first = $entry->file->functions()[0];
	verify($first->parameters[0] instanceof ast_parameter_declaration, 'Missing parameter model');
	verify($first->parameters[0]->parent === $first, 'Parameter not signature-owned');
	verify($first->body()->scope->resolve('var:p0') === $first->parameters[0], 'Parameter not published into body scope');
	verify($first->body()->statements[0]->value->local_def() === $first->parameters[0], 'Read does not bind parameter');
	verify($entry->body()->statements[0]->value instanceof ast_call_expression, 'Call is not a shared value expression');
	verify(count(Syntax_Inspection::file($entry->file)['functions'][0]['parameters']) === 1, 'Missing parameter inspection');
	Compilation_Inspection::program($entry);
	$before = serialize($entry);
	$cpp = (new Cpp_Emitter())->emit($entry);
	verify(serialize($entry) === $before, 'Lowering mutated facts');
	$fixtures[$flavor->name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex("122198oldnewSN\0W")];
	Function_Preparation::reset_body($entry->body());
	$again = (new Preparation())->prepare($entry->file, new entry_target(31));
	verify((new Cpp_Emitter())->emit($again) === $cpp, 'Nested call reset differs');
	$prepare($fn('recursive', ['int'], 'int', 'return recursive(' . $var('p0') . ');') . 'return 8;');
	foreach ([
		[$fn('bad', ['void'], 'void', '') . 'return 8;', 'parameter type'],
		[$fn('bad', ['Unknown'], 'void', '') . 'return 8;', 'parameter type'],
		[$fn('bad', [], 'Unknown', '') . 'return 8;', 'return type'],
		[$fn('bad', [], 'int', '') . 'return 8;', 'requires a value return'],
		[$fn('bad', [], 'int', 'return;') . 'return 8;', 'requires a value return'],
		[$fn('bad', [], 'void', 'return 8;') . 'return 8;', 'cannot return a value'],
		[$definitions . 'id_int(); return 8;', 'Argument count mismatch'],
		[$definitions . 'id_int(1,2); return 8;', 'Argument count mismatch'],
		[$definitions . 'id_byte(256); return 8;', 'byte range'],
		[$definitions . 'id_string(8); return 8;', 'conversion'],
		[$definitions . $decl('bad', 'discard(null)') . 'return 8;', 'Void call'],
		[$definitions . $decl('bad', 'id_null(null)') . 'return 8;', 'Cannot infer'],
		[$fn('bad', ['int'], 'int', $decl('p0', '1', 'int') . 'return 8;') . 'return 8;', 'Duplicate local'],
	] as [$invalid, $message]) {
		rejects(fn() => $prepare($invalid), $message);
	}
	// A value-dependent first-argument conversion must fail before evaluating the second argument.
	$failure = 'global ' . $decl('large', '300', 'int')
		. $fn('first', [], 'int', 'echo "F"; return ' . $var('large') . ';')
		. $fn('later', [], 'int', 'echo "L"; return 1;')
		. $fn('consume', ['byte', 'int'], 'void', 'echo "C";')
		. 'consume(first(),later()); return 0;';
	$fixtures[$flavor->name . '_failure'] = ['cpp' => (new Cpp_Emitter())->emit($prepare($failure)),
		'stdout_hex' => bin2hex('F'), 'catch_out_of_range' => true];
}
// One program crosses flavor and translation-unit boundaries with owning string and byte values.
$multi = (new Program_Frontend())->prepare_sources('main', [
	'main' => new source_file('main', 'echo pass("cross"); $r int=number(8); return $r;', flavor::php),
	'cpp' => new source_file('cpp', 'string pass(string text) { return relay(text); }', flavor::cpp),
	'js' => new source_file('js', 'function relay(text: string): string { return text; } function number(n: byte): byte { return n; }', flavor::js),
], new entry_target(31));
$bundle = Cpp_Program::emit($multi);
$fixtures['multifile'] = ['files' => $bundle->files, 'stdout_hex' => bin2hex('cross')];
// PHP follows the normative type-before-name signature; shorthand and deferred features reject.
$php = static fn(string $code) => (new Source_Frontend())->prepare(new source_file('php', $code, flavor::php), new entry_target(31));
foreach (['function f($x int): void {}', 'function f(int ...$x): void {}',
	'function f(int $x, int $x): void {}'] as $invalid) {
	rejects(fn() => $php($invalid . 'return 8;'), str_contains($invalid, 'int $x,') ? 'Duplicate local' : 'Expected');
}

$directory = sys_get_temp_dir() . '/scpp-typed-functions-' . bin2hex(random_bytes(8));
mkdir($directory);
try
{
	file_put_contents($directory . '/module.json', '{"entry":"main.php"}');
	file_put_contents($directory . '/main.php', '$x=f(8); $ret int=$x; return $ret;');
	file_put_contents($directory . '/lib.php', 'function f(int $value): int { return $value; }');
	$compiler = new Project_Compiler();
	$session = $compiler->frontend($directory);
	Incremental_Preparation::prepare($session, new entry_target(31));
	$caller = $session->entry->entry->ast;
	file_put_contents($directory . '/lib.php', 'function f(float $value): float { return $value; }');
	$session = $compiler->frontend($directory, $session);
	verify(Incremental_Preparation::prepare($session, new entry_target(31))['prepared'] === 2, 'Signature edit did not invalidate caller');
	verify($session->entry->entry->ast === $caller, 'Caller reparsed after signature edit');
	verify($caller->main()->body()->locals[0]->initializer->conversion(0) === value_conversion::numeric_floating, 'Stale argument conversion');
	file_put_contents($directory . '/lib.php', 'function f(float $value): float { return 8.75; }');
	$session = $compiler->frontend($directory, $session);
	verify(Incremental_Preparation::prepare($session, new entry_target(31))['reused'] === 1, 'Body-only edit invalidated caller');
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
echo "Typed signatures, value parameters/returns, nested calls, conversion boundaries and incremental contracts passed\n";
