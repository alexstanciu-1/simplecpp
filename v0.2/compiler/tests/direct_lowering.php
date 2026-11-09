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
		flavor::php => "$$name $type = $value;",
		flavor::cpp => "$type $name = $value;",
		flavor::js => "let $name: $type = $value;",
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
	verify(str_contains($safe, '(var_x_0 * 3LL)'), 'Original arithmetic grouping lost');
	$s = $var('s');
	$concat = $flavor === flavor::php ? '.' : '+';
	$string = $decl('s', 'string', '"hi"');
	$bytes = $emit($string . $s . ' = ' . $s . ' ' . $concat . ' "!\0x"; echo ' . $s . ';'
		. 'if (' . $s . ' === "hi!\0x") { echo 1; } if (' . $s . ' === "hi!\0y") { echo 2; } return 8;');
	verify(str_contains($bytes, 'var_s_0 = (var_s_0 + scpp::string{"\041\000\170", 3});')
		&& str_contains($bytes, 'var_s_0 == scpp::string{"\150\151\041\000\170", 5}') && !str_contains($bytes, '([&]()'),
		'String local and literal should use the direct runtime operators');
	$fixtures[$flavor->name . '_string_literal'] = ['cpp' => $bytes, 'stdout_hex' => bin2hex("hi!\0x1")];
	foreach (["$s === $s" => 'bool', '"hi" === ' . $s => 'bool', $s . ' !== "hi"' => 'bool',
		$s . ' == "hi"' => 'bool', "$s $concat $s" => 'string'] as $expression => $result_type) {
		$cpp = $emit($string . $decl('v', $result_type, $expression) . 'return 8;');
		verify(str_contains($cpp, 'const auto binary_left'), 'Unexpected direct lowering for ' . $expression);
	}
	$x = $var('x');
	$bump = $fn('bump', $param('int', 'x', true), 'void', "$x++;");
	$caller = $decl('x', 'int', '1') . "bump($x); echo $x; return 8;";
	$cpp = $emit($bump . $caller);
	verify(str_contains($cpp, 'bump_(var_x_0);') && !str_contains($cpp, 'argument_'), 'Expected direct reference binding');
	$fixtures[$flavor->name . '_reference_local'] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('2')];
	$forward = $fn('forward', $param('int', 'x', true), 'void', "bump($x);");
	$pair = $fn('pair', $param('int', 'x', true) . ', ' . $param('int', 'n'), 'void', "$x++;");
	$cpp = $emit($bump . $forward . $pair . $decl('x', 'int', '1') . "pair($x, 1); return 8;");
	verify(str_contains($cpp, 'bump_(argument_') && str_contains($cpp, 'pair_(argument_'), 'Other reference call forms changed');
	$change = $fn('change', $param('string', 's'), 'void', $s . ' = "changed"; echo ' . $s . ';');
	$cpp = $emit($change . $string . "change($s); echo $s; return 8;");
	verify(str_contains($cpp, 'change_(var_s_0);'), 'The by-value string parameter should make the copy');
	$fixtures[$flavor->name . '_string_parameter_copy'] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('changedhi')];
	$accept = $fn('accept', $param('byte', 'x'), 'void', 'echo "C", (int)' . $x . ';');
	foreach ([7, 300] as $number) {
		$source = $fn('produce', '', 'int', 'echo "P"; return ' . $number . ';') . $accept . 'accept(produce()); return 8;';
		$cpp = $emit($source);
		verify(str_contains($cpp, 'accept_(scpp::checked_int64_to_uint8(produce_()));')
			&& str_contains($cpp, '#include <scpp/conversions.hpp>'), 'Expected direct checked parameter conversion and its provider');
		$fixture = ['cpp' => $cpp, 'stdout_hex' => bin2hex($number === 7 ? 'PC7' : 'P')];
		if ($number === 300) {
			$fixture['catch_out_of_range'] = 'integer_cast_out_of_range';
		}
		$fixtures[$flavor->name . '_checked_parameter_' . $number] = $fixture;
	}
	$divide = $emit('echo (8 / 2) + 1; return 8;');
	verify(str_contains($divide, 'const auto binary_left = scpp::int64_divide(8LL, 2LL);'), 'Nested checked division should feed the existing outer snapshot directly');
	foreach (['int' => 'int64', 'byte' => 'uint8'] as $name => $native) {
		$source = $decl('a', $name, '6') . $decl('b', $name, '2');
		$source .= $decl('x', $name, $var('a') . '/' . $var('b')) . $decl('y', $name, $var('b') . '%' . $var('b'));
		$cpp = $emit($source . 'return 8;');
		verify(str_contains($cpp, "scpp::{$native}_divide(var_a_0, var_b_1)") &&
			str_contains($cpp, "scpp::{$native}_remainder(var_b_1, var_b_1)"), 'Expected direct checked integer helpers');
	}
	$a = $var('a');
	$b = $var('b');
	$prefix = $decl('a', 'int', '6') . $decl('b', 'int', '2');
	$direct = $emit($prefix . "echo $a << $b, $a >> $b, $a ** $b; if ($a !== $b) { echo 1; } return 8;");
	foreach (['int64_shift_left', 'int64_shift_right', 'int64_power'] as $helper) {
		verify(str_contains($direct, "scpp::{$helper}(var_a_0, var_b_1)"), 'Expected direct ' . $helper);
	}
	verify(str_contains($direct, 'var_a_0 != var_b_1') && !str_contains($direct, '([&]()'), 'Expected direct same-type integer comparison');
	$fixtures[$flavor->name . '_integer_operators'] = ['cpp' => $direct, 'stdout_hex' => bin2hex('241361')];
	$part = "($a + $a)";
	$repeat = $emit($prefix . $decl('x', 'int', "$a + $a") . "echo $x, $part * $b, $part + $part;"
		. "if ($part < $part) { echo 9; } if ($a === $a) { echo 1; } echo 2 ^ 2; return 8;");
	$native_part = '(var_a_0 + var_a_0)';
	verify(str_contains($repeat, "var_x_2 = $native_part;") && str_contains($repeat, "($native_part * var_b_1)")
		&& str_contains($repeat, "($native_part + $native_part)"), 'Safe repeated sums should compose directly');
	verify(str_contains($repeat, "const auto binary_left = $native_part; const auto binary_right = $native_part; return (binary_left < binary_right);")
		&& str_contains($repeat, 'binary_left == binary_right'), 'Self-comparisons must retain warning-safe operands');
	$fixtures[$flavor->name . '_repeated_sum'] = ['cpp' => $repeat, 'stdout_hex' => bin2hex('12242410')];

	$repeat_byte = $emit($decl('b', 'byte', '130') . "echo (int)($b + $b); return 8;");
	verify(str_contains($repeat_byte, 'static_cast<scpp::uint8>((var_b_0 + var_b_0))'), 'Byte sum must narrow before widening');
	$fixtures[$flavor->name . '_repeated_byte'] = ['cpp' => $repeat_byte, 'stdout_hex' => bin2hex('4')];

	$count = $var('calls');
	$next = $fn('number', $param('int', 'calls', true), 'int', "$count++; return $count;");
	$repeat_calls = $emit($next . $decl('calls', 'int', '0') . "echo number($count) + number($count), $count; return 8;");
	$fixtures[$flavor->name . '_repeated_calls'] = ['cpp' => $repeat_calls, 'stdout_hex' => bin2hex('32')];
	$byte = $emit($decl('a', 'byte', '6') . $decl('b', 'byte', '2') . "if ($a !== $b) { echo 1; } return 8;");
	verify(str_contains($byte, 'var_a_0 != var_b_1'), 'Expected direct byte comparison');
	$self = $emit($prefix . "if ($a !== $a) { echo 1; } return 8;");
	verify(str_contains($self, 'binary_left != binary_right'), 'Self-comparison lost its warning-safe spelling');
	$mixed = $emit($decl('a', 'int', '6') . $decl('b', 'byte', '2') . "if ($a !== $b) { echo 1; } return 8;");
	verify(str_contains($mixed, 'const auto binary_left'), 'Cross-type strict identity changed lowering');
	$compare = $fn('cmp', $param('byte', 'a') . ', ' . $param('byte', 'b'), 'int', "return $a <=> $b;");
	$locals = $emit($compare . $prefix . "echo $a <=> $b, cmp((byte)1, (byte)2), cmp((byte)2, (byte)2); return 8;");
	$formula = 'static_cast<scpp::int64>(((var_a_0 > var_b_1)) - ((var_a_0 < var_b_1)))';
	verify(substr_count($locals, $formula) === 2 && !str_contains($locals, 'const auto binary_left'),
		'Expected direct three-way comparisons for distinct int locals and byte parameters');
	$fixtures[$flavor->name . '_three_way_storage'] = ['cpp' => $locals, 'stdout_hex' => bin2hex('1-10')];
	$v = $var('v');
	$uses = $emit($prefix . $decl('x', 'int', "$a <=> $b") . $decl('v', 'vector<int>', '[]')
		. $v . '[] = 0;' . $v . '[] = 0;' . $v . "[$a <=> $b] = $b <=> $a;"
		. $v . "[0] = $b + 1; echo $x, " . $v . '[1], ' . $v . '[0]; return 8;');
	verify(str_contains($uses, 'scpp::int64 var_x_2 = (((var_a_0 > var_b_1)) - ((var_a_0 < var_b_1)));'),
		'Typed initialization should widen the integer comparison result directly');
	$reverse = 'static_cast<scpp::int64>(((var_b_1 > var_a_0)) - ((var_b_1 < var_a_0)))';
	verify(str_contains($uses, "var_v_3.set($formula, $reverse);"),
		'Vector set must use the exact comparison results directly for the index and element');
	$fixtures[$flavor->name . '_three_way_destinations'] = ['cpp' => $uses, 'stdout_hex' => bin2hex('1-13')];
	$nested = $emit($prefix . $decl('x', 'int', "($a <=> $b) + 1") . 'return 8;');
	verify(str_contains($nested, $formula), 'Initializer context leaked into nested arithmetic');
	foreach (["$a <=> $a", "$a <=> 2", "($a + 1) <=> $b"] as $expression) {
		verify(str_contains($emit($prefix . "echo $expression; return 8;"), 'const auto binary_left'),
			'Expected three-way snapshots for ' . $expression);
	}

	$global = $emit('global ' . $decl('a', 'int', '6') . $decl('b', 'int', '2') . "echo $a <=> $b; return 8;");
	verify(str_contains($global, 'const auto binary_left = ::App::var_a;'), 'Global read lost its three-way snapshot');
	$widen = $emit($decl('a', 'byte', '6') . $decl('b', 'int', '2') . "echo $a <=> $b; return 8;");
	verify(str_contains($widen, 'const auto binary_left'), 'Three-way operand promotion lost its snapshot');

	// Independent counters prove single evaluation without asserting which call runs first.
	$count = $var('calls');
	$effects = $fn('low', $param('int', 'calls', true), 'int', "$count++; return 1;")
		. $fn('high', $param('int', 'calls', true), 'int', "$count++; return 2;")
		. $decl('a', 'int', '0') . $decl('b', 'int', '0')
		. $decl('x', 'int', "low($a) <=> high($b)") . "echo $x, $a, $b; return 8;";
	$calls = $emit($effects);
	verify(str_contains($calls, 'return (((binary_left > binary_right)) - ((binary_left < binary_right)));'),
		'Three-way comparison of calls must compare their snapshots');
	$fixtures[$flavor->name . '_three_way_calls'] = ['cpp' => $calls, 'stdout_hex' => bin2hex('-111')];
	$promoted = $emit($decl('a', 'byte', '6') . $decl('b', 'int', '2') . 'echo ' . $var('a') . '/' . $var('b') . '; return 8;');
	verify(str_contains($promoted, 'scpp::int64_divide(binary_left, binary_right)'), 'Operand promotion lost its existing snapshots');
	$floating = $emit($decl('a', 'float', '6.0') . $decl('b', 'float', '2.0')
		. $decl('x', 'float', $var('a') . '/' . $var('b')) . 'return 8;');
	verify(str_contains($floating, 'scpp::float64_divide(binary_left, binary_right)'), 'Floating division changed lowering');
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
