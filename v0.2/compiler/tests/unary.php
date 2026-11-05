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

/** Require a source diagnostic for unsupported unary operands and invalidation failures. */
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
	$prepare = static fn(string $source) => (new Source_Frontend())->prepare(new source_file('unary', $source, $flavor), new entry_target(31));
	$entry = $prepare('echo - +8 * 2; return 8;');
	verify($entry->body()->expression_visits === 6, 'Each unary operand must be prepared once');
	$expression = $entry->body()->statements[0]->value;
	verify($expression instanceof scpp\compiler\ast_binary_expression, 'Unary precedence');
	$unary = $expression->left;
	verify($unary instanceof scpp\compiler\ast_unary_expression, 'Unary syntax');
	verify($unary->operator === scpp\compiler\unary_operator::negative, 'Outer minus');
	verify($unary->operand->operator === scpp\compiler\unary_operator::positive, 'Right-to-left prefix nesting');
	verify($unary->operand->parent === $unary, 'Operand ownership');
	verify(!$unary->constant_known(), 'Unary introduced constant folding');
	$cpp = (new Cpp_Emitter())->emit($entry);
	verify(!str_contains($cpp, '([&]'), 'Pure unary introduced a sequencing lambda');
	Function_Preparation::reset_body($entry->body());
	foreach ([$unary, $unary->operand] as $reset) {
		try {
			$reset->type_def();
			throw new RuntimeException('Recursive reset failed');
		}
		catch (LogicException $expected) {
		}
	}
	$again = (new Preparation())->prepare($entry->file, new entry_target(31));
	verify((new Cpp_Emitter())->emit($again) === $cpp, 'Reset/reprepare differs');
	$code = $decl('x', 'int64', '8');
	$expected = '';
	foreach (['-' . $var('x') => -8, '+' . $var('x') => 8, '- -8' => 8, '- +8' => -8,
		'-(8 + 2)' => -10, '-8 * 2 + 3' => -13, '(int)(-8)' => -8, '-(int)3.5' => -3,
		'-(int)true' => -1, '-0' => 0, '-9223372036854775807 - 1' => PHP_INT_MIN] as $test => $value) {
		$code .= 'echo ' . $test . '; echo "|";';
		$expected .= $value . '|';
	}
	$ref = match ($flavor) {
		flavor::php => 'int &$v', flavor::cpp => 'int &v', flavor::js => '&v: int',
	};
	$param = match ($flavor) {
		flavor::php => 'int $v', flavor::cpp => 'int v', flavor::js => 'v: int',
	};
	$code .= $fn('negative', $param, 'int', 'return -' . $var('v') . ';')
		. $fn('change', $ref, 'int', $var('v') . ' += 1; echo "C"; return ' . $var('v') . ';')
		. 'echo negative(+' . $var('x') . '); echo "|";'
		. 'echo -change(' . $var('x') . '); echo "|"; echo ' . $var('x') . '; echo "|";'
		. $var('x') . ' = -' . $var('x') . ';'
		. 'while (' . $var('x') . ' < -7) { ' . $var('x') . '++; } echo ' . $var('x') . ';'
		. 'if (!(-0) && -1 < +1) { echo "Y"; } return 8;';
	$entry = $prepare($code);
	$before = serialize($entry);
	$cpp = (new Cpp_Emitter())->emit($entry);
	verify(serialize($entry) === $before, 'Lowering mutated facts');
	Syntax_Inspection::file($entry->file);
	Compilation_Inspection::body($entry->body());
	$fixtures[$flavor->name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex($expected . '-8|C-9|9|-7Y')];
	foreach (['-true', '+false', '-null', '-"8"', '-(1.5)', '+(byte)8'] as $invalid) {
		rejects(fn() => $prepare('echo ' . $invalid . '; return 8;'), 'Unsupported unary');
	}
	rejects(fn() => $prepare('echo -9223372036854775808; return 8;'), 'outside int64 range');
	rejects(fn() => $prepare($decl('self', 'int', '-' . $var('self')) . 'return 8;'), 'during its initialization');
	rejects(fn() => $prepare($fn('nothing', '', 'void', 'return;') . 'echo -nothing(); return 8;'), 'Void call');
	rejects(fn() => $prepare($fn('change', $ref, 'int', 'return 1;') . $decl('x', 'int', '8') . 'change(+' . $var('x') . '); return 8;'), 'Reference argument');
	// Signed floating literals retain their existing path; unary float expressions remain deferred.
	$prepare($decl('f', 'float', '-0.0') . 'return 8;');
	$directory = sys_get_temp_dir() . '/scpp-unary-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try {
		file_put_contents($directory . '/module.json', '{}');
		file_put_contents($directory . '/main.' . $flavor->name, 'echo -value(); return 8;');
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
		$tokens = $caller->tokens;
		$write('bool', 'true');
		$session = $compiler->frontend($directory, $session);
		rejects(fn() => Incremental_Preparation::prepare($session, $target), 'Unsupported unary');
		verify($session->entry->entry->ast === $caller, 'Caller reparsed after signature change');
		$write('int64', '8');
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['prepared'] === 2, 'Unary recovery failed');
		verify(($session->entry->entry->ast === $caller) && ($caller->tokens === $tokens), 'Caller syntax replaced');
		// Native emission uses a fresh full preparation; incremental emission is outside this slice.
		$sources = [];
		foreach ($session->files() as $path => $file) {
			$sources[$path] = $file->source();
		}
		$program = (new Program_Frontend())->prepare_sources($session->entry_key(), $sources, $target);
		$native_files = Cpp_Program::emit($program)->files;
		$fixtures[$flavor->name . '_recovery'] = ['files' => $native_files, 'stdout_hex' => bin2hex('-8')];
		$write('int64', '10');
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['reused'] === 1, 'Body-only edit invalidated caller');
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['reused'] === 2, 'No-op rebuilt');
		file_put_contents($directory . '/main.' . $flavor->name, 'echo + value(); return 8;');
		$session = $compiler->frontend($directory, $session);
		$changed = Incremental_Preparation::prepare($session, $target);
		verify(($changed['prepared'] === 1) && ($changed['reused'] === 1), 'Sign edit rebuilt unchanged callee');
		verify($session->entry->entry->ast !== $caller, 'Changed sign reused old syntax');

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
echo "Int64 unary signs, single-pass preparation and incremental recovery passed\n";
