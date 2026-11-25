<?php

/* Independent native expectations for condition consumers and owned, lazy joins. */
return [
	'dynamic_condition_matrix' => [
		'function truth(dynamic $v): bool { if ($v) { return true; } return false; } '
			. 'function negate(dynamic $v): bool { return !$v; } '
			. 'function loops(): int { $v dynamic = 2; $n = 0; while ($v) { $n++; $v = false; } '
			. 'do { $n++; } while ($v); for (; $v;) { $n++; } '
			. 'if ($v) { return 0; } elseif (dynamic::{}) { $n++; } return $n; }',
		'([&]() { using D = scpp::dynamic_t<>; '
			. 'for (const auto& v : {D{}, D{scpp::bool_t{false}}, D{scpp::int_t<>{0LL}}, '
			. 'D{scpp::float_t{0.0}}, D{scpp::string_t{""}}, D{scpp::string_t{"0"}}}) '
			. 'if (function_truth(v).native_value() || !function_negate(v).native_value()) return false; '
			. 'for (const auto& v : {D{scpp::bool_t{true}}, D{scpp::int_t<>{1LL}}, '
			. 'D{scpp::float_t{0.5}}, D{scpp::string_t{"false"}}, D::make_container()}) '
			. 'if (!function_truth(v).native_value() || function_negate(v).native_value()) return false; '
			. 'return function_loops().native_value() == 3; }())',
	],
	'dynamic_logical_lazy' => [
		'function tick(int &$n): dynamic { $n++; return true; } '
			. 'function run(): int { $n = 0; $off dynamic = false; $on dynamic = true; '
			. '$a = $off && tick($n); $b = $on || tick($n); '
			. '$c = $on && tick($n); $d = $off || tick($n); '
			. '$e = 1 && $on; $f = $off || 1.0; '
			. 'if ($a || !$b || !$c || !$d || !$e || !$f) { return 99; } return $n; }',
		'function_run().native_value() == 2',
	],
	'dynamic_selection_lazy' => [
		'function source(int &$n, dynamic $v): dynamic { $n++; return $v; } '
			. 'function fallback(int &$n): int { $n += 10; return 9; } '
			. 'function elvis(dynamic $v, int &$n): dynamic { return source($n, $v) ?: fallback($n); } '
			. 'function coalesce(dynamic $v, int &$n): dynamic { return source($n, $v) ?? fallback($n); } '
			. 'function choose(bool $pick, int &$n): dynamic { return $pick ? source($n, 7) : fallback($n); }',
		'([&]() { using D = scpp::dynamic_t<>; scpp::int_t<> n{0LL}; '
			. 'auto a = function_elvis(D{scpp::int_t<>{0LL}}, n); '
			. 'auto b = function_coalesce(D{scpp::int_t<>{0LL}}, n); '
			. 'auto c = function_coalesce(D{}, n); '
			. 'auto d = function_choose(scpp::bool_t{true}, n); '
			. 'auto e = function_choose(scpp::bool_t{false}, n); '
			. 'return n.native_value() == 34 && a.get_int().native_value() == 9 '
			. '&& b.get_int().native_value() == 0 && c.get_int().native_value() == 9 '
			. '&& d.get_int().native_value() == 7 && e.get_int().native_value() == 9; }())',
	],
	'dynamic_selection_identity' => [
		'function run(): int { $original dynamic = {n: 1}; '
			. '$a = true ? $original : 7; $b = $original ?: 8; $c = $original ?? 9; '
			. '$a["n"] = 4; $b["extra"] = 5; $c["last"] = 6; '
			. '$a = 99; $n int = $original["n"]; $extra int = $original["extra"]; '
			. '$last int = $original["last"]; return $n + $extra + $last; } '
			. 'function scalar(): int { $a dynamic = 7; $b = $a ?? 0; $b = 9; return $a; }',
		'function_run().native_value() == 15 && function_scalar().native_value() == 7',
	],
	'dynamic_wrapper_coalesce' => [
		'function nullable(): dynamic { $v ?int = null; $fallback dynamic = 9; return $v ?? $fallback; } '
			. 'function present(): dynamic { $v ?int = 0; $fallback dynamic = 9; return $v ?? $fallback; } '
			. 'function result(): dynamic { $v result<int> = 0; $fallback dynamic = 9; return $v ?? $fallback; } '
			. 'function reverse(): dynamic { $fallback dynamic = 9; return 7 ?? $fallback; }',
		'function_nullable().get_int().native_value() == 9 && function_present().get_int().native_value() == 0 '
			. '&& function_result().get_int().native_value() == 0 && function_reverse().get_int().native_value() == 7',
	],
];
