<?php

/* Source boundaries and independent runtime observations; no mixed operators needed. */
$fixtures = [];
$fixtures['mixed_box_kinds'] = [
	'$n mixed = null; $b mixed = false; $i mixed = 0; $f mixed = 1.5; $s mixed = "0"; '
		. '$default mixed; $changed mixed = 1; $changed = "new"; $changed = null;',
	'local_n.is_null().native_value() && local_default.is_null().native_value()'
		. ' && local_changed.is_null().native_value() && !local_b.bool_value().native_value()'
		. ' && local_i.int_value().native_value() == 0 && local_f.float_value().native_value() == 1.5'
		. ' && local_s.get_string().native_value() == "0"',
];
$fixtures['mixed_copy_values'] = [
	'function pass(mixed $v): mixed { return $v; } '
		. 'function change(mixed $v): mixed { $v = "changed"; return $v; } '
		. '$a mixed = "old"; $b = pass($a); $c = change($a); $d mixed = null; $d = $a; '
		. '$a = 7; $s string = $b; $b = "new";',
	'local_a.int_value().native_value() == 7 && local_b.get_string().native_value() == "new"'
		. ' && local_c.get_string().native_value() == "changed" && local_d.get_string().native_value() == "old"'
		. ' && local_s.native_value() == "old"',
];
$fixtures['mixed_box_boundaries'] = [
	'function produce(int &$n): int { $n++; return $n; } '
		. 'function wrap(int $v): mixed { return $v; } function pass(mixed $v): mixed { return $v; } '
		. 'function replace(mixed &$v): void { $v = "changed"; } '
		. 'function absent(): mixed { return null; } '
		. '$n = 0; $a = pass(produce($n)); $b = wrap(produce($n)); '
		. '$c mixed = 0; $c = produce($n); $d = $a; replace($a); $e = absent();',
	'local_n.native_value() == 3 && local_a.get_string().native_value() == "changed"'
		. ' && local_b.int_value().native_value() == 2 && local_c.int_value().native_value() == 3'
		. ' && local_d.int_value().native_value() == 1 && local_e.is_null().native_value()',
];
$fixtures['mixed_value_parameter_snapshot'] = [
	'function observe(mixed $snapshot, mixed &$original): string { $original = "new"; return $snapshot; } '
		. '$a mixed = "old"; $seen = observe($a, $a);',
	'local_seen.native_value() == "old" && local_a.get_string().native_value() == "new"',
];
$fixtures['mixed_source_exception'] = [
	'function fail(int &$n): mixed { $n++; return 1 / 0; } '
		. 'function store(int &$out, int &$n): void { $out = fail($n); }',
	'([&]() { scpp::int_t<> out{9LL}, n{0LL}; bool caught = false; '
		. 'try { function_store(out, n); } catch (const std::runtime_error&) { caught = true; } '
		. 'return caught && out.native_value() == 9 && n.native_value() == 1; }())',
];
$payloads = [
	'int' => ['scpp::int_t<>', '9LL', ['0' => '0LL', '42' => '42LL']],
	'bool' => ['scpp::bool_t', 'true', ['false' => 'false', '0' => 'false', '2' => 'true', '0.0' => 'false',
		'1.5' => 'true', '""' => 'false', '"0"' => 'false', '"1"' => 'true', '"false"' => 'false', '"true"' => 'true']],
	'float' => ['scpp::float_t', '9.5', ['false' => '0.0', '2' => '2.0', '1.5' => '1.5', '"2.5"' => '2.5']],
	'string' => ['scpp::string_t', '"unchanged"', ['false' => '""', 'true' => '"1"', '2' => '"2"', '1.5' => '"1.5"', '"text"' => '"text"']],
];
$failures = [
	'int' => ['false', '1.5', '"42"', 'null'],
	'bool' => ['"hello"', '"TRUE"', 'null'],
	'float' => ['"2.5tail"', '"nan"', 'null'],
	'string' => ['null'],
];
foreach ($payloads as $payload => [$cpp_type, $initial, $values])
{
	$functions = 'function read(mixed $v): ' . $payload . ' { return $v; } '
		. 'function initialize(mixed $v): ' . $payload . ' { $out ' . $payload . ' = $v; return $out; } '
		. 'function consume(' . $payload . ' $v, int &$calls): ' . $payload . ' { $calls++; return $v; } '
		. 'function argument(mixed $v, int &$calls): ' . $payload . ' { return consume($v, $calls); } '
		. 'function once(mixed $v, int &$n): mixed { $n++; return $v; } '
		. 'function store(mixed $v, ' . $payload . ' &$out, int &$n): void { $out = once($v, $n); } ';
	$index = 0;
	foreach ($values as $literal => $expected)
	{
		$fixtures['mixed_extract_' . $payload . '_' . $index] = [
			$functions . '$v mixed = ' . $literal . '; $n = 0; $calls = 0; '
				. '$a ' . $payload . ' = once($v, $n); $b = read(once($v, $n)); '
				. '$c = argument(once($v, $n), $calls); $d = initialize($v); store($v, $d, $n);',
			'local_n.native_value() == 4 && local_calls.native_value() == 1'
				. ' && local_a.native_value() == ' . $expected . ' && local_b.native_value() == ' . $expected
				. ' && local_c.native_value() == ' . $expected . ' && local_d.native_value() == ' . $expected,
		];
		$index++;
	}
	foreach ($failures[$payload] as $index => $literal)
	{
		$fixtures['mixed_failure_' . $payload . '_' . $index] = [
			$functions . '$bad mixed = ' . $literal . ';',
			'([&]() { ' . $cpp_type . ' out{' . $initial . '}; scpp::int_t<> n{0LL}, calls{0LL}; int caught = 0; '
				. 'try { (void)function_read(local_bad); } catch (const std::runtime_error&) { ++caught; } '
				. 'try { (void)function_initialize(local_bad); } catch (const std::runtime_error&) { ++caught; } '
				. 'try { (void)function_argument(local_bad, calls); } catch (const std::runtime_error&) { ++caught; } '
				. 'try { function_store(local_bad, out, n); } catch (const std::runtime_error&) { ++caught; } '
				. 'return caught == 4 && out.native_value() == ' . $initial
				. ' && n.native_value() == 1 && calls.native_value() == 0; }())',
		];
	}
	$fixtures['mixed_failure_' . $payload . '_table'] = [
		$functions,
		'([&]() { scpp::mixed_t bad{scpp::shared<scpp::hash_t<scpp::mixed_t>>()}; '
			. $cpp_type . ' out{' . $initial . '}; scpp::int_t<> n{0LL}; bool caught = false; '
			. 'try { function_store(bad, out, n); } catch (const std::runtime_error&) { caught = true; } '
			. 'return caught && out.native_value() == ' . $initial . ' && n.native_value() == 1'
			. ' && bad.kind() == scpp::mixed_t::kind_t::shared_table_v; }())',
	];
}
$fixtures['mixed_copy_separated_families'] = [
	'function pass(mixed $v): mixed { return $v; } function passdynamic(dynamic $v): dynamic { return $v; }',
	'([&]() { auto object = scpp::dynamic_t<>::make_container(); auto copy = function_passdynamic(object); '
		. 'scpp::mixed_t original{scpp::unique<scpp::hash_t<scpp::mixed_t>>()}; auto value = function_pass(original); '
		. 'return copy.table_if() == object.table_if() && value.table_if() != original.table_if() '
		. '&& value.table_if()->size() == 0 && original.table_if()->size() == 0; }())',
];
return $fixtures;
