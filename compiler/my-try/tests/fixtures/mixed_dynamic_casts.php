<?php

/* v0.2 family casts preserve scalar/null values and reject every container. */
return [
	'mixed_dynamic_cast_scalars' => [
		'function box(dynamic $v): mixed { return (mixed)$v; } '
			. 'function unbox(mixed $v): dynamic { return (dynamic)$v; }',
		'([&]() { for (const auto& original : {scpp::mixed_t{false}, scpp::mixed_t{true}, '
			. 'scpp::mixed_t{scpp::int_t<>{7LL}}, scpp::mixed_t{scpp::float_t{1.5}}, '
			. 'scpp::mixed_t{scpp::string_t{"text"}}}) { '
			. 'auto dynamic = function_unbox(original); auto round = function_box(dynamic); '
			. 'if (round.kind() != original.kind() || !(round == original).native_value()) return false; '
			. 'auto copy = dynamic; dynamic = scpp::dynamic_t<>{}; '
			. 'if (!(function_box(copy) == original).native_value()) return false; } return true; }())',
	],
	'mixed_dynamic_cast_null' => [
		'function boxempty(): mixed { $v dynamic; return (mixed)$v; } '
			. 'function unbox(mixed $v): dynamic { return (dynamic)$v; } '
			. 'function roundtrip(mixed $v): mixed { return (mixed)(dynamic)$v; }',
		'function_boxempty().is_null().native_value() '
			. '&& function_unbox(scpp::mixed_t{}).is_null().native_value() '
			. '&& function_roundtrip(scpp::mixed_t{}).is_null().native_value()',
	],
	'mixed_dynamic_cast_failure' => [
		'function once(mixed $v, int &$n): mixed { $n++; return $v; } '
			. 'function store(mixed $v, dynamic &$out, int &$n): void { $out = (dynamic)once($v, $n); } '
			. 'function reverse(dynamic $v, mixed &$out): void { $out = (mixed)$v; }',
		'([&]() { auto out = scpp::dynamic_t<>::make_container(); auto alias = out; '
			. 'auto table = scpp::shared<scpp::hash_t<scpp::mixed_t>>(); scpp::int_t<> n{0LL}; int caught = 0; '
			. 'for (const auto& bad : {scpp::mixed_t{table}, scpp::mixed_t{scpp::weak(table)}, '
			. 'scpp::mixed_t{scpp::unique<scpp::hash_t<scpp::mixed_t>>()}}) { '
			. 'try { function_store(bad, out, n); } catch (const std::runtime_error&) { ++caught; } '
			. 'if (out.table_if() != alias.table_if()) return false; } '
			. 'scpp::mixed_t kept{scpp::int_t<>{9LL}}; '
			. 'try { function_reverse(alias, kept); } catch (const std::runtime_error&) { ++caught; } '
			. 'if (kept.get_int().native_value() != 9) return false; '
			. 'function_store(scpp::mixed_t{scpp::int_t<>{7LL}}, out, n); '
			. 'return caught == 4 && n.native_value() == 4 && out.get_int().native_value() == 7 '
			. '&& alias.table_if() && alias.get_table().empty().native_value(); }())',
	],
];
