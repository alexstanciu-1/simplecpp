<?php

/* Wrapper-state selection followed by scalar boxing, not wrapper boxing or truthiness. */
$fixtures = [];
$payloads = [
	'bool' => ['scpp::bool_t', 'bool_value', ['false', 'true']],
	'int' => ['scpp::int_t<>', 'int_value', ['0LL', '7LL']],
	'float' => ['scpp::float_t', 'float_value', ['0.0', '1.5']],
	'string' => ['scpp::string_t', 'get_string', ['""', '"0"', '"text"']],
];
foreach (['nullable', 'result', 'result_or_false'] as $family)
{
	foreach ($payloads as $payload => [$cpp_payload, $accessor, $values])
	{
		$wrapper = $family === 'nullable' ? '?' . $payload : $family . '<' . $payload . '>';
		$cpp_wrapper = 'scpp::' . $family . '<' . $cpp_payload . '>';
		$absent = $family === 'result' ? 'scpp::error_t{scpp::string_t{"failed"}}'
			: ($family === 'nullable' ? 'scpp::null' : 'scpp::false_sentinel');
		$source = 'function once(' . $wrapper . ' $v, int &$n): ' . $wrapper . ' { $n++; return $v; } '
			. 'function fallback(mixed $v, int &$n): mixed { $n++; return $v; } '
			. 'function choose(' . $wrapper . ' $v, mixed $other, int &$n, int &$f): mixed '
			. '{ return once($v, $n) ?? fallback($other, $f); } ';
		$probe = '([&]() { scpp::mixed_t fallback{scpp::string_t{"fallback"}}; ';
		foreach ($values as $expected)
		{
			$probe .= '{ ' . $cpp_wrapper . ' original{' . $cpp_payload . '{' . $expected . '}}; '
				. 'scpp::int_t<> n{0LL}, f{0LL}; auto out = function_choose(original, fallback, n, f); '
				. 'if (n.native_value() != 1 || f.native_value() != 0 || !original.has_value().native_value()'
				. ' || out.' . $accessor . '().native_value() != ' . $expected . ') return false; '
				. 'original = ' . $cpp_wrapper . '{' . $absent . '}; '
				. 'if (out.' . $accessor . '().native_value() != ' . $expected . ') return false; } ';
		}
		$probe .= $cpp_wrapper . ' absent{' . $absent . '}; scpp::int_t<> n{0LL}, f{0LL}; '
			. 'auto text = function_choose(absent, fallback, n, f); '
			. 'fallback = scpp::mixed_t{scpp::string_t{"changed"}}; '
			. 'auto nil = function_choose(absent, scpp::mixed_t{scpp::null}, n, f); '
			. 'auto table = scpp::shared<scpp::hash_t<scpp::mixed_t>>(); scpp::mixed_t object{scpp::dynamic_box(table)}; '
			. 'auto handle = function_choose(absent, object, n, f); '
			. 'return n.native_value() == 3 && f.native_value() == 3 && !absent.has_value().native_value()'
			. ' && text.get_string().native_value() == "fallback" && nil.is_null().native_value()'
			. ' && handle.dynamic_if()->get() == table.get() && object.dynamic_if()->get() == table.get()'
			. ' && table->empty().native_value(); }())';
		$fixtures['mixed_wrapper_coalesce_' . $family . '_' . $payload] = [$source, $probe];
	}
	$wrapper = $family === 'nullable' ? '?int' : $family . '<int>';
	$cpp_wrapper = 'scpp::' . $family . '<scpp::int_t<>>';
	$absent = $family === 'result' ? 'scpp::error_t{scpp::string_t{"failed"}}'
		: ($family === 'nullable' ? 'scpp::null' : 'scpp::false_sentinel');
	$fixtures['mixed_wrapper_coalesce_failure_' . $family] = [
		'function fail(int &$n): mixed { $n++; return 1 / 0; } '
			. 'function sourcefail(int &$n): ' . $wrapper . ' { $n++; return 1 / 0; } '
			. 'function store(' . $wrapper . ' $v, mixed &$out, int &$n): void { $out = $v ?? fail($n); } '
			. 'function leftfail(mixed &$out, int &$n, int &$f): void { $out = sourcefail($n) ?? fail($f); }',
		'([&]() { ' . $cpp_wrapper . ' good{scpp::int_t<>{0LL}}, bad{' . $absent . '}; '
			. 'scpp::mixed_t out{scpp::string_t{"old"}}; scpp::int_t<> n{0LL}, f{0LL}; '
			. 'function_store(good, out, n); if (out.int_value().native_value() != 0 || n.native_value() != 0) return false; '
			. 'out = scpp::mixed_t{scpp::string_t{"old"}}; int caught = 0; '
			. 'try { function_store(bad, out, n); } catch (const std::runtime_error&) { ++caught; } '
			. 'try { function_leftfail(out, n, f); } catch (const std::runtime_error&) { ++caught; } '
			. 'return caught == 2 && n.native_value() == 2 && f.native_value() == 0'
			. ' && out.get_string().native_value() == "old"; }())',
	];
}
return $fixtures;
