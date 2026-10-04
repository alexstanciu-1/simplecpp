<?php

/* Branch normalization and lazy selection; expected values do not reuse runtime selectors. */
$fixtures = [];
$values = [
	['null', false, 'is_null', 'true'],
	['false', false, 'bool_value', 'false'], ['true', true, 'bool_value', 'true'],
	['0', false, 'int_value', '0'], ['7', true, 'int_value', '7'],
	['0.0', false, 'float_value', '0.0'], ['1.5', true, 'float_value', '1.5'],
	['""', false, 'get_string', '""'], ['"0"', false, 'get_string', '"0"'],
	['"false"', true, 'get_string', '"false"'], ['"hello"', true, 'get_string', '"hello"'],
];
foreach ($values as $index => [$literal, $truthy, $accessor, $expected])
{
	$original = '.' . $accessor . '().native_value() == ' . $expected;
	$fixtures['mixed_selection_state_' . $index] = [
		'function once(mixed $v, int &$n): mixed { $n++; return $v; } '
			. 'function fallback(int &$n): int { $n++; return 42; } '
			. '$v mixed = ' . $literal . '; $n = 0; $fallbacks = 0; '
			. '$full = true ? once($v, $n) : fallback($fallbacks); '
			. '$elvis = once($v, $n) ?: fallback($fallbacks); '
			. '$coalesce = once($v, $n) ?? fallback($fallbacks); $v = "changed";',
		'local_n.native_value() == 3 && local_fallbacks.native_value() == '
			. (($truthy ? 0 : 1) + ($literal === 'null' ? 1 : 0))
			. ' && local_full' . $original
			. ' && local_elvis' . ($truthy ? $original : '.int_value().native_value() == 42')
			. ' && local_coalesce' . ($literal === 'null' ? '.int_value().native_value() == 42' : $original),
	];
}
foreach ([['false', 'bool_value', 'false'], ['9', 'int_value', '9'],
	['2.5', 'float_value', '2.5'], ['"text"', 'get_string', '"text"']] as $index => [$literal, $accessor, $expected])
{
	$predicate = '.' . $accessor . '().native_value() == ' . $expected;
	$fixtures['mixed_selection_symmetric_' . $index] = [
		'function fail(): mixed { return 1 / 0; } '
			. '$m mixed = null; $a = true ? ' . $literal . ' : $m; '
			. '$b = false ? $m : ' . $literal . '; $c = $m ?? ' . $literal . '; '
			. '$d = ' . $literal . ' ?? fail(); $e = $m ?: ' . $literal . '; '
			. '$f = ' . $literal . ' ?: $m;',
		'local_a' . $predicate . ' && local_b' . $predicate . ' && local_c' . $predicate
			. ' && local_d' . $predicate . ' && local_e' . $predicate
			. ' && local_f' . ($literal === 'false' ? '.is_null().native_value()' : $predicate),
	];
}
$fixtures['mixed_selection_mixed_pairs'] = [
	'function fail(): mixed { return 1 / 0; } '
		. '$a mixed = null; $b mixed = null; $c mixed = "left"; $d mixed = 7; '
		. '$nullresult = $a ?? $b; $nullselected = false ? $c : $a; '
		. '$left = $c ?? fail(); $right = $a ?? $d; $elvis = $a ?: $d; '
		. '$truthy = $c ?: fail(); $full = true ? $c : fail(); $c = "changed"; $d = 8;',
	'local_nullresult.is_null().native_value() && local_nullselected.is_null().native_value()'
		. ' && local_left.get_string().native_value() == "left" && local_right.int_value().native_value() == 7'
		. ' && local_elvis.int_value().native_value() == 7 && local_truthy.get_string().native_value() == "left"'
		. ' && local_full.get_string().native_value() == "left"',
];
$fixtures['mixed_selection_failure'] = [
	'function fail(int &$n): mixed { $n++; return 1 / 0; } '
		. 'function leftfail(mixed &$out, int &$n): void { $out = fail($n) ?? 1; } '
		. 'function fallbackfail(mixed &$out, int &$n): void { $v mixed = null; $out = $v ?? fail($n); } '
		. 'function elvisfail(mixed &$out, int &$n): void { $v mixed = false; $out = $v ?: fail($n); } '
		. 'function branchfail(mixed &$out, int &$n): void { $out = true ? fail($n) : 1; }',
	'([&]() { scpp::mixed_t out{scpp::string_t{"old"}}; scpp::int_t<> n{0LL}; int caught = 0; '
		. 'try { function_leftfail(out, n); } catch (const std::runtime_error&) { ++caught; } '
		. 'try { function_fallbackfail(out, n); } catch (const std::runtime_error&) { ++caught; } '
		. 'try { function_elvisfail(out, n); } catch (const std::runtime_error&) { ++caught; } '
		. 'try { function_branchfail(out, n); } catch (const std::runtime_error&) { ++caught; } '
		. 'return caught == 4 && n.native_value() == 4 && out.get_string().native_value() == "old"; }())',
];
$fixtures['mixed_selection_table_bridge'] = [
	'function choose(mixed $v): mixed { return true ? $v : 1; } '
		. 'function coalesce(mixed $v): mixed { return $v ?? 1; } '
		. 'function elvis(mixed $v): mixed { return $v ?: 1; }',
	'([&]() { auto table = scpp::shared<scpp::hash_t<scpp::mixed_t>>(); '
		. 'scpp::mixed_t object{scpp::dynamic_box(table)}; auto selected = function_choose(object); '
		. 'auto present = function_coalesce(object); bool caught = false; '
		. 'try { (void)function_elvis(object); } catch (const std::runtime_error&) { caught = true; } '
		. 'return caught && selected.dynamic_if()->get() == table.get() && present.dynamic_if()->get() == table.get()'
		. ' && table->empty().native_value(); }())',
];
return $fixtures;
