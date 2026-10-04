<?php

/* Runtime kind dispatch, operand boxing and checked destinations for mixed arithmetic. */
$fixtures = [];
foreach (['add' => ['+', 11, 11.0], 'subtract' => ['-', 7, 7.0],
	'multiply' => ['*', 18, 18.0], 'divide' => ['/', 4, 4.5], 'remainder' => ['%', 1, 0.0]] as $name => [$op, $integer, $floating])
{
	$source = 'function calculate(mixed $a, mixed $b): mixed { return $a ' . $op . ' $b; } '
		. 'function right(mixed $a, int $b): mixed { return $a ' . $op . ' $b; } '
		. 'function left(int $a, mixed $b): mixed { return $a ' . $op . ' $b; } '
		. 'function store(mixed $a, mixed $b, mixed &$out): void { $out = $a ' . $op . ' $b; } ';
	$probe = '([&]() { scpp::mixed_t a{scpp::int_t<>{9LL}}, b{scpp::int_t<>{2LL}}; '
		. 'auto result = function_calculate(a, b); '
		. 'if (result.int_value().native_value() != ' . $integer
		. ' || function_right(a, scpp::int_t<>{2LL}).int_value().native_value() != ' . $integer
		. ' || function_left(scpp::int_t<>{9LL}, b).int_value().native_value() != ' . $integer . ') return false; ';
	if ($op !== '%')
	{
		$source .= 'function rightfloat(mixed $a, float $b): mixed { return $a ' . $op . ' $b; } '
			. 'function leftfloat(float $a, mixed $b): mixed { return $a ' . $op . ' $b; } ';
		$probe .= 'scpp::mixed_t fa{scpp::float_t{9.0}}, fb{scpp::float_t{2.0}}; '
			. 'if (function_calculate(fa, fb).float_value().native_value() != ' . $floating
			. ' || function_calculate(a, fb).float_value().native_value() != ' . $floating
			. ' || function_calculate(fa, b).float_value().native_value() != ' . $floating
			. ' || function_rightfloat(a, scpp::float_t{2.0}).float_value().native_value() != ' . $floating
			. ' || function_leftfloat(scpp::float_t{9.0}, b).float_value().native_value() != ' . $floating . ') return false; ';
	}
	$probe .= 'auto table = scpp::shared<scpp::hash_t<scpp::mixed_t>>(); int caught = 0; '
		. 'scpp::mixed_t out{scpp::string_t{"unchanged"}}; '
		. 'for (const auto& bad : {scpp::mixed_t{scpp::null}, scpp::mixed_t{true}, '
		. 'scpp::mixed_t{scpp::string_t{"12"}}, scpp::mixed_t{table}, scpp::mixed_t{scpp::dynamic_box(table)}}) { '
		. 'try { function_store(bad, b, out); } catch (const std::runtime_error&) { ++caught; } '
		. 'try { function_store(a, bad, out); } catch (const std::runtime_error&) { ++caught; } } '
		. 'return caught == 10 && out.get_string().native_value() == "unchanged"'
		. ' && a.int_value().native_value() == 9 && b.int_value().native_value() == 2; }())';
	$fixtures['mixed_arithmetic_' . $name] = [$source, $probe];
}
foreach (['positive' => ['+', 9], 'negative' => ['-', -9]] as $name => [$op, $expected])
{
	$fixtures['mixed_arithmetic_' . $name] = [
		'function once(mixed $v, int &$n): mixed { $n++; return $v; } '
			. 'function calculate(mixed $v, int &$n): mixed { return ' . $op . 'once($v, $n); }',
		'([&]() { scpp::int_t<> n{0LL}; scpp::mixed_t source{scpp::int_t<>{9LL}}; '
			. 'auto integer = function_calculate(source, n); '
			. 'auto floating = function_calculate(scpp::mixed_t{scpp::float_t{9.0}}, n); int caught = 0; '
			. 'try { function_calculate(scpp::mixed_t{scpp::string_t{"9"}}, n); } catch (const std::runtime_error&) { ++caught; } '
			. 'return n.native_value() == 3 && caught == 1 && source.int_value().native_value() == 9'
			. ' && integer.int_value().native_value() == ' . $expected
			. ' && floating.float_value().native_value() == ' . $expected . '; }())',
	];
}
$fixtures['mixed_arithmetic_boundaries'] = [
	'function divide(mixed $a, mixed $b): mixed { return $a / $b; } '
		. 'function remainder(mixed $a, mixed $b): mixed { return $a % $b; } '
		. 'function store(mixed $a, mixed $b, int &$out): void { $out = $a + $b; } '
		. 'function returned(mixed $a, mixed $b): int { return $a + $b; } '
		. 'function accept(int $v, int &$n): int { $n++; return $v; } '
		. 'function argument(mixed $a, mixed $b, int &$n): int { return accept($a + $b, $n); }',
	'([&]() { scpp::mixed_t a{scpp::int_t<>{9LL}}, b{scpp::int_t<>{2LL}}, f{scpp::float_t{2.0}}; '
		. 'scpp::int_t<> out{77LL}, n{0LL}; int caught = 0; '
		. 'if (function_returned(a, b).native_value() != 11 || function_argument(a, b, n).native_value() != 11) return false; '
		. 'try { function_store(a, f, out); } catch (const std::runtime_error&) { ++caught; } '
		. 'try { function_returned(a, f); } catch (const std::runtime_error&) { ++caught; } '
		. 'try { function_argument(a, f, n); } catch (const std::runtime_error&) { ++caught; } '
		. 'try { function_divide(a, scpp::mixed_t{scpp::int_t<>{0LL}}); } catch (const std::runtime_error&) { ++caught; } '
		. 'try { function_divide(a, scpp::mixed_t{scpp::float_t{0.0}}); } catch (const std::runtime_error&) { ++caught; } '
		. 'try { function_remainder(a, scpp::mixed_t{scpp::int_t<>{0LL}}); } catch (const std::runtime_error&) { ++caught; } '
		. 'try { function_remainder(a, f); } catch (const std::runtime_error&) { ++caught; } '
		. 'return caught == 7 && out.native_value() == 77 && n.native_value() == 1; }())',
];
return $fixtures;
