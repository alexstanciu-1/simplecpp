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
	$number = $fn('number', '', 'int', 'echo "N"; return 4;');
	$three = $fn('three', $param('byte', 'a') . ',' . $param('int', 'b') . ',' . $param('int', 'c'),
		'int', 'return (int)' . $var('a') . ' + ' . $var('b') . ' + ' . $var('c') . ';');
	$literal_calls = $emit($sum . $three . $number
		. 'echo sum(number(),2), sum(2,number()), three(255,number(),1), sum(number()); return 8;');
	foreach (['sum_(number_(), 2LL)', 'sum_(2LL, number_())', 'three_(static_cast<scpp::uint8>(255LL), number_(), 1LL)',
		'sum_(number_(), 3LL)'] as $call) {
		verify(str_contains($literal_calls, $call), 'Missing direct source call: ' . $call);
	}
	verify(!str_contains($literal_calls, 'argument_'), 'Scalar literal peer required temporary storage');
	$fixtures[$flavor->name . '_literal_peers'] = ['cpp' => $literal_calls, 'stdout_hex' => bin2hex('N6N6N260N7')];
	$s = $var('s');
	$concat = $flavor === flavor::php ? '.' : '+';
	$string = $decl('s', 'string', '"hi"');
	$repeat_call = static fn(string $subject, string $count): string => $flavor === flavor::php
		? "str_repeat($subject,$count)" : "$subject.repeat($count)";
	$label = $fn('label', '', 'string', 'echo "L"; return "xy";');
	$repeat_symbol = $flavor === flavor::php ? 'str_repeat' : 'repeat';
	$cpp = $emit($label . 'echo ' . $repeat_call('label()', '2') . '; return 8;');
	verify(str_contains($cpp, "$repeat_symbol(label_(), 2LL)") && !str_contains($cpp, 'argument_'), 'Expected direct runtime arguments');
	$fixtures[$flavor->name . '_runtime_repeat_direct'] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('Lxyxy')];
	$change_text = $fn('change_text', $param('string', 's', true), 'int', "$s=\"new\"; echo \"R\"; return 2;");
	$cpp = $emit($change_text . $decl('s', 'string', '"old"') . 'echo ' . $repeat_call($s, "change_text($s)") . ",$s; return 8;");
	verify(preg_match('/const auto (argument_\d+_0) = var_s_0; return ' . $repeat_symbol . '\(\1, change_text_\(var_s_0\)\);/', $cpp) === 1,
		'Borrowed source must be saved before the count changes it');
	$fixtures[$flavor->name . '_runtime_repeat_mutation'] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('Roldoldnew')];
	$limit = $emit($label . 'echo ' . $repeat_call('label()', '9223372036854775807') . '; return 8;');
	$fixtures[$flavor->name . '_runtime_repeat_failure'] = ['cpp' => $limit, 'stdout_hex' => bin2hex('L'),
		'catch_length_error' => 'string_repeat_too_large'];
	$fail_label = $fn('label', '', 'string', 'echo "L"; echo 1/0; return "xy";');
	$cpp = $emit($fail_label . 'echo ' . $repeat_call('label()', '9223372036854775807') . '; return 8;');
	$fixtures[$flavor->name . '_runtime_repeat_operand_failure'] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('L'),
		'catch_domain_error' => 'division_by_zero'];
	$count = $fn('count_mark', '', 'int', 'echo "C"; return 2;');
	$cpp = $emit($fail_label . $count . 'echo ' . $repeat_call('label()', 'count_mark()') . '; return 8;');
	$fixtures[$flavor->name . '_runtime_ordered_first_failure'] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('L'),
		'catch_domain_error' => 'division_by_zero'];
	$bad_count = $fn('bad_count', '', 'int', 'echo "C"; return 1%0;');
	$cpp = $emit($label . $bad_count . 'echo ' . $repeat_call('label()', 'bad_count()') . '; return 8;');
	verify(preg_match('/const auto (argument_\d+_0) = label_\(\); return ' . $repeat_symbol . '\(\1, bad_count_\(\)\);/', $cpp) === 1,
		'Final scalar producer must be in the adapter call after the string capture');
	$fixtures[$flavor->name . '_runtime_ordered_final_failure'] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('LC'),
		'catch_domain_error' => 'modulo_by_zero'];
	$pad_call = match ($flavor) {
		flavor::php => 'str_pad(label(),6,padding(),1)',
		flavor::cpp => 'label().pad_end(6,padding())',
		flavor::js => 'label().padEnd(6,padding())',
	};
	$padding = $fn('padding', '', 'string', 'echo "P"; return "-";');
	$cpp = $emit($label . $padding . "echo $pad_call; return 8;");
	verify(preg_match('/const auto (argument_\d+_0) = label_\(\); const auto (argument_\d+_2) = padding_\(\); return \w+\(\1, 6LL, \2'
		. ($flavor === flavor::php ? ', 1LL' : '') . '\);/', $cpp) === 1,
		'Ordered padding must capture both producers and pass the width directly');
	$fixtures[$flavor->name . '_runtime_pad_literal'] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('LPxy----')];
	$fixtures[$flavor->name . '_runtime_pad_failure'] = ['cpp' => $emit($fail_label . $padding . "echo $pad_call; return 8;"),
		'stdout_hex' => bin2hex('L'), 'catch_domain_error' => 'division_by_zero'];
	if ($flavor === flavor::php) {
		$format = $emit($label . $padding . 'echo number_format(1234,0,label(),padding()); return 8;');
		verify(preg_match('/return number_format\(static_cast<scpp::int64>\(1234LL\), 0LL, argument_\d+_2, argument_\d+_3\);/', $format) === 1,
			'Leading literals must stay inline and select the exact numeric overload');
		$fixtures['php_runtime_format_literals'] = ['cpp' => $format, 'stdout_hex' => bin2hex('LP1-234')];
	}
	$bytes = $emit($string . $s . ' = ' . $s . ' ' . $concat . ' "!\0x"; echo ' . $s . ';'
		. 'if (' . $s . ' === "hi!\0x") { echo 1; } if (' . $s . ' === "hi!\0y") { echo 2; } return 8;');
	verify(str_contains($bytes, 'var_s_0 = (var_s_0 + scpp::string{"!\0x", 3});')
		&& str_contains($bytes, 'var_s_0 == scpp::string{"hi!\0x", 5}') && !str_contains($bytes, '([&]()'),
		'String local and literal should use the direct runtime operators');
	$fixtures[$flavor->name . '_string_literal'] = ['cpp' => $bytes, 'stdout_hex' => bin2hex("hi!\0x1")];
	$peer = $var('peer');
	$stable = $string . $decl('peer', 'string', '"hi"');
	foreach (['===', '!==', '==', '!=', '<', '<=', '>', '>=', $concat] as $operator) {
		$cpp = $emit($stable . $decl('v', $operator === $concat ? 'string' : 'bool', "$s $operator $peer") . 'return 8;');
		verify(!str_contains($cpp, 'binary_left'), 'Unexpected string snapshots for ' . $operator);
	}
	$append = $fn('append_copy', $param('string', 's', true) . ',' . $param('string', 'peer', true),
		'void', "$s = $s $concat $peer;");
	$long = $decl('s', 'string', '"abcdefghijklmnopqrstuvw\0"');
	$aliased = $emit($append . $long . 'if ("short" === ' . $s . ") { return 9; } append_copy($s,$s); if ($s !== $s) { return 9; } echo $s; return 8;");
	verify(str_contains($aliased, 'var_s_0 = (var_s_0 + var_peer_1);'), 'Expected direct reference parameter operands');
	$fixtures[$flavor->name . '_aliased_strings'] = ['cpp' => $aliased, 'stdout_hex' => bin2hex(str_repeat("abcdefghijklmnopqrstuvw\0", 2))];
	$change_string = $fn('replace_text', $param('string', 's', true), 'string', "$s = \"new\"; return \"R\";");
	$effectful = $emit($change_string . $string . "echo ($s $concat replace_text($s)), $s; return 8;");
	verify(str_contains($effectful, 'const auto binary_left = var_s_0;'), 'Borrowed string lost its snapshot before a mutating RHS');
	$fixtures[$flavor->name . '_string_mutation'] = ['cpp' => $effectful, 'stdout_hex' => bin2hex('hiRnew')];
	$x = $var('x');
	$bump = $fn('bump', $param('int', 'x', true), 'void', "$x++;");
	$caller = $decl('x', 'int', '1') . "bump($x); echo $x; return 8;";
	$cpp = $emit($bump . $caller);
	verify(str_contains($cpp, 'bump_(var_x_0);') && !str_contains($cpp, 'argument_'), 'Expected direct reference binding');
	$fixtures[$flavor->name . '_reference_local'] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('2')];
	$forward = $fn('forward', $param('int', 'x', true), 'void', "bump($x);");
	$pair = $fn('pair', $param('int', 'x', true) . ', ' . $param('int', 'n'), 'void', "$x++;");
	$cpp = $emit($bump . $forward . $pair . $decl('x', 'int', '1') . "forward($x); pair($x, 1); echo $x; return 8;");
	verify(str_contains($cpp, 'bump_(var_x_0);') && str_contains($cpp, 'pair_(var_x_0, 1LL);'), 'Expected direct forwarding and exact reference binding');
	$fixtures[$flavor->name . '_forward_reference'] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('3')];
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
	verify(str_contains($divide, '(scpp::int64_divide(8LL, 2LL) + 1LL)'), 'Nested checked division should feed the addition directly');
	$fixtures[$flavor->name . '_checked_literal'] = ['cpp' => $divide, 'stdout_hex' => bin2hex('5')];
	$xor = $emit($decl('a', 'bool', 'true') . $decl('b', 'bool', 'false')
		. 'if (!(' . $var('a') . ' xor ' . $var('b') . ') || (false && (1 / 0))) { return 9; } return 8;');
	verify(str_contains($xor, '!(var_a_0 != var_b_1)') && !str_contains($xor, 'logical_left'), 'Expected grouped direct xor');
	$fixtures[$flavor->name . '_local_xor'] = ['cpp' => $xor, 'stdout_hex' => ''];
	$producers = $fn('left', '', 'int', 'echo "L"; return 10;') . $fn('right', '', 'int', 'echo "R"; return 3;');
	foreach (['/' => ['divide', '3'], '%' => ['remainder', '1'], '<<' => ['shift_left', '80'],
		'>>' => ['shift_right', '1'], '**' => ['power', '1000']] as $operator => [$helper, $expected]) {
		$cpp = $emit($producers . "echo left() $operator right(); return 8;");
		verify(str_contains($cpp, "scpp::int64_{$helper}(left_(), right_())"), 'Expected direct by-value arithmetic helper');
		$fixtures[$flavor->name . '_call_' . $helper] = ['cpp' => $cpp,
			'stdout_hex' => array_map('bin2hex', ['LR' . $expected, 'RL' . $expected])];
	}
	$float_calls = $emit($producers . 'if (left() / (float)right() > 3.0) { echo 1; } return 8;');
	verify(str_contains($float_calls, 'scpp::float64_divide(static_cast<scpp::float64>(left_()), (static_cast<scpp::float64>(right_())))'),
		'Promoted floating divide should consume each argument directly');
	$fixtures[$flavor->name . '_call_float_divide'] = ['cpp' => $float_calls, 'stdout_hex' => array_map('bin2hex', ['LR1', 'RL1'])];
	$failure = $emit($producers . 'echo left() << (1 % 0); return 8;');
	$fixtures[$flavor->name . '_call_argument_failure'] = ['cpp' => $failure,
		'stdout_hex' => array_map('bin2hex', ['', 'L']), 'catch_domain_error' => 'modulo_by_zero'];
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
	verify(str_contains($promoted, 'scpp::int64_divide(static_cast<scpp::int64>(var_a_0), var_b_1)'), 'Expected direct promoted division');
	$floating = $emit($decl('a', 'float', '6.0') . $decl('b', 'float', '2.0')
		. $decl('x', 'float', $var('a') . '/' . $var('b')) . 'return 8;');
	verify(str_contains($floating, 'scpp::float64_divide(var_a_0, var_b_1)'), 'Expected direct float division');
	$change = $fn('change_int', $param('int', 'x', true), 'int', "$x = 90; echo \"C\"; return 2;");
	$mark = $fn('mark_int', $param('int', 'v'), 'int', 'echo ' . $var('v') . '; return ' . $var('v') . ';');
	$last_default = $fn('choose', $param('int', 'a') . ',' . $param('int', 'b') . ',' . $param('byte', 'c') . '=7',
		'int', 'return ' . $var('a') . '+' . $var('b') . '+(int)' . $var('c') . ';');
	$ordered_literals = $emit($three . $mark . $last_default
		. 'echo three(255,mark_int(2),mark_int(3)), choose(mark_int(4),1,mark_int(5)), choose(mark_int(6),mark_int(7)); return 8;');
	foreach (['three_\(static_cast<scpp::uint8>\(255LL\), argument_\d+_1, mark_int_\(3LL\)\)',
		'choose_\(argument_\d+_0, 1LL, scpp::checked_int64_to_uint8\(mark_int_\(5LL\)\)\)',
		'choose_\(argument_\d+_0, mark_int_\(7LL\), static_cast<scpp::uint8>\(7LL\)\)'] as $args) {
		verify(preg_match('/return ' . $args . ';/', $ordered_literals) === 1, 'Expected direct literal peers in ordered call: ' . $args);
	}
	verify(preg_match_all('/scpp::int64 argument_\d+_\d+ =/', $ordered_literals) === 3, 'Expected one scalar capture per ordered call');
	$late_ref = $fn('update_last', $param('int', 'a') . ',' . $param('int', 'b') . ',' . $param('int', 'x', true),
		'void', "$x = " . $var('a') . '+' . $var('b') . ';');
	$ref_cpp = $emit($late_ref . $mark . $decl('x', 'int', '0') . "update_last(1,mark_int(2),$x); echo $x; return 8;");
	verify(str_contains($ref_cpp, 'update_last_(1LL, mark_int_(2LL), var_x_0);'), 'Expected direct final reference binding');
	$fixtures[$flavor->name . '_ordered_late_reference'] = ['cpp' => $ref_cpp, 'stdout_hex' => bin2hex('23')];
	$fixtures[$flavor->name . '_ordered_literal_positions'] = ['cpp' => $ordered_literals, 'stdout_hex' => bin2hex('2326045106720')];

	$alias = $fn('write_pair', $param('int', 'a', true) . ',' . $param('int', 'b', true),
		'void', "$a=4; $b=$b+1;");
	$write = $fn('write_sum', $param('int', 'a') . ',' . $param('int', 'x', true) . ',' . $param('int', 'b'),
		'void', "$x=$a+$b;");
	$ref_calls = $emit($alias . $write . $change . $decl('x', 'int', '8')
		. "write_sum($x,$x,change_int($x)); echo $x; write_pair($x,$x); echo $x; return 8;");
	verify(preg_match('/return write_sum_\(argument_\d+_0, var_x_0, change_int_\(var_x_0\)\);/', $ref_calls) === 1
		&& str_contains($ref_calls, 'write_pair_(var_x_0, var_x_0);'), 'Only the original by-value read requires capture');
	$fixtures[$flavor->name . '_ordered_reference_alias'] = ['cpp' => $ref_calls, 'stdout_hex' => bin2hex('C105')];
	$counter = match ($flavor) {
		flavor::php => 'struct Counter { public static function advance(int &$x,int $b):void { $x+=$b; } '
			. 'public static function accept(byte $a,int $b):void { echo "C",(int)$a,$b; } }',
		flavor::cpp => 'struct Counter { static void advance(int &x,int b) { x+=b; } '
			. 'static void accept(byte a,int b) { echo "C",(int)a,b; } };',
		flavor::js => 'struct Counter { static advance(&x:int,b:int):void { x+=b; } '
			. 'static accept(a:byte,b:int):void { echo "C",(int)a,b; } }',
	};
	$qualifier = $flavor === flavor::js ? 'Counter.' : 'Counter::';
	$static_calls = $emit($counter . $mark . $decl('x', 'int', '8')
		. "{$qualifier}advance($x,2); echo $x; {$qualifier}accept(mark_int(4),mark_int(5)); return 8;");
	verify(str_contains($static_calls, 'method_7_Counter_advance(var_x_0, 2LL);')
		&& preg_match('/return method_7_Counter_accept\(argument_\d+_0, mark_int_\(5LL\)\);/', $static_calls) === 1,
		'Static calls must use direct reference binding and retain the first effectful conversion');
	$fixtures[$flavor->name . '_static_arguments'] = ['cpp' => $static_calls, 'stdout_hex' => bin2hex('1045C45')];
	$static_failure = $emit($counter . $mark . "{$qualifier}accept(mark_int(300),mark_int(5)); return 8;");
	$fixtures[$flavor->name . '_static_conversion_failure'] = ['cpp' => $static_failure, 'stdout_hex' => bin2hex('300'),
		'catch_out_of_range' => 'integer_cast_out_of_range'];

	[$inline_record, $local_record, $receiver] = match ($flavor) {
		flavor::php => ['struct Acc { public int $n; public function add(byte $a,int $b=2):void { $this->n=(int)$a+$b; echo "C"; } }',
			'$r Acc;', '$r->'],
		flavor::cpp => ['struct Acc { int n; void add(byte a,int b=2) { this->n=(int)a+b; echo "C"; } };', 'Acc r;', 'r->'],
		flavor::js => ['struct Acc { n:int; add(a:byte,b:int=2):void { this.n=(int)a+b; echo "C"; } }', 'let r:Acc;', 'r.'],
	};
	$prefix = $inline_record . $mark . $local_record;
	$inline_calls = $emit($prefix . "{$receiver}add(3); echo {$receiver}n; {$receiver}add(mark_int(4),mark_int(5)); echo {$receiver}n; return 8;");
	verify(str_contains($inline_calls, 'method_3_Acc_add(var_r_0, static_cast<scpp::uint8>(3LL), 2LL);')
		&& preg_match('/return method_3_Acc_add\(var_r_0, argument_\d+_0, mark_int_\(5LL\)\);/', $inline_calls) === 1
		&& !str_contains($inline_calls, 'receiver_payload'), 'Inline method should retain only the first effectful argument');
	$fixtures[$flavor->name . '_inline_method_arguments'] = ['cpp' => $inline_calls, 'stdout_hex' => bin2hex('C545C9')];
	$failure = $emit($prefix . "{$receiver}add(mark_int(300),mark_int(5)); return 8;");
	$fixtures[$flavor->name . '_inline_method_failure'] = ['cpp' => $failure, 'stdout_hex' => bin2hex('300'),
		'catch_out_of_range' => 'integer_cast_out_of_range'];

	$holder = match ($flavor) {
		flavor::php => 'struct Holder { public Acc $item; }',
		flavor::cpp => 'struct Holder { Acc item; };',
		flavor::js => 'struct Holder { item:Acc; }',
	};
	$member = $flavor === flavor::js ? '.' : '->';
	$p = $var('p');
	foreach (['shared' => 'Acc', 'shared_field' => 'Holder'] as $kind => $record_name) {
		$field = $record_name === 'Acc' ? '' : $member . 'item';
		$target = $p . $field . $member;
		$owner_type = "shared<$record_name>";
		$prefix = $inline_record . $holder . $mark;
		$allocated = $decl('p', $owner_type, "new $record_name()");
		$replace = $fn('replace_owner', $param($owner_type, 'p', true), 'int', "$p=new $record_name(); echo \"R\"; return 4;");
		$source = $prefix . $replace . $allocated . $decl('original', $owner_type, $p)
			. "{$target}add(3); {$target}add(5,replace_owner($p)); echo " . $var('original') . $field . $member . "n,{$target}n; return 8;";
		$cpp = $emit($source);
		verify(str_contains($cpp, 'auto receiver_owner = var_p_0;') && str_contains($cpp, 'auto&& receiver_payload = ')
			&& str_contains($cpp, 'method_3_Acc_add(receiver_payload, static_cast<scpp::uint8>(3LL), 2LL)'), 'Shared receiver setup must survive direct argument lowering');
		$fixtures[$flavor->name . '_' . $kind . '_replace'] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('CRC90')];
		$call = "{$target}add(mark_int(300),mark_int(5)); return 8;";
		$empty = $emit($prefix . $decl('p', $owner_type, 'null') . $call);
		$fixtures[$flavor->name . '_' . $kind . '_empty'] = ['cpp' => $empty, 'stdout_hex' => '',
			'catch_runtime_error' => 'ownership_empty_access'];
		$failure = $emit($prefix . $allocated . $call);
		verify(preg_match('/return method_3_Acc_add\(receiver_payload, argument_\d+_0, mark_int_\(5LL\)\);/', $failure) === 1,
			'Shared method must capture the checked first argument');
		$fixtures[$flavor->name . '_' . $kind . '_checked'] = ['cpp' => $failure, 'stdout_hex' => bin2hex('300'),
			'catch_out_of_range' => 'integer_cast_out_of_range'];
	}

	// The returned inline record owns the last strong handle after make clears the caller's slot.
	[$temp_model, $make] = match ($flavor) {
		flavor::php => ['struct Child { public int $n; } struct Temp { public shared<Child> $child; '
			. 'public static function make(shared<Child> &$p):Temp { echo "R",$p->n; $t Temp; $t->child=$p; $p=null; return $t; } '
			. 'public function useArgs(byte $a,int $b,weak<Child> &$w):void { if (lock($w)) { echo "M",(int)$a,$b; } } }', 'Temp::make'],
		flavor::cpp => ['struct Child { int n; }; struct Temp { shared<Child> child; '
			. 'static Temp make(shared<Child> &p) { echo "R",p->n; Temp t; t->child=p; p=null; return t; } '
			. 'void useArgs(byte a,int b,weak<Child> &w) { if (lock(w)) { echo "M",(int)a,b; } } };', 'Temp::make'],
		flavor::js => ['struct Child { n:int; } struct Temp { child:shared<Child>; '
			. 'static make(&p:shared<Child>):Temp { echo "R",p.n; let t:Temp; t.child=p; p=null; return t; } '
			. 'useArgs(a:byte,b:int,&w:weak<Child>):void { if (lock(w)) { echo "M",(int)a,b; } } }', 'Temp.make'],
	};
	$w = $var('w');
	$inspect = $fn('inspect', $param('weak<Child>', 'w', true), 'int',
		"if (lock($w)) { echo \"A\"; return 4; } return 0;");
	$factory = "$make($p){$member}useArgs";
	$setup = $temp_model . $inspect . $mark . $decl('p', 'shared<Child>', 'new Child()')
		. $decl('w', 'weak<Child>', "weak($p)");
	$after = "if (lock($w)) { return 9; } echo \"E\"; return 8;";
	$cpp = $emit($setup . "$factory(inspect($w),mark_int(5),$w); $after");
	verify(substr_count($cpp, 'auto&& receiver_payload = method_4_Temp_make(var_p_0);') === 1
		&& preg_match('/return method_4_Temp_useArgs\(receiver_payload, argument_\d+_0, mark_int_\(5LL\), var_w_1\);/', $cpp) === 1,
		'Temporary receiver must bind once and capture only the earlier conversion');
	$fixtures[$flavor->name . '_temporary_lifetime'] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('R0A5M45E')];
	$cpp = $emit($setup . "$factory(3,2,$w); $after");
	verify(str_contains($cpp, 'method_4_Temp_useArgs(receiver_payload, static_cast<scpp::uint8>(3LL), 2LL, var_w_1)'),
		'Temporary receiver should take literals directly');
	$fixtures[$flavor->name . '_temporary_literals'] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('R0M32E')];
	$call = "$factory(mark_int(300),mark_int(5),$w); return 8;";
	$fixtures[$flavor->name . '_temporary_receiver_failure'] = ['cpp' => $emit($setup . "$p=null;" . $call),
		'stdout_hex' => bin2hex('R'), 'catch_runtime_error' => 'ownership_empty_access'];
	$fixtures[$flavor->name . '_temporary_argument_failure'] = ['cpp' => $emit($setup . $call),
		'stdout_hex' => bin2hex('R0300'), 'catch_out_of_range' => 'integer_cast_out_of_range'];

	[$record, $inline_decl, $shared_decl] = match ($flavor) {
		flavor::php => ['struct Pair { public function __construct(byte $a,int $b) { echo "C",(int)$a,$b; } }', '$p Pair', '$q=new Pair'],
		flavor::cpp => ['struct Pair { Pair(byte a,int b) { echo "C",(int)a,b; } };', 'Pair p', 'auto q=new Pair'],
		flavor::js => ['struct Pair { constructor(a:byte,b:int) { echo "C",(int)a,b; } }', 'let p:Pair', 'let q=new Pair'],
	};
	foreach (['inline' => [$inline_decl, 'scpp_records::Pair'], 'shared' => [$shared_decl, 'owner_Pair::make']] as $kind => [$construct, $symbol]) {
		$prefix = $record . $mark;
		$direct_ctor = $emit($prefix . "$construct(7,mark_int(3)); return 8;");
		verify(str_contains($direct_ctor, "$symbol(static_cast<scpp::uint8>(7LL), mark_int_(3LL))"),
			"Expected direct $kind construction with a proven literal");
		$ordered_ctor = $emit($prefix . "$construct(mark_int(4),mark_int(5)); return 8;");
		verify(preg_match('/return ' . preg_quote($symbol, '/') . '\(argument_\d+_0, mark_int_\(5LL\)\);/', $ordered_ctor) === 1,
			"Expected one capture for ordered $kind construction");
		$fixtures[$flavor->name . '_' . $kind . '_constructor_direct'] = ['cpp' => $direct_ctor, 'stdout_hex' => bin2hex('3C73')];
		$fixtures[$flavor->name . '_' . $kind . '_constructor_ordered'] = ['cpp' => $ordered_ctor, 'stdout_hex' => bin2hex('45C45')];
		$failure = $emit($prefix . "$construct(mark_int(300),mark_int(5)); return 8;");
		$fixtures[$flavor->name . '_' . $kind . '_constructor_failure'] = ['cpp' => $failure, 'stdout_hex' => bin2hex('300'),
			'catch_out_of_range' => 'integer_cast_out_of_range'];
	}
	$line = $fn('line', $param('int', 'price') . ',' . $param('int', 'qty') . '=3', 'int',
		'echo "L"; return ' . $var('price') . '*' . $var('qty') . ';');
	$addition = $emit($line . $decl('x', 'int', '8') . $decl('price', 'int', '2')
		. "$x = $x + line(" . $var('price') . ",3); echo $x; echo line($x) + $x; return 8;");
	verify(str_contains($addition, 'var_x_0 = (var_x_0 + line_(var_price_1, 3LL));')
		&& str_contains($addition, '(line_(var_x_0, 3LL) + var_x_0)'), 'Expected direct local and by-value call addition');
	$fixtures[$flavor->name . '_local_call_addition'] = ['cpp' => $addition, 'stdout_hex' => bin2hex('L14L56')];
	$aliases = $emit($line . $change . $decl('x', 'int', '8')
		. "echo $x + change_int($x); $x=8; echo $x + line(change_int($x),3); return 8;");
	verify(substr_count($aliases, 'const auto binary_left = var_x_0;') === 2, 'Lost a snapshot across direct or nested reference mutation');
	$fixtures[$flavor->name . '_local_call_alias'] = ['cpp' => $aliases, 'stdout_hex' => bin2hex('C10CL14')];
	$checked_line = $fn('checked_line', $param('byte', 'price'), 'int', 'echo "CALLEE"; return 1;');
	$fail_add = $emit($checked_line . $decl('x', 'int', '8') . $decl('price', 'int', '0') . $var('price') . '+=300;'
		. "echo $x + checked_line(" . $var('price') . '); return 8;');
	verify(str_contains($fail_add, '(var_x_0 + checked_line_(scpp::checked_int64_to_uint8(static_cast<scpp::int64>(var_price_1))))'),
		'Direct addition must keep checked parameter conversion');
	$fixtures[$flavor->name . '_local_call_failure'] = ['cpp' => $fail_add, 'stdout_hex' => '', 'catch_out_of_range' => 'integer_cast_out_of_range'];
	$ordered_calls = $emit($sum . $change . $mark . $decl('x', 'int', '8')
		. "echo sum($x,change_int($x)), sum(mark_int(4),mark_int(5)); return 8;");
	verify(preg_match('/return sum_\(argument_[0-9]+_0, change_int_\(var_x_0\)\);/', $ordered_calls) === 1
		&& preg_match('/return sum_\(argument_[0-9]+_0, mark_int_\(5LL\)\);/', $ordered_calls) === 1
		&& !preg_match('/argument_[0-9]+_1\b/', $ordered_calls), 'Expected only the first scalar argument to be captured');
	$fixtures[$flavor->name . '_ordered_call_peers'] = ['cpp' => $ordered_calls, 'stdout_hex' => bin2hex('C10459')];
	// Reading a scalar field must release the returned last owner before observe enters.
	$point = match ($flavor) {
		flavor::php => 'struct Point { public int $x; }',
		flavor::cpp => 'struct Point { int x; };',
		flavor::js => 'struct Point { x:int; }',
	};
	$member = $flavor === flavor::js ? '.' : '->';
	$p = $var('p');
	$owner_case = $point . $fn('transfer', $param('shared<Point>', 'p', true), 'shared<Point>',
		$decl('saved', 'shared<Point>', $p) . "$p=null; return " . $var('saved') . ';')
		. $fn('observe', $param('weak<Point>', 'w') . ',' . $param('int', 'v') . ',' . $param('int', 'tail') . '=0', 'int',
			'if (lock(' . $var('w') . ')) { return 99; } return ' . $var('v') . ';')
		. $decl('p', 'shared<Point>', 'new Point()') . "$p{$member}x=7; echo observe(weak($p),transfer($p){$member}x); return 8;";
	$fixtures[$flavor->name . '_final_owner_release'] = ['cpp' => $emit($owner_case), 'stdout_hex' => bin2hex('7')];
	$transfer = $fn('transfer', $param('shared<Point>', 'p', true), 'shared<Point>',
		$decl('saved', 'shared<Point>', $p) . "$p=null; return " . $var('saved') . ';');
	$owner_count = $emit($point . $transfer . $label . $decl('p', 'shared<Point>', 'new Point()')
		. $decl('w', 'weak<Point>', "weak($p)") . "$p{$member}x=2; echo "
		. $repeat_call('label()', "transfer($p){$member}x") . '; if (lock(' . $var('w') . ')) { return 9; } return 8;');
	verify(preg_match('/const auto (argument_\d+_1) = \(transfer_\(var_p_0\)\)->x; return ' . $repeat_symbol . '\(argument_\d+_0, \1\);/', $owner_count) === 1,
		'Final field read must release its temporary owner before adapter entry');
	$fixtures[$flavor->name . '_runtime_ordered_owner'] = ['cpp' => $owner_count, 'stdout_hex' => bin2hex('Lxyxy')];
	// Even a sole value argument may need its own full-expression boundary beside a reference.
	$ref_owner_case = $point . $fn('transfer', $param('shared<Point>', 'p', true), 'shared<Point>',
		$decl('saved', 'shared<Point>', $p) . "$p=null; return " . $var('saved') . ';')
		. $fn('observe_ref', $param('weak<Point>', 'w', true) . ',' . $param('int', 'v'), 'int',
			'if (lock(' . $var('w') . ')) { return 99; } return ' . $var('v') . ';')
		. $decl('p', 'shared<Point>', 'new Point()') . $decl('w', 'weak<Point>', "weak($p)")
		. "$p{$member}x=7; echo observe_ref(" . $var('w') . ",transfer($p){$member}x); return 8;";
	$fixtures[$flavor->name . '_ref_owner_release'] = ['cpp' => $emit($ref_owner_case), 'stdout_hex' => bin2hex('7')];
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
		'stdout_hex' => '', 'catch_domain_error' => ['division_by_zero', 'modulo_by_zero']];
	$checked = $fn('accept_byte', $param('byte', 'a') . ',' . $param('int', 'b'), 'int', 'return 1;')
		. $fn('bad', '', 'int', 'echo "B"; return 300;')
		. $fn('later', '', 'int', 'echo "WRONG"; return 1;')
		. 'echo accept_byte(bad(),later()); return 8;';
	$failing = $fn('reject_byte', $param('byte', 'a') . ',' . $param('int', 'b'), 'int', 'echo "CALLEE"; return 1;')
		. $fn('outside_byte', '', 'int', 'echo "B"; return 300;') . 'echo reject_byte(outside_byte(),2); return 8;';
	$cpp = $emit($failing);
	verify(str_contains($cpp, 'reject_byte_(scpp::checked_int64_to_uint8(outside_byte_()), 2LL)'),
		'Only the checked argument can have effects');
	$fixtures[$flavor->name . '_sole_checked_argument'] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('B'),
		'catch_out_of_range' => 'integer_cast_out_of_range'];
	$checked_last = $fn('last_byte', $param('int', 'a') . ',' . $param('byte', 'b'), 'int', 'echo "CALLEE"; return 1;')
		. $fn('first_mark', '', 'int', 'echo "A"; return 1;')
		. $fn('outside_byte', '', 'int', 'echo "B"; return 300;')
		. 'echo last_byte(first_mark(),outside_byte()); return 8;';
	$cpp = $emit($checked_last);
	verify(preg_match('/return last_byte_\(argument_[0-9]+_0, scpp::checked_int64_to_uint8\(outside_byte_\(\)\)\);/', $cpp) === 1,
		'Final scalar should keep its checked conversion in the call');
	$fixtures[$flavor->name . '_ordered_final_conversion'] = ['cpp' => $cpp, 'stdout_hex' => bin2hex('AB'),
		'catch_out_of_range' => 'integer_cast_out_of_range'];
	$fixtures[$flavor->name . '_conversion_order'] = ['cpp' => $emit($checked), 'stdout_hex' => bin2hex('B'), 'catch_out_of_range' => 'integer_cast_out_of_range'];
}
if (isset($argv[1])) {
	file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT) . "\n");
}
echo "Direct scalar emission and retained sequencing proofs passed\n";
