<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
require_once __DIR__ . '/../host/syntax.php';
require_once __DIR__ . '/../host/compilation.php';

use scpp\compiler\{flavor, Source_Frontend, source_file, entry_target, Cpp_Emitter, type_registry, type_float64,
	type_int64, type_bool, value_conversion, diagnostic, diagnostic_group, Syntax_Inspection, Compilation_Inspection,
	Project_Compiler, Incremental_Preparation};

function verify(bool $condition, string $message): void
{
	if (!$condition) {
		throw new RuntimeException($message);
	}
}

/** Reject through source diagnostics, not PHP arithmetic errors or Clang failures. */
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

function variable(flavor $flavor, string $name): string
{
	return ($flavor === flavor::php ? '$' : '') . $name;
}

function declaration(flavor $flavor, string $type, string $name, string $value): string
{
	return match ($flavor) {
		flavor::cpp => "$type $name = $value;",
		flavor::php => '$' . "$name $type = $value;",
		flavor::js => "let $name: $type = $value;",
	};
}

verify(type_registry::resolve('float') === type_registry::resolve('float64'), 'Float alias identity');
verify(type_float64::instance()->cpp_type() === 'double', 'Float representation');
$literal_cases = [
	'0.0' => '0000000000000000', '-0.0' => '8000000000000000', '1e-400' => '0000000000000000',
	'-1e-400' => '8000000000000000', '5e-324' => '0000000000000001', '-5e-324' => '8000000000000001',
	'2.4703282292062327e-324' => '0000000000000000', '2.2250738585072014e-308' => '0010000000000000',
	'1.7976931348623157e308' => '7fefffffffffffff', '1.7976931348623158e308' => '7fefffffffffffff',
	'1.00000000000000011102230246251565404236316680908203125' => '3ff0000000000000',
	'1.00000000000000033306690738754696212708950042724609375' => '3ff0000000000002',
	'.5' => '3fe0000000000000', '+.5' => '3fe0000000000000', '10.' => '4024000000000000',
	'1E+3' => '408f400000000000', '08e0' => '4020000000000000',
];
$fixtures = [];
foreach (flavor::cases() as $flavor)
{
	$decl = static fn(string $type, string $name, string $value): string => declaration($flavor, $type, $name, $value);
	$var = static fn(string $name): string => variable($flavor, $name);
	$frontend = new Source_Frontend();
	$prepare = static fn(string $code) => $frontend->prepare(new source_file('floating', $code, $flavor), new entry_target(31));
	$code = '';
	foreach (array_keys($literal_cases) as $index => $literal) {
		$code .= $decl('float', 'value' . $index, $literal);
	}
	$entry = $prepare($code . 'return 8;');
	$locals = array_values($entry->body()->locals);
	foreach (array_values($literal_cases) as $index => $bits) {
		verify(bin2hex(pack('E', $locals[$index]->constant())) === $bits, 'Incorrect literal rounding at ' . $index);
		verify($locals[$index]->initializer->source_text() === array_keys($literal_cases)[$index], 'Source spelling lost');
	}
	$inspection = Syntax_Inspection::file($entry->file);
	verify($inspection['statements'][0]['initializer']['kind'] === 'floating', 'Float syntax inspection');
	verify(str_starts_with(Compilation_Inspection::program($entry)['statements'][1]['initializer']['constant'], '-0x0.'), 'Inspection lost signed zero');
	$before = serialize($entry);
	$literal_cpp = (new Cpp_Emitter())->emit($entry);
	verify($before === serialize($entry), 'Floating lowering mutated facts');
	$source = 'global ' . $decl('float', 'external', '8.75')
		. 'global ' . $decl('float64', 'tiny', '-1e-400')
		. 'global ' . $decl('int', 'global_int', '8.75')
		. $decl('float', 'from_int', '9007199254740993')
		. $decl('float', 'from_bool', 'true') . $decl('bool', 'from_zero', '-0.0')
		. $decl('bool', 'from_nonzero', '5e-324') . $decl('int', 'truncated', '-8.75')
		. $decl('int', 'checked', $var('external'))
		. $decl('bool', 'runtime_bool', $var('external')) . $decl('int', 'bool_int', $var('runtime_bool'))
		. 'echo ' . $var('truncated') . '; echo ' . $var('bool_int') . '; return ' . $var('checked') . ';';
	$converted = $prepare($source);
	$values = array_values($converted->body()->locals);
	verify($values[0]->constant() === 9007199254740992.0, 'Integer to float rounding');
	verify(($values[1]->constant() === 1.0) && ($values[2]->constant() === false) && ($values[3]->constant() === true), 'Boolean float conversions');
	verify($values[4]->constant() === -8, 'Truncation toward zero');
	verify($values[5]->initialization() === value_conversion::floating_integer_checked, 'Runtime check missing');
	verify(!$values[5]->constant_known(), 'Unknown storage incorrectly folded');
	$cpp = (new Cpp_Emitter())->emit($converted);
	verify(str_contains($cpp, '#include <scpp/values.hpp>'), 'Missing public float runtime boundary');
	foreach (['1e309', '-1e309', '1.7976931348623159e308'] as $literal) {
		rejects(fn() => $prepare($decl('float', 'bad', $literal) . 'return 8;'), 'finite range');
	}
	foreach (['9223372036854775808.0', '-9223372036854777856.0'] as $literal) {
		rejects(fn() => $prepare($decl('int', 'bad', $literal) . 'return 8;'), 'outside int64');
	}
	rejects(fn() => $prepare($decl('float', 'rounded', '9223372036854775807')
		. $decl('int', 'bad', $var('rounded')) . 'return 8;'), 'outside int64');
	$minimum = $prepare('global ' . $decl('int', 'low', '-9223372036854775808.0') . 'return 8;');
	verify($minimum->file->globals()[0]->type_def()->convert_constant(value_conversion::floating_integer_proven,
		$minimum->file->globals()[0]->initializer->constant()) === PHP_INT_MIN, 'Exact int64 lower boundary');
	rejects(fn() => $prepare($decl('byte', 'bad', '8.0') . 'return 8;'), 'conversion');
	rejects(fn() => $prepare($decl('byte', 'small', '8') . $decl('float', 'bad', $var('small')) . 'return 8;'), 'conversion');
	rejects(fn() => $prepare($decl('float', 'bad', 'null') . 'return 8;'), 'conversion');
	rejects(fn() => $prepare('return 8.0;'), 'requires int64');
	rejects(fn() => $prepare('echo 8.0; return 8;'), 'Echo currently');
	$literal = $values[0]->initializer;
	rejects(fn() => type_float64::instance()->prepare_conversion($literal, false), 'implicit conversion');
	$float_literal = $values[3]->initializer;
	$float_literal->reset_preparation();
	type_registry::prepare_literal($float_literal);
	verify(bin2hex(pack('E', $float_literal->constant())) === '0000000000000001', 'Reset lost subnormal');
	$fixtures[$flavor->value] = ['source' => $source, 'cpp' => $cpp, 'literal_cpp' => $literal_cpp,
		'bits' => array_values($literal_cases)];
}
verify(count(array_unique(array_column($fixtures, 'cpp'))) === 1, 'Floating lowering differs by flavor');

// Inferred global int -> float edits must replace the consumer's cached conversion without reparsing it.
$directory = sys_get_temp_dir() . '/scpp-floating-' . bin2hex(random_bytes(8));
mkdir($directory);
try
{
	file_put_contents($directory . '/module.json', '{"entry":"main.php"}');
	file_put_contents($directory . '/main.php', '$result int = $external; return $result;');
	file_put_contents($directory . '/lib.php', 'global $external = 8;');
	$compiler = new Project_Compiler();
	$session = $compiler->frontend($directory);
	Incremental_Preparation::prepare($session, new entry_target(31));
	$caller = $session->entry->entry->ast;
	file_put_contents($directory . '/lib.php', 'global $external = 8.75;');
	$session = $compiler->frontend($directory, $session);
	verify(Incremental_Preparation::prepare($session, new entry_target(31))['prepared'] === 1, 'Inferred float type did not invalidate');
	verify($session->entry->entry->ast === $caller, 'Unchanged caller reparsed');
	verify(array_values($caller->main()->body()->locals)[0]->initialization() === value_conversion::floating_integer_checked, 'Stale conversion after float edit');
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
echo "Floating literals, exact bits, range checks, conversions, purity and incremental invalidation passed\n";
