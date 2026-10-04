<?php

/* Source Result boundaries plus independent native value/state observations. */
$fixtures = [];
foreach (['result', 'result_or_false', 'result_or_bool'] as $family)
{
	$fixtures['result_construct_' . $family] = [
		'$i ' . $family . '<int> = 0; $b ' . $family . '<bool> = false; '
			. '$f ' . $family . '<float> = 1.5; $s ' . $family . '<string> = ""; '
			. '$n uint8 = 7; $u ' . $family . '<byte> = $n;',
		'local_i.has_value().native_value() && local_i.value().native_value() == 0'
			. ' && local_b.has_value().native_value() && !local_b.value().native_value()'
			. ' && local_f.has_value().native_value() && local_f.value().native_value() == 1.5'
			. ' && local_s.has_value().native_value() && local_s.value().native_value().empty()'
			. ' && local_u.has_value().native_value() && local_u.value().native_value() == 7',
	];
	$fixtures['result_copy_' . $family] = [
		'function pass(' . $family . '<string> $v): ' . $family . '<string> { return $v; } '
			. 'function change(' . $family . '<string> $v): ' . $family . '<string> { $v = "changed"; return $v; } '
			. '$a ' . $family . '<string> = "old"; $b = pass($a); $c = change($a); '
			. '$d ' . $family . '<string> = "first"; $d = $a; $a = "new";',
		'local_a.value().native_value() == "new" && local_b.value().native_value() == "old"'
			. ' && local_c.value().native_value() == "changed" && local_d.value().native_value() == "old"',
	];
	$fixtures['result_boundaries_' . $family] = [
		'function produce(int &$n): int { $n++; return $n; } '
			. 'function wrap(int $n): ' . $family . '<int> { return $n; } '
			. 'function pass(' . $family . '<int> $v): ' . $family . '<int> { return $v; } '
			. 'function replace(' . $family . '<int> &$v): void { $v = 7; } '
			. '$n = 0; $a = pass(produce($n)); $b = wrap(produce($n)); '
			. '$c ' . $family . '<int> = 0; $c = produce($n); $d = $a; replace($a);',
		'local_n.native_value() == 3 && local_a.value().native_value() == 7'
			. ' && local_b.value().native_value() == 2 && local_c.value().native_value() == 3'
			. ' && local_d.value().native_value() == 1',
	];
}
foreach (['result_or_false', 'result_or_bool'] as $family)
{
	$fixtures['result_false_' . $family] = [
		'function absent(): ' . $family . '<int> { return false_sentinel; } '
			. 'function pass(' . $family . '<int> $v): ' . $family . '<int> { return $v; } '
			. '$a ' . $family . '<int> = null; $b ' . $family . '<int> = nullopt; '
			. '$c = pass(false_sentinel); $d = absent(); $e = $c; $c = 7;',
		'local_a.is_false().native_value() && local_b.is_false().native_value()'
			. ' && local_c.has_value().native_value() && local_c.value().native_value() == 7'
			. ' && local_d.is_false().native_value() && local_e.is_false().native_value()',
	];
	$fixtures['result_bool_' . $family] = [
		'$a ' . $family . '<bool> = false; $b = $a; $a = true; '
			. '$c ' . $family . '<bool> = false_sentinel; $d = $c; $c = false; '
			. '$e ' . $family . '<bool> = true; $e = null; '
			. '$f ' . $family . '<bool> = true; $f = nullopt;',
		'local_a.has_value().native_value() && local_a.value().native_value()'
			. ' && local_b.has_value().native_value() && !local_b.value().native_value()'
			. ' && local_c.has_value().native_value() && !local_c.value().native_value()'
			. ' && local_d.is_false().native_value() && local_e.is_false().native_value()'
			. ' && local_f.is_false().native_value()',
	];
}
$fixtures['result_true_states'] = [
	'function yes(): result_or_bool<bool> { return true_sentinel; } '
		. 'function pass(result_or_bool<int> $v): result_or_bool<int> { return $v; } '
		. '$a = yes(); $b = $a; $a = false; $c = pass(true_sentinel); '
		. '$d result_or_bool<string> = "payload"; $d = true_sentinel; $e = $d; $d = false_sentinel;',
	'local_a.has_value().native_value() && !local_a.value().native_value()'
		. ' && local_b.is_true().native_value() && local_c.is_true().native_value()'
		. ' && local_d.is_false().native_value() && local_e.is_true().native_value()',
];
$fixtures['result_error_copy_bridge'] = [
	'function pass(result<int> $v): result<int> { return $v; }',
	'function_pass(scpp::result<scpp::int_t<>>{scpp::error_t{scpp::string_t("failure")}})'
		. '.error()->get_message().native_value() == "failure"',
];
$payloads = [
	'int' => ['0', 'scpp::int_t<>', '0LL', '9LL'],
	'bool' => ['false', 'scpp::bool_t', 'false', 'true'],
	'float' => ['0.0', 'scpp::float_t', '0.0', '9.5'],
	'string' => ['""', 'scpp::string_t', '""', '"unchanged"'],
];
foreach (['result', 'result_or_false', 'result_or_bool'] as $family)
{
	foreach ($payloads as $payload => [$literal, $cpp_type, $native_value, $initial])
	{
		$wrapper = $family . '<' . $payload . '>';
		$functions = 'function read(' . $wrapper . ' $r): ' . $payload . ' { return $r; } '
			. 'function initialize(' . $wrapper . ' $r): ' . $payload . ' { $x ' . $payload . ' = $r; return $x; } '
			. 'function consume(' . $payload . ' $v, int &$calls): ' . $payload . ' { $calls++; return $v; } '
			. 'function argument(' . $wrapper . ' $r, int &$calls): ' . $payload . ' { return consume($r, $calls); } '
			. 'function once(' . $wrapper . ' $r, int &$n): ' . $wrapper . ' { $n++; return $r; } '
			. 'function store(' . $wrapper . ' $r, ' . $payload . ' &$out, int &$n): void { $out = once($r, $n); } ';
		$fixtures['result_extract_' . $family . '_' . $payload] = [
			$functions . '$r ' . $wrapper . ' = ' . $literal . '; $n = 0; $calls = 0; '
				. '$a ' . $payload . ' = once($r, $n); $b = read(once($r, $n)); '
				. '$c = argument(once($r, $n), $calls); $d = initialize($r); store($r, $d, $n);',
			'local_n.native_value() == 4 && local_calls.native_value() == 1'
				. ' && local_a.native_value() == ' . $native_value . ' && local_b.native_value() == ' . $native_value
				. ' && local_c.native_value() == ' . $native_value . ' && local_d.native_value() == ' . $native_value
				. ' && local_r.has_value().native_value()',
		];
		$states = $family === 'result'
			? ['error' => 'scpp::error_t{scpp::string_t("failure")}']
			: ['false' => 'scpp::false_sentinel'];
		if ($family === 'result_or_bool') {
			$states['true'] = 'scpp::true_sentinel';
		}
		foreach ($states as $state => $sentinel)
		{
			// The native bridge supplies non-value input; source error construction stays deferred.
			$probe = '([&]() { scpp::' . $family . '<' . $cpp_type . '> bad{' . $sentinel . '}; '
				. $cpp_type . ' out{' . $initial . '}; scpp::int_t<> n{0LL}, calls{0LL}; int caught = 0; '
				. 'try { (void)function_read(bad); } catch (const std::runtime_error& e) { '
				. 'if (std::string(e.what()).find("requires a present value") == std::string::npos) return false; ++caught; } '
				. 'try { (void)function_initialize(bad); } catch (const std::runtime_error&) { ++caught; } '
				. 'try { (void)function_argument(bad, calls); } catch (const std::runtime_error&) { ++caught; } '
				. 'try { function_store(bad, out, n); } catch (const std::runtime_error&) { ++caught; } '
				. 'return caught == 4 && out.native_value() == ' . $initial
				. ' && n.native_value() == 1 && calls.native_value() == 0 && !bad.has_value().native_value(); }())';
			$fixtures['result_extract_failure_' . $family . '_' . $payload . '_' . $state] = [$functions, $probe];
		}
	}
}
$fixtures['result_extract_owned_string'] = [
	'$r result<string> = "old"; $s string = $r; $r = "new";',
	'local_s.native_value() == "old" && local_r.value().native_value() == "new"',
];
$fixtures['result_extract_alias'] = [
	'$n uint8 = 7; $r result<byte> = $n; $v uint8 = $r;',
	'local_v.native_value() == 7',
];
foreach (['?int', 'result_or_false<int>'] as $index => $family)
{
	$fixtures['result_take_optional_' . $index] = [
		'function source(int &$n): ' . $family . ' { $n++; return 0; } '
			. '$n = 0; $v = 9; $yes = take($v) = source($n); $present = $v; '
			. '$r ' . $family . ' = null; $v = 7; $no = take($v, $r); '
			. '$skipped = false && (take($v) = source($n));',
		'local_n.native_value() == 1 && local_yes.native_value() && local_present.native_value() == 0'
			. ' && !local_no.native_value() && local_v.native_value() == 7 && !local_skipped.native_value()',
	];
}
$fixtures['result_take_bool_states'] = [
	'$v = true; $flag = true; $r result_or_bool<bool> = false; '
		. '$a = take($v, $flag) = $r; $payload = $v; $unchanged = $flag; '
		. '$r = false_sentinel; $b = take($v, $flag, $r); $off = $flag; '
		. '$r = true_sentinel; $c = take($v, $flag) = $r;',
	'local_a.native_value() && !local_payload.native_value() && local_unchanged.native_value()'
		. ' && !local_b.native_value() && !local_off.native_value() && local_c.native_value()'
		. ' && local_flag.native_value() && !local_v.native_value()',
];
$fixtures['result_take_error_states'] = [
	'function extract(result<int> $r, int &$v, error &$e): bool { return take($v, $e) = $r; } '
		. 'function usable(error $e): bool { return !$e; } '
		. 'function failed(error $e): bool { if ($e) { return true; } return false; } '
		. '$e error = error::$no_error; $fresh error; $r result<int> = 0; $v = 9; '
		. 'if (take($v, $e) = $r) { $v = $v + 1; } $clean = !$e;',
	'local_v.native_value() == 1 && local_clean.native_value() && !local_fresh.has_error().native_value()'
		. ' && ([&]() { scpp::int_t<> out{9LL}; scpp::error_t error; '
		. 'scpp::result<scpp::int_t<>> bad{scpp::error_t{scpp::string_t("")}}; '
		. 'if (function_extract(bad, out, error).native_value() || out.native_value() != 9'
		. ' || !function_failed(error).native_value() || function_usable(error).native_value()) return false; '
		. 'scpp::result<scpp::int_t<>> good{scpp::int_t<>{0LL}}; '
		. 'return function_extract(good, out, error).native_value() && out.native_value() == 0'
		. ' && function_usable(error).native_value() && !function_failed(error).native_value(); }())',
];
$fixtures['result_take_source_before_output'] = [
	'function source(int &$v): ?int { $v = 12; return null; } '
		. '$v = 7; $ok = take($v) = source($v);',
	'!local_ok.native_value() && local_v.native_value() == 12',
];
$fixtures['result_take_owned_string'] = [
	'$r result<string> = ""; $v = "old"; $e error; $a = take($v, $e) = $r; '
		. '$r = "new"; $n ?float = 0.0; $f = 1.5; $b = take($f) = $n;',
	'local_a.native_value() && local_v.native_value().empty() && !local_e.has_error().native_value()'
		. ' && local_r.value().native_value() == "new" && local_b.native_value() && local_f.native_value() == 0.0',
];
$fixtures['result_take_exception_propagates'] = [
	'function source(result<int> $r, int &$n): ?int { $n++; $v int = $r; return $v; } '
		. 'function extract(result<int> $r, int &$v, int &$n): bool { return take($v) = source($r, $n); }',
	'([&]() { scpp::result<scpp::int_t<>> bad{scpp::error_t{scpp::string_t("bad")}}; '
		. 'scpp::int_t<> out{7LL}, n{0LL}; try { (void)function_extract(bad, out, n); } '
		. 'catch (const std::runtime_error&) { return out.native_value() == 7 && n.native_value() == 1; } '
		. 'return false; }())',
];
return $fixtures;
