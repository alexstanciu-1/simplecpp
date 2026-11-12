<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
require_once __DIR__ . '/../host/syntax.php';
require_once __DIR__ . '/../host/compilation.php';

use scpp\compiler\{flavor, source_file, Source_Frontend, entry_target, Cpp_Emitter, diagnostic, diagnostic_group,
	Project_Compiler, Incremental_Preparation, Function_Preparation, Preparation, Program_Frontend, Cpp_Program,
	Syntax_Inspection, Compilation_Inspection, ast_assignment_statement, binary_operator, value_conversion, type_int64};

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
	$prepare = static fn(string $source) => (new Source_Frontend())->prepare(new source_file('compounds', $source, $flavor), new entry_target(31));
	$x = $var('x');
	$entry = $prepare($decl('x', 'int', '10') . $x . '+=3; return 8;');
	$write = $entry->body()->statements[1];
	verify(($write instanceof ast_assignment_statement) && ($write->operator === binary_operator::add), 'Shared assignment syntax');
	verify($write->value->parent === $write, 'RHS parent');
	verify($write->local_def() === $entry->body()->statements[0], 'One destination binding');
	verify(!$write->constant_known() && ($write->constant() === null), 'Compound result must not reuse RHS constant');
	verify($entry->body()->expression_visits === 3, 'One visit per RHS, no synthetic binary preparation');
	$direct = (new Cpp_Emitter())->emit($entry);
	verify(!str_contains($direct, '([&]') && !str_contains($direct, 'scpp/arithmetic.hpp'), 'Plain addition added a lambda or runtime include');
	$b = $var('b');
	foreach (['add' => ['+=', '<16', '1317'], 'subtract' => ['-=', '>4', '73'],
		'multiply' => ['*=', '<60', '3090']] as $operation => [$operator, $condition, $output]) {
		$plain = $decl('x', 'int', '10') . $decl('b', 'int', '3') . $x . $operator . $b . '; echo ' . $x . ';'
			. 'for (' . $x . $operator . '1;' . $x . $condition . ';' . $x . $operator . $b . '){continue;} echo ' . $x . '; return 8;';
		$plain_cpp = (new Cpp_Emitter())->emit($prepare($plain));
		verify(str_contains($plain_cpp, "var_x_0 $operator var_b_1;") && str_contains($plain_cpp, "for (var_x_0 $operator 1")
			&& str_contains($plain_cpp, "; var_x_0 $operator var_b_1)") && !str_contains($plain_cpp, 'compound_previous'),
			"Expected native $operator in the statement and both for clauses");
		$fixtures[$flavor->name . '_plain_' . $operation] = ['cpp' => $plain_cpp, 'stdout_hex' => bin2hex($output)];
	}
	// Isolate each operator so another helper cannot supply its include.
	foreach (['/=' => ['int64_divide', 3], '%=' => ['int64_remainder', 1], '<<=' => ['int64_shift_left', 80],
		'>>=' => ['int64_shift_right', 1], '**=' => ['int64_power', 1000]] as $operator => [$helper, $expected]) {
		$source = $decl('x', 'int', '10') . $decl('b', 'int', '3')
			. "$x $operator $b; if ($x !== $expected) { return 9; } return 8;";
		$cpp = (new Cpp_Emitter())->emit($prepare($source));
		verify(str_contains($cpp, "scpp::$helper("), "$flavor->name $operator must call $helper");
		verify(str_contains($cpp, '#include <scpp/arithmetic.hpp>'), "$flavor->name $operator missing arithmetic header");
		$fixtures[$flavor->name . '_' . $helper] = ['cpp' => $cpp, 'stdout_hex' => ''];
	}
	$code = $decl('x', 'int64', '10');
	foreach (['+=3', '-=2', '*=4', '/=3', '%=5'] as $operation) {
		$code .= $x . $operation . '; echo ' . $x . '; echo ",";';
	}
	$ref = match ($flavor) {
		flavor::php => 'int &$v',
		flavor::cpp => 'int &v',
		flavor::js => '&v: int',
	};
	// change writes 100 and returns 3. Each call must run once, after saving 10.
	$bitwise = $fn('change', $ref, 'int', $var('v') . '=100; echo "R"; return 3;')
		. $decl('x', 'int', '10') . $decl('b', 'int', '3');
	$expected = '';
	foreach (['&' => [2, 8], '|' => [11, 14], '^' => [9, 6]] as $operator => [$local_result, $literal_result]) {
		$bitwise .= $x . '=10;' . $x . $operator . '=' . $b . '; echo ' . $x . ';'
			. $x . '=10;' . $x . $operator . '=12; echo ' . $x . ';'
			. $x . '=10;' . $x . $operator . '=change(' . $x . '); echo ' . $x . ';';
		$expected .= $local_result . $literal_result . 'R' . $local_result;
	}
	$bitwise_cpp = (new Cpp_Emitter())->emit($prepare($bitwise . 'return 8;'));
	foreach (['&', '|', '^'] as $operator) {
		verify(str_contains($bitwise_cpp, "var_x_0 $operator= var_b_1;") && str_contains($bitwise_cpp, "var_x_0 $operator= 12"),
			"Expected native $operator= for a local or integer literal");
		verify(str_contains($bitwise_cpp, "{ const auto compound_previous = var_x_0; var_x_0 = (compound_previous $operator change_(var_x_0)); }"),
			"Expected a snapshot for $operator= before a mutating call");
	}
	$fixtures[$flavor->name . '_bitwise'] = ['cpp' => $bitwise_cpp, 'stdout_hex' => bin2hex($expected)];
	$param = match ($flavor) {
		flavor::php => 'int $v',
		flavor::cpp => 'int v',
		flavor::js => 'v: int',
	};
	$code .= $fn('change', $ref, 'int', $var('v') . '=100; echo "R"; return 3;')
		. $fn('bump', $ref, 'void', $var('v') . '+=2; return;')
		. $fn('copy', $param, 'int', $var('v') . '*=2; return ' . $var('v') . ';')
		. $x . '=10;' . $x . '*=change(' . $x . '); echo ' . $x . ';'
		. $x . '=10;' . $x . '-=change(' . $x . '); echo ' . $x . ';'
		. $x . '=10;' . $x . '+=change(' . $x . '); echo ' . $x . ';'
		. 'bump(' . $x . '); echo ' . $x . '; echo copy(' . $x . '); echo ' . $x . ';'
		. $x . '=3;' . $x . '+=' . $x . '; echo ' . $x . ';'
		. $x . '+= (int) true; echo ' . $x . ';'
		. $x . '=0; while (' . $x . '<2) {' . $x . '+=1; echo ' . $x . ';}'
		. 'do {' . $x . '-=1;} while (' . $x . '>0); echo ' . $x . ';'
		. $x . '=0-17;' . $x . '/=2; echo ' . $x . ';'
		. $x . '=0-17;' . $x . '%=2; echo ' . $x . ';'
		. 'if (true) {' . $decl('inner', 'int', '1') . $var('inner') . '+=1; echo ' . $var('inner') . ';}'
		. 'return 8;';
	$entry = $prepare($code);
	$before = serialize($entry);
	$cpp = (new Cpp_Emitter())->emit($entry);
	foreach (['+', '-', '*'] as $operator) {
		verify(str_contains($cpp, "{ const auto compound_previous = var_x_0; var_x_0 = (compound_previous $operator change_(var_x_0)); }"),
			"Expected a snapshot before a mutating call in $operator=");
	}
	verify(serialize($entry) === $before, 'Lowering mutated facts');
	Syntax_Inspection::file($entry->file);
	Compilation_Inspection::body($entry->body());
	$fixtures[$flavor->name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('13,11,44,14,4,R30R7R1315301567120-8-12')];
	Function_Preparation::reset_body($entry->body());
	$again = (new Preparation())->prepare($entry->file, new entry_target(31));
	verify((new Cpp_Emitter())->emit($again) === $cpp, 'Reset/reprepare differs');
	foreach (['true', '1.0', 'null', '"text"'] as $rhs) {
		rejects(fn() => $prepare($decl('x', 'int', '1') . $x . '+=' . $rhs . '; return 8;'), 'Unsupported compound');
	}
	foreach (['bool' => 'true', 'null' => 'null', 'string' => '"text"'] as $type => $value) {
		rejects(fn() => $prepare($decl('x', $type, $value) . $x . '-=' . $x . '; return 8;'), 'Unsupported compound');
	}
	rejects(fn() => $prepare($x . '+=1; return 8;'), 'existing local');
	$constant = $flavor === flavor::js ? 'const LIMIT: int = 1;' : 'const int LIMIT = 1;';
	rejects(fn() => $prepare($constant . $var('LIMIT') . '+=1; return 8;'), $flavor === flavor::php ? 'existing local' : 'constant');
	$global = match ($flavor) {
		flavor::php => 'global $x int = 1;',
		flavor::cpp => 'global int x = 1;',
		flavor::js => 'global let x: int = 1;',
	};
	rejects(fn() => $prepare($global . $x . '+=1; return 8;'), 'global writes are deferred');
	// A successful compound destroys the old literal fact; the later byte conversion must check at runtime.
	$stale = $decl('x', 'int', '1') . $x . '+=300;' . $decl('b', 'byte', $x) . 'return 8;';
	$fixtures[$flavor->name . '_facts'] = ['cpp' => (new Cpp_Emitter())->emit($prepare($stale)),
		'stdout_hex' => '', 'catch_out_of_range' => true];
	// Test-only C++ callers observe reference storage after exceptions; source try/catch stays deferred.
	foreach (['/=0' => [10, 'division_by_zero', ''], '%=0' => [10, 'modulo_by_zero', ''],
		'/=change(' . $var('v') . ')' => [100, 'division_by_zero', 'R'],
		'+=change(' . $var('v') . ')/0' => [100, 'division_by_zero', 'R']] as $update => [$expected, $error, $output]) {
		$source = $fn('change', $ref, 'int', $var('v') . '=100; echo "R"; return 0;')
			. $fn('update', $ref, 'void', $var('v') . $update . '; return;') . 'return 8;';
		$entry = $prepare($source);
		$name = '::App::' . Cpp_Emitter::function_name($entry->file->declarations['func:update']);
		$cpp = '#include <stdexcept>' . "\n#include <string_view>\n"
			. str_replace('int main()', 'int unused_entry()', (new Cpp_Emitter())->emit($entry));
		$cpp .= "\nint main() { " . type_int64::instance()->cpp_type() . ' value = 10; try { ' . $name . '(value); }'
			. ' catch (const std::domain_error& e) { return value == ' . $expected
			. ' && std::string_view(e.what()) == "' . $error . '" ? 8 : 91; } return 90; }';
		$fixtures[$flavor->name . '_failure_' . count($fixtures)] = ['cpp' => $cpp, 'stdout_hex' => bin2hex($output)];
	}
	$directory = sys_get_temp_dir() . '/scpp-compounds-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try {
		file_put_contents($directory . '/module.json', '{}');
		$main = $directory . '/main.' . $flavor->name;
		file_put_contents($main, $decl('x', 'int', '10') . $x . '+=value(); echo ' . $x . '; return 8;');
		$lib = $directory . '/lib.' . $flavor->name;
		$write = static function (string $type, string $value) use ($lib, $fn): void {
			file_put_contents($lib, $fn('value', '', $type, 'return ' . $value . ';'));
		};
		$write('int', '3');
		$compiler = new Project_Compiler();
		$session = $compiler->frontend($directory);
		$target = new entry_target(31);
		Incremental_Preparation::prepare($session, $target);
		$caller = $session->entry->entry->ast;
		$tokens = $caller->tokens;
		$write('float', '3.5');
		$session = $compiler->frontend($directory, $session);
		rejects(fn() => Incremental_Preparation::prepare($session, $target), 'Unsupported compound');
		$write('int64', '30');
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['prepared'] === 2, 'Compound recovery failed');
		verify(($session->entry->entry->ast === $caller) && ($caller->tokens === $tokens), 'Caller syntax replaced');
		$sources = [];
		foreach ($session->files() as $path => $file) {
			$sources[$path] = $file->source();
		}
		$program = (new Program_Frontend())->prepare_sources($session->entry_key(), $sources, $target);
		$native_files = Cpp_Program::emit($program)->files;
		$fixtures[$flavor->name . '_recovery'] = ['files' => $native_files, 'stdout_hex' => bin2hex('40')];
		$write('int64', '300');
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['reused'] === 1, 'Body edit invalidated caller');
		file_put_contents($main, $decl('x', 'int', '10') . $x . '*=value(); return 8;');
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['prepared'] === 1, 'Operator edit not prepared');
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
echo "Shared compound assignments, generated arithmetic, write-back facts and incremental recovery passed\n";
