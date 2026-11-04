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

/** Require a source diagnostic for unsupported comparisons and invalidation failures. */
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
	$prepare = static fn(string $source) => (new Source_Frontend())->prepare(new source_file('arithmetic', $source, $flavor), new entry_target(31));
	$entry = $prepare('echo 2 + 3 * 4; return 8;');
	verify($entry->body()->expression_visits === 6, 'Arithmetic operands must each be prepared once');
	$code = '';
	$expected = '';
	foreach (['2 + 3 * 4' => 14, '(2 + 3) * 4' => 20, '20 - 5 - 3' => 12,
		'20 / 3' => 6, '20 % 3' => 2, '(0 - 20) / 3' => -6, '(0 - 20) % 3' => -2,
		'20 / (0 - 3)' => -6, '20 % (0 - 3)' => 2, '(0 - 20) / (0 - 3)' => 6,
		'24 / 3 / 2' => 4, '24 / (3 / 2)' => 24, '9223372036854775807 - 1' => 9223372036854775806,
		'(0 - 9223372036854775807 - 1) + 1' => -9223372036854775807] as $expression => $answer) {
		$code .= 'echo ' . $expression . '; echo ",";';
		$expected .= $answer . ',';
	}
	$params = match ($flavor) {
		flavor::php => 'int $a, int64 $b',
		flavor::cpp => 'int a, int64 b',
		flavor::js => 'a: int, b: int64',
	};
	$ref = match ($flavor) {
		flavor::php => 'int &$v',
		flavor::cpp => 'int &v',
		flavor::js => '&v: int',
	};
	$code .= $fn('sum', $params, 'int64', 'return ' . $var('a') . ' + ' . $var('b') . ';')
		. $fn('change', $ref, 'int', 'echo "R"; ' . $var('v') . '=9; return 2;')
		. $fn('left', '', 'int', 'echo "L"; return 3;')
		. $decl('x', 'int', '1') . 'echo ' . $var('x') . ' + change(' . $var('x') . ');'
		. 'echo left() * change(' . $var('x') . ');'
		. 'echo sum(2 + 3, 4 * 2);'
		. $var('x') . '=0; while (' . $var('x') . ' < 3) { echo ' . $var('x') . '; '
		. $var('x') . '=' . $var('x') . '+1; }'
		. 'do {' . $var('x') . '=' . $var('x') . '-1; } while (' . $var('x') . '>0);'
		. 'if (2 + 3 * 4 == 14) { echo "P"; } return 4 + 4;';
	$entry = $prepare($code);
	$before = serialize($entry);
	$cpp = (new Cpp_Emitter())->emit($entry);
	verify(serialize($entry) === $before, 'Lowering mutated facts');
	verify(str_contains($cpp, '#include <scpp/arithmetic.hpp>'), 'Missing arithmetic public include');
	verify(!str_contains((new Cpp_Emitter())->emit($prepare('return 4 + 4;')), 'scpp/arithmetic.hpp'), 'Plain arithmetic added runtime include');
	Syntax_Inspection::file($entry->file);
	Compilation_Inspection::body($entry->body());
	$fixtures[$flavor->name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex($expected . 'R3LR613012P')];
	Function_Preparation::reset_body($entry->body());
	$again = (new Preparation())->prepare($entry->file, new entry_target(31));
	verify((new Cpp_Emitter())->emit($again) === $cpp, 'Reset/reprepare differs');
	foreach (['1 + 1.0', '1.0 + 2.0', 'true + 1', 'null * 1', '"a" + "b"'] as $invalid) {
		rejects(fn() => $prepare('echo ' . $invalid . '; return 8;'), 'Unsupported binary');
	}
	rejects(fn() => $prepare($decl('b', 'byte', '1') . 'echo ' . $var('b') . '+' . $var('b') . '; return 8;'), 'Unsupported binary');
	rejects(fn() => $prepare($decl('x', 'int', $var('x') . '+1') . 'return 8;'), 'during its initialization');
	foreach (['/' => 'division_by_zero', '%' => 'modulo_by_zero'] as $operator => $error) {
		$failure = $fn('left', '', 'int', 'echo "L"; return 8;')
			. $fn('zero', '', 'int', 'echo "R"; return 0;')
			. 'echo left() ' . $operator . ' zero(); return 8;';
		$fixtures[$flavor->name . '_' . $error] = ['cpp' => (new Cpp_Emitter())->emit($prepare($failure)),
			'stdout_hex' => bin2hex('LR'), 'catch_domain_error' => $error];
		// Failed left expression must suppress evaluation of the outer right call.
		$failure = $fn('later', '', 'int', 'echo "WRONG"; return 2;')
			. 'echo (1 ' . $operator . ' 0) + later(); return 8;';
		$fixtures[$flavor->name . '_' . $error . '_left'] = ['cpp' => (new Cpp_Emitter())->emit($prepare($failure)),
			'stdout_hex' => '', 'catch_domain_error' => $error];
	}
	$directory = sys_get_temp_dir() . '/scpp-arithmetic-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try {
		file_put_contents($directory . '/module.json', '{}');
		file_put_contents($directory . '/main.' . $flavor->name, 'if (value() + 2 == 10) { echo "Y"; } return 8;');
		$lib = $directory . '/lib.' . $flavor->name;
		$write = static function (string $type, string $value) use ($lib, $fn): void {
			file_put_contents($lib, $fn('value', '', $type, 'return ' . $value . ';'));
		};
		$write('int', '8');
		$compiler = new Project_Compiler();
		$session = $compiler->frontend($directory);
		$target = new entry_target(31);
		Incremental_Preparation::prepare($session, $target);
		$caller = $session->entry->entry->ast;
		$write('float', '8.0');
		$session = $compiler->frontend($directory, $session);
		rejects(fn() => Incremental_Preparation::prepare($session, $target), 'Unsupported binary');
		verify($session->entry->entry->ast === $caller, 'Caller reparsed after signature change');
		$write('int64', '8');
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['prepared'] === 2, 'Arithmetic recovery failed');
		verify($session->entry->entry->ast === $caller, 'Caller reparsed on recovery');
		$write('int64', '800');
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['reused'] === 1, 'Body-only edit invalidated caller');
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
$multi = (new Program_Frontend())->prepare_sources('main', [
	'main' => new source_file('main', 'echo value() * 2; return 4 + 4;', flavor::php),
	'cpp' => new source_file('cpp', 'int64 value() { return 2 + 3; }', flavor::cpp),
], new entry_target(31));
$units = array_values(Cpp_Program::emit($multi)->files);
$fixtures['multifile'] = ['cpp' => array_pop($units), 'extra_cpp' => $units, 'stdout_hex' => bin2hex('10')];
if (isset($argv[1])) {
	file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n");
}
echo "Generated int64 arithmetic, ordered failures, single-pass preparation and incremental recovery passed\n";
