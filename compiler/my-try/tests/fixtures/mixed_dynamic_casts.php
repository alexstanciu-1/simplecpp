<?php

/* Explicit bridges preserve handles and tags; extraction checks the actual payload. */
return [
	'mixed_dynamic_cast_identity' => [
		'function box(dynamic $v): mixed { return (mixed)$v; } '
			. 'function unbox(mixed $v): dynamic { return (dynamic)$v; }',
		'([&]() { auto table = scpp::shared<scpp::hash_t<scpp::mixed_t>>(); '
			. 'auto boxed = function_box(table); auto copied = function_unbox(boxed); '
			. 'scpp::mixed_t shared{table}; auto extracted = function_unbox(shared); '
			. 'auto reboxed = function_box(extracted); '
			. 'return boxed.dynamic_if() && boxed.dynamic_if()->get() == table.get()'
			. ' && copied.get() == table.get() && extracted.get() == table.get()'
			. ' && shared.shared_table_if() && shared.shared_table_if()->get() == table.get()'
			. ' && reboxed.dynamic_if() && reboxed.dynamic_if()->get() == table.get(); }())',
	],
	'mixed_dynamic_cast_empty' => [
		'function boxempty(): mixed { $v dynamic; return (mixed)$v; } '
			. 'function unbox(mixed $v): dynamic { return (dynamic)$v; } '
			. 'function roundtrip(mixed $v): mixed { return (mixed)(dynamic)$v; }',
		'([&]() { auto empty = function_boxempty(); scpp::mixed_t nil{scpp::null}; '
			. 'auto handle = function_unbox(nil); auto round = function_roundtrip(nil); '
			. 'return empty.dynamic_if() && !empty.dynamic_if()->get() && !empty.is_null().native_value()'
			. ' && !handle.get() && round.dynamic_if() && !round.dynamic_if()->get()'
			. ' && nil.is_null().native_value() && !function_unbox(empty).get(); }())',
	],
	'mixed_dynamic_cast_failure' => [
		'function once(mixed $v, int &$n): mixed { $n++; return $v; } '
			. 'function store(mixed $v, dynamic &$out, int &$n): void { $out = (dynamic)once($v, $n); }',
		'([&]() { auto table = scpp::shared<scpp::hash_t<scpp::mixed_t>>(); auto out = table; '
			. 'scpp::int_t<> n{0LL}; int caught = 0; '
			. 'for (const auto& bad : {scpp::mixed_t{false}, scpp::mixed_t{scpp::int_t<>{7LL}}, '
			. 'scpp::mixed_t{scpp::float_t{1.5}}, scpp::mixed_t{scpp::string_t{"text"}}}) { '
			. 'try { function_store(bad, out, n); } catch (const std::runtime_error&) { ++caught; } '
			. 'if (out.get() != table.get()) return false; } '
			. 'function_store(scpp::mixed_t{scpp::dynamic_box(table)}, out, n); '
			. 'return caught == 4 && n.native_value() == 5 && out.get() == table.get(); }())',
	],
	'mixed_dynamic_cast_ownership_rejection' => [
		'function unbox(mixed $v): dynamic { return (dynamic)$v; }',
		'([&]() { auto table = scpp::shared<scpp::hash_t<scpp::mixed_t>>(); '
			. 'scpp::mixed_t weak{scpp::weak(table)}; '
			. 'scpp::mixed_t owned{scpp::unique<scpp::hash_t<scpp::mixed_t>>()}; int caught = 0; '
			. 'try { function_unbox(weak); } catch (const std::runtime_error&) { ++caught; } '
			. 'try { function_unbox(owned); } catch (const std::runtime_error&) { ++caught; } '
			. 'return caught == 2 && owned.table_if() && weak.weak_table_if(); }())',
	],
];
