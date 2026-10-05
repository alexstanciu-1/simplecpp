<?php

/* Generated-source proofs of tagged bindings and same-family container signatures. */
return [
	'dynamic_scalar_copy' => [
		'function run(): int { $a dynamic = 7; $copy = $a; $a = 9; $out int = $copy; return $out; }',
		'function_run().native_value() == 7',
	],
	'dynamic_scalar_boundaries' => [
		'function receive(dynamic $v): dynamic { return $v; } '
			. 'function flag(): bool { $v = receive(false); $out bool = $v; return $out; } '
			. 'function number(): float { return receive(1.5); } '
			. 'function text(): string { return receive("hello"); } '
			. 'function absent(): dynamic { return receive(null); } '
			. 'function explicitcast(): int { $v dynamic = "42"; return (int)$v; }',
		'!function_flag().native_value() && function_number().native_value() == 1.5 '
			. '&& function_text().native_value() == "hello" && function_absent().is_null().native_value() '
			. '&& function_explicitcast().native_value() == 42',
	],
	'dynamic_required_failure' => [
		'function store(dynamic $v, int &$out): void { $out = $v; } '
			. 'function replace(dynamic &$out): void { $out = "new"; }',
		'([&]() { scpp::int_t<> out{9LL}; int caught = 0; '
			. 'for (const auto& bad : {scpp::dynamic_t<>{}, scpp::dynamic_t<>::make_container()}) { '
			. 'try { function_store(bad, out); } catch (const std::runtime_error&) { ++caught; } '
			. 'if (out.native_value() != 9) return false; } '
			. 'function_store(scpp::dynamic_t<>{scpp::int_t<>{7LL}}, out); '
			. 'auto binding = scpp::dynamic_t<>::make_container(); auto alias = binding; function_replace(binding); '
			. 'return caught == 2 && out.native_value() == 7 && binding.get_string().native_value() == "new" '
			. '&& alias.table_if() != nullptr; }())',
	],
	'dynamic_nested_identity' => [
		'function mutate(dynamic $v): void { $v["child"]["n"] = 9; $v = 0; } '
			. 'function run(): int { $a dynamic = {child: {n: 1}}; $alias = $a; mutate($alias); '
			. '$n int = $a["child"]["n"]; return $n; }',
		'function_run().native_value() == 9',
	],
	'dynamic_read_family' => [
		'function missing(): dynamic { $a dynamic = {n: 1}; return $a["absent"]; } '
			. 'function nested(): dynamic { $a dynamic = {child: dynamic::{n: 7}}; return $a["child"]; }',
		'function_missing().is_null().native_value() '
			. '&& function_nested().get_table().at(scpp::string_t{"n"}).get_int().native_value() == 7',
	],
	'dynamic_iteration_values' => [
		'function run(): int { $a dynamic = [1, 2, 3]; $a[] = 4; $sum = 0; '
			. 'foreach ($a as $key => $item) { $n int = $item; $sum += $n; } return $sum; } '
			. 'function typed(): int { $a dynamic = [5, 6]; $sum = 0; $item int = 0; '
			. 'foreach ($a as $item) { $sum += $item; } return $sum; }',
		'function_run().native_value() == 10 && function_typed().native_value() == 11',
	],
	'dynamic_nested_iteration' => [
		'function run(): int { $rows dynamic = [[1, 2], [3]]; $sum = 0; '
			. 'foreach ($rows as $row) { foreach ($row as $item) { $n int = $item; $sum += $n; } } return $sum; }',
		'function_run().native_value() == 6',
	],
	'dynamic_checked_paths' => [
		'function run(): bool { $a dynamic = {child: {n: 1}}; unset($a["child"]["n"]); '
			. 'return empty($a["child"]["n"]) && !isset($a["child"]["n"]); } '
			. 'function absentwrite(): void { $a dynamic = null; $a["n"] = 1; } '
			. 'function scalariteration(): void { $a dynamic = 7; foreach ($a as $item) {} }',
		'([&]() { int caught = 0; try { function_absentwrite(); } catch (const std::runtime_error&) { ++caught; } '
			. 'try { function_scalariteration(); } catch (const std::runtime_error&) { ++caught; } '
			. 'return caught == 2 && function_run().native_value(); }())',
	],
	'dynamic_in_typed_containers' => [
		'function run(): int { $items vector<dynamic> = [1, {n: 2}]; '
			. '$row dynamic = $items[1]; $row["n"] = 7; $n int = $items[1]["n"]; return $n; }',
		'function_run().native_value() == 7',
	],
	'dynamic_duplicate_rejection' => [
		'function fail(): dynamic { return ["1" => 7, 1 => 8]; }',
		'([&]() { try { function_fail(); } catch (const std::runtime_error&) { return true; } return false; }())',
	],
];
