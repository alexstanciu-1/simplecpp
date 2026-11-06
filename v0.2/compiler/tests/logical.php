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

/** Require a source diagnostic for unsupported logical operands and invalidation failures. */
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
	$prepare = static fn(string $source) => (new Source_Frontend())->prepare(new source_file('logical', $source, $flavor), new entry_target(31));
	$entry = $prepare($decl('result', 'bool', '!false || true && false') . 'return 8;');
	verify($entry->body()->expression_visits === 7, 'Logical expressions must each be prepared once');
	$expression = $entry->body()->statements[0]->initializer;
	verify($expression->operator === scpp\compiler\binary_operator::logical_or, 'OR precedence');
	verify($expression->right->operator === scpp\compiler\binary_operator::logical_and, 'AND precedence');
	verify($expression->left instanceof scpp\compiler\ast_not_expression, 'Negation syntax');
	verify($expression->left->operand->parent === $expression->left, 'Negation parent');
	$direct = (new Cpp_Emitter())->emit($entry);
	verify(!str_contains($direct, '([&]'), 'Logical operators introduced a sequencing lambda');
	$code = '';
	$expected = '';
	foreach (['!false' => true, '!!true' => true, '!8' => false, '!0' => true,
		'!-0.0' => true, '!1.25' => false, '!""' => true, '!"0"' => true, '!"false"' => false,
		'!"00"' => false, 'true || false && false' => true, '(true || false) && false' => false,
		'1 + 2 == 3 && 4 < 5' => true, '!true == false' => true,
		'true && 8' => true, '0 || "yes"' => true, '"0" || 0.0' => false,
		'"false" && 1.0' => true, '!((1 + 2) == 3)' => false] as $test => $truth) {
		$code .= 'if (' . $test . ') { echo "T"; } else { echo "F"; }';
		$expected .= $truth ? 'T' : 'F';
	}
	// Exercise independent admission across every current condition-type pair.
	foreach (['true', '8', '1.5', '"yes"'] as $left) {
		foreach (['false', '0', '-0.0', '"0"'] as $right) {
			$code .= 'if (' . $left . ' && ' . $right . ') { echo "WRONG"; }'
				. 'if (' . $right . ' || ' . $left . ') {} else { echo "WRONG"; }';
		}
	}
	$ref = match ($flavor) {
		flavor::php => 'int &$v',
		flavor::cpp => 'int &v',
		flavor::js => '&v: int',
	};
	$parameter = match ($flavor) {
		flavor::php => 'bool $v',
		flavor::cpp => 'bool v',
		flavor::js => 'v: bool',
	};
	$code .= $fn('left', '', 'bool', 'echo "L"; return false;')
		. $fn('right', '', 'int', 'echo "R"; return 8;')
		. $fn('text', '', 'string', 'echo "S"; return "0";')
		. $fn('change', $ref, 'bool', $var('v') . '=9; echo "M"; return true;')
		. $fn('identity', $parameter, 'bool', 'return !!' . $var('v') . ';')
		. 'if (left() && right()) { echo "WRONG"; }'
		. 'if (!left() || right()) { echo "O"; }'
		. 'if (left() || right()) { echo "A"; }'
		. 'if (!text()) { echo "Z"; }'
		. 'if (false && (1 / 0)) { echo "WRONG"; }'
		. 'if (true || (1 % 0)) { echo "K"; }'
		. $decl('x', 'int', '1') . $decl('result', 'bool', 'false && change(' . $var('x') . ')')
		. 'echo ' . $var('x') . ';'
		. $var('result') . '=identity(true && change(' . $var('x') . ')); echo ' . $var('x') . ';'
		. 'while (' . $var('x') . ' > 0 && !(' . $var('x') . ' == 7)) {' . $var('x') . '=' . $var('x') . '-1; }'
		. 'echo ' . $var('x') . ';'
		. 'do {' . $var('x') . '=' . $var('x') . '-1; } while (' . $var('x') . ' > 5 && true);'
		. 'echo ' . $var('x') . '; return 8;';
	$entry = $prepare($code);
	$before = serialize($entry);
	$cpp = (new Cpp_Emitter())->emit($entry);
	verify(serialize($entry) === $before, 'Lowering mutated facts');
	Syntax_Inspection::file($entry->file);
	Compilation_Inspection::body($entry->body());
	$fixtures[$flavor->name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex($expected . 'LLOLRASZK1M975')];
	Function_Preparation::reset_body($entry->body());
	$again = (new Preparation())->prepare($entry->file, new entry_target(31));
	verify((new Cpp_Emitter())->emit($again) === $cpp, 'Reset/reprepare differs');
	foreach (['!null', 'true || null', 'false && null'] as $invalid) {
		rejects(fn() => $prepare($decl('result', 'bool', $invalid) . 'return 8;'), 'Unsupported condition type');
	}
	rejects(fn() => $prepare('if (true || absent()) {} return 8;'), 'Unknown function');
	rejects(fn() => $prepare($fn('nothing', '', 'void', 'return;') . 'if (false && nothing()) {} return 8;'), 'Void call');
	rejects(fn() => $prepare($decl('self', 'bool', '!' . $var('self')) . 'return 8;'), 'during its initialization');
	// A conditional reference write must invalidate the old constant even though the call may be skipped.
	$failure = $fn('change', $ref, 'bool', $var('v') . '=300; return true;')
		. $decl('x', 'int', '1') . $decl('result', 'bool', 'true && change(' . $var('x') . ')')
		. $decl('narrow', 'byte', $var('x')) . 'return 8;';
	$fixtures[$flavor->name . '_reference'] = ['cpp' => (new Cpp_Emitter())->emit($prepare($failure)),
		'stdout_hex' => '', 'catch_out_of_range' => true];
	$directory = sys_get_temp_dir() . '/scpp-logical-' . bin2hex(random_bytes(8));
	mkdir($directory);
	try {
		file_put_contents($directory . '/module.json', '{}');
		file_put_contents($directory . '/main.' . $flavor->name, 'if (false || !value()) { echo "Y"; } return 8;');
		$lib = $directory . '/lib.' . $flavor->name;
		$write = static function (string $type, string $value) use ($lib, $fn): void {
			file_put_contents($lib, $fn('value', '', $type, 'return ' . $value . ';'));
		};
		$write('bool', 'false');
		$compiler = new Project_Compiler();
		$session = $compiler->frontend($directory);
		$target = new entry_target(31);
		Incremental_Preparation::prepare($session, $target);
		$caller = $session->entry->entry->ast;
		$tokens = $caller->tokens;
		$write('null', 'null');
		$session = $compiler->frontend($directory, $session);
		rejects(fn() => Incremental_Preparation::prepare($session, $target), 'Unsupported condition type');
		verify($session->entry->entry->ast === $caller, 'Caller reparsed after signature change');
		$write('string', '"0"');
		$session = $compiler->frontend($directory, $session);
		verify(Incremental_Preparation::prepare($session, $target)['prepared'] === 2, 'Logical recovery failed');
		verify(($session->entry->entry->ast === $caller) && ($caller->tokens === $tokens), 'Caller syntax replaced');
		// Native emission uses a fresh full preparation; incremental emission is outside this slice.
		$sources = [];
		foreach ($session->files() as $path => $file) {
			$sources[$path] = $file->source();
		}
		$program = (new Program_Frontend())->prepare_sources($session->entry_key(), $sources, $target);
		$native_files = Cpp_Program::emit($program)->files;
		$fixtures[$flavor->name . '_recovery'] = ['files' => $native_files, 'stdout_hex' => bin2hex('Y')];
		$write('string', '"hello"');
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
if (isset($argv[1])) {
	file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n");
}
echo "Logical truthiness, short circuit, single-pass preparation and incremental recovery passed\n";
