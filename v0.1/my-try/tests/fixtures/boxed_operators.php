<?php

/* One source/probe matrix for both families; only the family spelling is substituted. */
$operator_cases = [];
foreach (['add' => ['+', '+=', 11, 11.0], 'subtract' => ['-', '-=', 7, 7.0],
	'multiply' => ['*', '*=', 18, 18.0], 'divide' => ['/', '/=', 4, 4.5],
	'remainder' => ['%', '%=', 1, 0.0]] as $name => [$op, $update, $integer, $floating]) {
	$source = 'function calculate(BOX $a, BOX $b): BOX { return $a ' . $op . ' $b; } '
		. 'function left(int $a, BOX $b): BOX { return $a ' . $op . ' $b; } '
		. 'function right(BOX $a, int $b): BOX { return $a ' . $op . ' $b; } '
		. 'function update(BOX &$a, BOX $b): BOX { return $a ' . $update . ' $b; } '
		. 'function store(BOX &$a, BOX $b, BOX &$out): void { $out = ($a ' . $update . ' $b); }';
	$probe = '([&]() { using B = CPPBOX; B a{scpp::int_t<>{9LL}}, b{scpp::int_t<>{2LL}}; '
		. 'if (function_calculate(a, b).get_int().native_value() != ' . $integer
		. ' || function_left(scpp::int_t<>{9LL}, b).get_int().native_value() != ' . $integer
		. ' || function_right(a, scpp::int_t<>{2LL}).get_int().native_value() != ' . $integer . ') return false; '
		. 'auto old = a; auto result = function_update(a, b); '
		. 'if (old.get_int().native_value() != 9 || a.get_int().native_value() != ' . $integer
		. ' || result.get_int().native_value() != ' . $integer . ') return false; ';
	if ($op !== '%') {
		$probe .= 'B fa{scpp::float_t{9.0}}, fb{scpp::float_t{2.0}}; '
			. 'if (function_calculate(old, fb).get_float().native_value() != ' . $floating
			. ' || function_calculate(fa, b).get_float().native_value() != ' . $floating
			. ' || function_update(fa, fb).get_float().native_value() != ' . $floating . ') return false; ';
	}
	$probe .= 'a = old; B out{scpp::string_t{"old"}}; int caught = 0; '
		. 'for (const auto& bad : {B{scpp::float_t{2.0}}, B{scpp::string_t{"2"}}, B{scpp::bool_t{true}}, B{}}) { '
		. 'try { function_store(a, bad, out); } catch (const std::runtime_error&) { ++caught; } '
		. 'if (a.get_int().native_value() != 9 || out.get_string().native_value() != "old") return false; } '
		. 'return caught == 4 && b.get_int().native_value() == 2; }())';
	$operator_cases[$name] = [$source, $probe];
}
$operator_cases['unary_and_snapshots'] = [
	'function source(BOX $v, int &$n): BOX { $n++; return $v; } '
		. 'function positive(BOX $v, int &$n): BOX { return +source($v, $n); } '
		. 'function negative(BOX $v, int &$n): BOX { return -source($v, $n); } '
		. 'function preinc(BOX &$v): BOX { return ++$v; } '
		. 'function postinc(BOX &$v): BOX { return $v++; } '
		. 'function predec(BOX &$v): BOX { return --$v; } '
		. 'function postdec(BOX &$v): BOX { return $v--; }',
	'([&]() { using B = CPPBOX; B v{scpp::int_t<>{4LL}}; scpp::int_t<> n{0LL}; '
		. 'if (function_positive(v, n).get_int().native_value() != 4 '
		. '|| function_negative(v, n).get_int().native_value() != -4 || n.native_value() != 2) return false; '
		. 'auto a = function_preinc(v); auto b = function_postinc(v); '
		. 'auto c = function_predec(v); auto d = function_postdec(v); '
		. 'B f{scpp::float_t{1.5}}; auto before = function_postinc(f); '
		. 'return a.get_int().native_value() == 5 && b.get_int().native_value() == 5 '
		. '&& c.get_int().native_value() == 5 && d.get_int().native_value() == 5 '
		. '&& v.get_int().native_value() == 4 && before.get_float().native_value() == 1.5 '
		. '&& f.get_float().native_value() == 2.5; }())',
];
$operator_cases['failures'] = [
	'function divide(BOX &$v, BOX $rhs): void { $v /= $rhs; } '
		. 'function modulo(BOX &$v, BOX $rhs): void { $v %= $rhs; } '
		. 'function increment(BOX &$v): void { $v++; } '
		. 'function typed(BOX &$v): int { return ++$v; } '
		. 'function ordinary(BOX &$v): void { $v = $v + 0.5; } '
		. 'function twice(BOX &$v): BOX { return $v += $v; } '
		. 'function table(): void { $v BOX = []; $v++; }',
	'([&]() { using B = CPPBOX; B v{scpp::int_t<>{4LL}}; int caught = 0; '
		. 'if (function_twice(v).get_int().native_value() != 8) return false; '
		. 'try { function_divide(v, B{scpp::int_t<>{0LL}}); } catch (const std::runtime_error&) { ++caught; } '
		. 'try { function_modulo(v, B{scpp::int_t<>{0LL}}); } catch (const std::runtime_error&) { ++caught; } '
		. 'if (v.get_int().native_value() != 8) return false; '
		. 'B text{scpp::string_t{"2"}}; try { function_increment(text); } catch (const std::runtime_error&) { ++caught; } '
		. 'try { function_table(); } catch (const std::runtime_error&) { ++caught; } '
		. 'function_ordinary(v); try { function_typed(v); } catch (const std::runtime_error&) { ++caught; } '
		. 'return caught == 5 && v.get_float().native_value() == 9.5 && text.get_string().native_value() == "2"; }())',
];
$operator_cases['comparisons'] = [
	'function check(): bool { $n BOX = 1; $s BOX = "1"; $f BOX = 1.0; $b BOX = true; $nil BOX = null; '
		. 'return $n === 1 && 1 === $n && $n !== 1.0 && 1.0 !== $n '
		. '&& $s === "1" && "1" === $s && $f === 1.0 && 1.0 === $f '
		. '&& $b === true && true === $b && $nil === null && null === $nil '
		. '&& $n == $s && $n == 1.0 && $n != 2 && $n < 2 && 0 < $n '
		. '&& $n <= 1 && $n > 0 && $n >= 1 && $n === $n && $n !== $s; } '
		. 'function invalid(BOX $v, bool &$out): void { $out = $v < 1; }',
	'([&]() { using B = CPPBOX; scpp::bool_t out{true}; int caught = 0; '
		. 'try { function_invalid(B{scpp::string_t{"1"}}, out); } catch (const std::runtime_error&) { ++caught; } '
		. 'return function_check().native_value() && caught == 1 && out.native_value(); }())',
];
$operator_cases['text'] = [
	'function append(BOX &$v): BOX { return $v .= "!"; } '
		. 'function join(BOX $v): string { return "[" . $v . "]"; } '
		. 'function failure(BOX &$v): void { $v .= (1 / 0); } '
		. 'function typed(BOX &$v): int { return $v .= "!"; }',
	'([&]() { using B = CPPBOX; B v{scpp::int_t<>{7LL}}; auto copy = v; int caught = 0; '
		. 'if (function_join(v).native_value() != "[7]") return false; '
		. 'try { function_failure(v); } catch (const std::runtime_error&) { ++caught; } '
		. 'if (v.get_int().native_value() != 7) return false; auto old = function_append(v); '
		. 'try { function_typed(v); } catch (const std::runtime_error&) { ++caught; } '
		. 'return caught == 2 && old.get_string().native_value() == "7!" '
		. '&& v.get_string().native_value() == "7!!" && copy.get_int().native_value() == 7; }())',
];
$operator_cases['containers'] = [
	'function read(): BOX { $v BOX = {n: 7}; return $v["n"] + 1; } '
		. 'function aliases(): bool { $a BOX = {n: 1}; $b = $a; return $a === $b; } '
		. 'function independent(): bool { $a BOX = {n: 1}; $b BOX = {n: 1}; return $a === $b; } '
		. 'function text(): string { $a BOX = {}; return $a . "!"; }',
	'function_read().get_int().native_value() == 8 && function_aliases().native_value() '
		. '&& function_independent().native_value() == INDEPENDENT '
		. '&& function_text().native_value() == "CONTAINER!"',
];
$fixtures = [];
foreach (['mixed' => 'scpp::mixed_t', 'dynamic' => 'scpp::dynamic_t<>'] as $family => $cpp) {
	foreach ($operator_cases as $name => [$source, $probe]) {
		$fixtures['boxed_' . $family . '_' . $name] = [str_replace('BOX', $family, $source),
			str_replace(['CPPBOX', 'INDEPENDENT', 'CONTAINER'],
				[$cpp, $family === 'mixed' ? 'true' : 'false', $family === 'mixed' ? 'Array' : 'Object'], $probe)];
	}
}
return $fixtures;
