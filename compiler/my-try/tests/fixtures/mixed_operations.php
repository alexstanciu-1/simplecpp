<?php

/* Explicit casts, condition interpretation and non-mutating one-value probes. */
$fixtures = [];
$states = [
	['null', false, false, true], ['false', false, true, true], ['true', true, true, false],
	['0', false, true, true], ['7', true, true, false], ['0.0', false, true, true],
	['1.5', true, true, false], ['""', false, true, true], ['"0"', false, true, false],
	['"false"', true, true, false], ['"hello"', true, true, false],
];
foreach ($states as $index => [$literal, $truthy, $present, $empty])
{
	$fixtures['mixed_probe_scalar_' . $index] = [
		'function once(mixed $v, int &$n): mixed { $n++; return $v; } '
			. '$v mixed = ' . $literal . '; $n = 0; $branch = 0; '
			. 'if (once($v, $n)) { $branch = 1; } else { $branch = 2; } '
			. '$selected = once($v, $n) ? 1 : 2; $negated = !once($v, $n); '
			. '$present = isset(once($v, $n)); $empty = empty(once($v, $n));',
		'local_n.native_value() == 5 && local_branch.native_value() == ' . ($truthy ? '1' : '2')
			. ' && local_selected.native_value() == ' . ($truthy ? '1' : '2')
			. ' && local_negated.native_value() == ' . ($truthy ? 'false' : 'true')
			. ' && local_present.native_value() == ' . ($present ? 'true' : 'false')
			. ' && local_empty.native_value() == ' . ($empty ? 'true' : 'false'),
	];
}
$fixtures['mixed_condition_consumers'] = [
	'function test(int &$calls): mixed { $calls++; return $calls < 3; } '
		. '$a mixed = "0"; $b mixed = "false"; $branch = 0; '
		. 'if ($a) { $branch = 1; } elseif ($b) { $branch = 2; } else { $branch = 3; } '
		. '$wcalls = 0; $wbody = 0; while (test($wcalls)) { $wbody++; } '
		. '$dcalls = 0; $dbody = 0; do { $dbody++; } while (test($dcalls)); '
		. '$fcalls = 0; $fbody = 0; for (; test($fcalls);) { $fbody++; } '
		. '$lazy = $b ? 7 : 1 / 0;',
	'local_branch.native_value() == 2 && local_wcalls.native_value() == 3'
		. ' && local_wbody.native_value() == 2 && local_dcalls.native_value() == 3'
		. ' && local_dbody.native_value() == 3 && local_fcalls.native_value() == 3'
		. ' && local_fbody.native_value() == 2 && local_lazy.native_value() == 7',
];
$fixtures['mixed_unary_single_operand'] = [
	'function flag(int &$n): bool { $n++; return false; } '
		. 'function number(int &$n): int { $n++; return 7; } '
		. '$n = 0; $b = !flag($n); $negative = -number($n); $positive = +number($n); $bits = ~number($n);',
	'local_n.native_value() == 4 && local_b.native_value() && local_negative.native_value() == -7'
		. ' && local_positive.native_value() == 7 && local_bits.native_value() == -8',
];
$casts = [
	'int' => ['scpp::int_t<>', '9LL', [['true', '1'], ['1.75', '1'], ['"42"', '42']], ['null', '"42tail"']],
	'bool' => ['scpp::bool_t', 'true', [['false', 'false'], ['2', 'true'], ['0.0', 'false'],
		['""', 'false'], ['"0"', 'false'], ['"false"', 'false'], ['"1"', 'true'], ['"true"', 'true']],
		['null', '"hello"', '"TRUE"']],
	'float' => ['scpp::float_t', '9.5', [['true', '1.0'], ['2', '2.0'], ['"2.5"', '2.5']],
		['null', '"2.5tail"']],
	'string' => ['scpp::string_t', '"unchanged"', [['null', '""'], ['false', '""'], ['true', '"1"'],
		['2', '"2"'], ['1.5', '"1.5"'], ['"text"', '"text"']], []],
];
foreach ($casts as $type => [$cpp_type, $initial, $values, $failures])
{
	$functions = 'function once(mixed $v, int &$n): mixed { $n++; return $v; } '
		. 'function convert(mixed $v, ' . $type . ' &$out, int &$n): void { $out = (' . $type . ')once($v, $n); } ';
	$source = $functions . '$n = 0; ';
	$checks = [];
	foreach ($values as $index => [$literal, $expected])
	{
		$source .= '$v' . $index . ' mixed = ' . $literal . '; $out' . $index . ' = (' . $type . ')once($v'
			. $index . ', $n); convert($v' . $index . ', $out' . $index . ', $n); ';
		$checks[] = 'local_out' . $index . '.native_value() == ' . $expected;
	}
	$checks[] = 'local_n.native_value() == ' . (count($values) * 2);
	$fixtures['mixed_cast_' . $type] = [$source, implode(' && ', $checks)];
	foreach ($failures as $index => $literal)
	{
		$fixtures['mixed_cast_failure_' . $type . '_' . $index] = [
			$functions . '$bad mixed = ' . $literal . ';',
			'([&]() { ' . $cpp_type . ' out{' . $initial . '}; scpp::int_t<> n{0LL}; bool caught = false; '
				. 'try { function_convert(local_bad, out, n); } catch (const std::runtime_error&) { caught = true; } '
				. 'return caught && n.native_value() == 1 && out.native_value() == ' . $initial . '; }())',
		];
	}
	$fixtures['mixed_cast_table_' . $type] = [
		$functions,
		'([&]() { scpp::mixed_t bad{scpp::shared<scpp::hash_t<scpp::mixed_t>>()}; '
			. $cpp_type . ' out{' . $initial . '}; scpp::int_t<> n{0LL}; bool caught = false; '
			. 'try { function_convert(bad, out, n); } catch (const std::runtime_error&) { caught = true; } '
			. 'return caught && n.native_value() == 1 && out.native_value() == ' . $initial
			. ' && bad.shared_table_if()->get()->empty().native_value(); }())',
	];
}
$fixtures['mixed_operation_source_exception'] = [
	'function fail(int &$n): mixed { $n++; return 1 / 0; } '
		. 'function castfail(int &$n): int { return (int)fail($n); } '
		. 'function conditionfail(int &$n): bool { if (fail($n)) { return true; } return false; } '
		. 'function notfail(int &$n): bool { return !fail($n); } '
		. 'function issetfail(int &$n): bool { return isset(fail($n)); } '
		. 'function emptyfail(int &$n): bool { return empty(fail($n)); }',
	'([&]() { scpp::int_t<> n{0LL}; int caught = 0; '
		. 'try { (void)function_castfail(n); } catch (const std::runtime_error&) { ++caught; } '
		. 'try { (void)function_conditionfail(n); } catch (const std::runtime_error&) { ++caught; } '
		. 'try { (void)function_notfail(n); } catch (const std::runtime_error&) { ++caught; } '
		. 'try { (void)function_issetfail(n); } catch (const std::runtime_error&) { ++caught; } '
		. 'try { (void)function_emptyfail(n); } catch (const std::runtime_error&) { ++caught; } '
		. 'return caught == 5 && n.native_value() == 5; }())',
];
$fixtures['mixed_condition_table_runtime_failure'] = [
	'function condition(mixed $v): bool { if ($v) { return true; } return false; } '
		. 'function negate(mixed $v): bool { return !$v; } '
		. 'function present(mixed $v): bool { return isset($v); } '
		. 'function vacant(mixed $v): bool { return empty($v); }',
	'([&]() { auto table = scpp::shared<scpp::hash_t<scpp::mixed_t>>(); '
		. 'scpp::mixed_t boxed{table}; scpp::mixed_t object{scpp::unique<scpp::hash_t<scpp::mixed_t>>()}; int caught = 0; '
		. 'try { (void)function_condition(boxed); } catch (const std::runtime_error&) { ++caught; } '
		. 'try { (void)function_negate(boxed); } catch (const std::runtime_error&) { ++caught; } '
		. 'try { (void)function_condition(object); } catch (const std::runtime_error&) { ++caught; } '
		. 'try { (void)function_negate(object); } catch (const std::runtime_error&) { ++caught; } '
		. 'return caught == 4 && function_present(boxed).native_value() && function_vacant(boxed).native_value()'
		. ' && function_present(object).native_value() && function_vacant(object).native_value()'
		. ' && table->empty().native_value() && boxed.shared_table_if()->get() == table.get()'
		. ' && object.table_if()->size() == 0 && object.table_if() != table.get(); }())',
];
return $fixtures;
