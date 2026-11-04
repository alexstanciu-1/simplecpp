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
	$param = static fn(string $type, string $name, bool $ref = false): string => match ($flavor) {
		flavor::php => $type . ' ' . ($ref ? '&$' : '$') . $name,
		flavor::cpp => $type . ' ' . ($ref ? '&' : '') . $name,
		flavor::js => ($ref ? '&' : '') . $name . ': ' . $type,
	};
	$prepare = static fn(string $source) => (new Source_Frontend())->prepare(new source_file('direct', $source, $flavor), new entry_target(31));
	$emit = static fn(string $source): string => (new Cpp_Emitter())->emit($prepare($source));
	$sum = $fn('sum', $param('int', 'a') . ', ' . $param('int', 'b') . '=3', 'int', 'return ' . $var('a') . '+' . $var('b') . ';');
	$safe = $emit($sum . $decl('x', 'int', '2') . 'echo sum(' . $var('x') . '*3+1); return 8;');
	verify(!str_contains($safe, '([&]()'), 'Trivial operands or identity arguments still wrapped');
	verify(!str_contains($safe, '&&>('), 'Scalar identity arguments still moved');
	verify(str_contains($safe, '(local_0 * 3LL)'), 'Original arithmetic grouping lost');
	$divide = $emit('echo (8 / 2) + 1; return 8;');
	verify(substr_count($divide, '([&]()') === 2, 'Throwing operation or its containing expression lost sequencing');
	$code = $sum
		. $fn('bump', $param('int', 'x', true), 'int', $var('x') . '=9; return 2;')
		. $fn('pick', $param('string', 'a') . ',' . $param('string', 'b'), 'string', 'echo ' . $var('a') . '; return ' . $var('b') . ';')
		. $fn('replace', $param('string', 's', true), 'string', $var('s') . '="new"; return "R";')
		. $fn('mark', $param('int', 'v'), 'int', 'echo ' . $var('v') . '; return ' . $var('v') . ';')
		. $decl('x', 'int', '1') . 'echo sum(' . $var('x') . ',bump(' . $var('x') . '));'
		. $var('x') . '=1; echo ' . $var('x') . '+bump(' . $var('x') . ');'
		. 'echo sum(mark(4),mark(5));'
		. $decl('s', 'string', '"old"') . 'echo pick(' . $var('s') . ',replace(' . $var('s') . '));'
		. $var('x') . '=0; while (' . $var('x') . '<2) {' . $var('x') . '=' . $var('x') . '+1; } echo ' . $var('x') . '; return 8;';
	$entry = $prepare($code);
	$before = serialize($entry);
	$cpp = (new Cpp_Emitter())->emit($entry);
	verify(serialize($entry) === $before, 'Lowering changed AST/preparation');
	verify(str_contains($cpp, 'static_cast<scpp::string&&>'), 'String move/copy preservation lost');
	$fixtures[$flavor->name] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('33459oldR2')];
	$fixtures[$flavor->name . '_failure_order'] = ['cpp' => $emit('echo (1/0) + (1%0); return 8;'),
		'stdout_hex' => '', 'catch_domain_error' => 'division_by_zero'];
	$checked = $fn('accept_byte', $param('byte', 'a') . ',' . $param('int', 'b'), 'int', 'return 1;')
		. $fn('bad', '', 'int', 'echo "B"; return 300;')
		. $fn('later', '', 'int', 'echo "WRONG"; return 1;')
		. 'echo accept_byte(bad(),later()); return 8;';
	$fixtures[$flavor->name . '_conversion_order'] = ['cpp' => $emit($checked), 'stdout_hex' => bin2hex('B'), 'catch_out_of_range' => true];
}
if (isset($argv[1])) {
	file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n");
}
echo "Direct scalar emission and retained sequencing proofs passed\n";
