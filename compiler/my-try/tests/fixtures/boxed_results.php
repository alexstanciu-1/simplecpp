<?php

/* One Result payload matrix; family-specific expectations only for container copies. */
$boxed_result_fixtures = [];
foreach (['mixed' => 'scpp::mixed_t', 'dynamic' => 'scpp::dynamic_t<>'] as $family => $native) {
	foreach (['result', 'result_or_false', 'result_or_bool'] as $wrapper) {
		$type = $wrapper . '<' . $family . '>';
		$cpp = 'scpp::' . $wrapper . '<B>';
		$prefix = 'boxed_result_' . $family . '_' . $wrapper;
		$alternative = $wrapper === 'result' ? ', error &$err' : ($wrapper === 'result_or_bool' ? ', bool &$flag' : '');
		$outputs = $wrapper === 'result' ? ', $err' : ($wrapper === 'result_or_bool' ? ', $flag' : '');
		$native_outputs = $wrapper === 'result' ? ', err' : ($wrapper === 'result_or_bool' ? ', flag' : '');
		$failure = $wrapper === 'result' ? 'scpp::error_t{scpp::string_t{"failed"}}' : 'scpp::false_sentinel';
		$boxed_result_fixtures[$prefix . '_boundaries'] = [
			'function wrap(' . $family . ' $v): ' . $type . ' { return $v; } '
				. 'function copy(' . $type . ' $v): ' . $type . ' { return $v; } '
				. 'function store(' . $type . ' $v, ' . $family . ' &$out): void { $out = $v; } '
				. 'function consume(' . $family . ' $v): ' . $family . ' { return $v; } '
				. 'function read(' . $type . ' $v): ' . $family . ' { return consume($v); } '
				. 'function elements(' . $family . ' $v): ' . $family . ' { $rs vector<' . $type . '> = [$v]; '
				. '$values vector<' . $family . '> = [$rs[0]]; $values[] = $rs[0]; return $values[1]; } '
				. 'function container(): ' . $type . ' { $v ' . $family . ' = {n: 1}; return $v; } '
				. 'function mutate(' . $type . ' $v): int { $out ' . $family . ' = $v; $out["n"] = 8; return $out["n"]; }',
			'([&]() { using B = ' . $native . '; using R = ' . $cpp . '; '
				. 'auto scalar = function_copy(function_wrap(B{scpp::int_t<>{7LL}})); '
				. 'if (function_read(scalar).get_int().native_value() != 7) return false; '
				. 'if (function_elements(B{scpp::int_t<>{6LL}}).get_int().native_value() != 6) return false; '
				. 'auto tree = function_container(); auto copied = function_copy(tree); '
				. 'if (function_mutate(copied).native_value() != 8) return false; '
				. 'if (tree.value().table_if()->at(scpp::string_t{"n"}).get_int().native_value() != '
				. ($family === 'dynamic' ? '8' : '1') . ') return false; '
				. 'B out{scpp::int_t<>{91LL}}; bool caught = false; try { function_store(R{' . $failure . '}, out); } '
				. 'catch (const std::exception&) { caught = true; } return caught && out.get_int().native_value() == 91; }())',
		];
		$boxed_result_fixtures[$prefix . '_states'] = [
			'function truth(' . $type . ' $v): bool { if ($v) { return true; } return false; } '
				. 'function present(' . $type . ' $v): bool { return isset($v); } '
				. 'function vacant(' . $type . ' $v): bool { return empty($v); } '
				. 'function container(): ' . $type . ' { $v ' . $family . ' = {}; return $v; } '
				. 'function change(' . $family . ' &$v): void { $v["n"] = 9; } '
				. 'function source(' . $type . ' $v, int &$calls): ' . $type . ' { $calls++; return $v; } '
				. 'function extract(' . $type . ' $v, ' . $family . ' &$out, int &$calls' . $alternative . '): bool '
				. '{ return take($out' . $outputs . ') = source($v, $calls); }',
			'([&]() { using B = ' . $native . '; using R = ' . $cpp . '; scpp::int_t<> calls{0LL}; '
				. 'scpp::error_t err{scpp::string_t{"old"}}; scpp::bool_t flag{false}; B out{scpp::int_t<>{91LL}}; '
				. 'for (const auto& value : {B{}, B{scpp::bool_t{false}}, B{scpp::int_t<>{0LL}}, B{scpp::string_t{"0"}}}) { '
				. 'R success{value}; if (function_truth(success).native_value() || !function_present(success).native_value()) return false; '
				. 'if (function_vacant(success).native_value() == (value.kind() == B::kind_t::string_v)) return false; '
				. 'if (!function_extract(success, out, calls' . $native_outputs . ').native_value() || out.kind() != value.kind()) return false; } '
				. ($wrapper === 'result' ? 'if (err.has_error().native_value()) return false; ' : '')
				. 'out = B{scpp::int_t<>{91LL}}; R failed{' . $failure . '}; '
				. 'if (function_extract(failed, out, calls' . $native_outputs . ').native_value() || out.get_int().native_value() != 91 '
				. '|| function_truth(failed).native_value() || function_present(failed).native_value() || !function_vacant(failed).native_value()) return false; '
				. ($wrapper === 'result' ? 'if (!err.has_error().native_value()) return false; ' : '')
				. ($wrapper === 'result_or_bool' ? 'R yes{scpp::true_sentinel}; if (!function_extract(yes, out, calls, flag).native_value() '
					. '|| !flag.native_value() || out.get_int().native_value() != 91 || !function_truth(yes).native_value() '
					. '|| !function_present(yes).native_value() || function_vacant(yes).native_value()) return false; ' : '')
				. 'auto tree = function_container(); if (!function_present(tree).native_value() || !function_vacant(tree).native_value()) return false; '
				. ($family === 'dynamic' ? 'if (!function_truth(tree).native_value()) return false; '
					: 'bool rejected = false; try { (void)function_truth(tree); } catch (const std::exception&) { rejected = true; } if (!rejected) return false; ')
				. 'if (!function_extract(tree, out, calls' . $native_outputs . ').native_value()) return false; function_change(out); '
				. 'return tree.value().table_if()->size() == ' . ($family === 'dynamic' ? '1' : '0')
				. ' && calls.native_value() == ' . ($wrapper === 'result_or_bool' ? '7' : '6') . '; }())',
		];
		$boxed_result_fixtures[$prefix . '_joins'] = [
			'function choose(bool $pick, ' . $type . ' $r, ' . $family . ' $v): ' . $type . ' { return $pick ? $r : $v; } '
				. 'function reverse(bool $pick, ' . $type . ' $r, ' . $family . ' $v): ' . $type . ' { return $pick ? $v : $r; } '
				. 'function fallback(int &$calls): ' . $family . ' { $calls++; return 9; } '
				. 'function elvis(' . $type . ' $r, int &$calls): ' . $type . ' { return $r ?: fallback($calls); }',
			'([&]() { using B = ' . $native . '; using R = ' . $cpp . '; R failed{' . $failure . '}; B v{scpp::int_t<>{7LL}}; '
				. 'if (function_choose(scpp::bool_t{true}, failed, v).has_value().native_value() '
				. '|| function_reverse(scpp::bool_t{false}, failed, v).has_value().native_value()) return false; '
				. 'if (function_choose(scpp::bool_t{false}, failed, v).value().get_int().native_value() != 7 '
				. '|| function_reverse(scpp::bool_t{true}, failed, v).value().get_int().native_value() != 7) return false; '
				. 'scpp::int_t<> calls{0LL}; auto zero = function_elvis(R{B{scpp::int_t<>{0LL}}}, calls); '
				. 'auto yes = function_elvis(R{v}, calls); return calls.native_value() == 1 '
				. '&& zero.value().get_int().native_value() == 9 && yes.value().get_int().native_value() == 7; }())',
		];
		if ($wrapper !== 'result_or_bool') {
			$boxed_result_fixtures[$prefix . '_coalesce'] = [
				'function source(' . $type . ' $v, int &$calls): ' . $type . ' { $calls++; return $v; } '
					. 'function fallback(int &$calls): ' . $family . ' { $calls += 10; return 9; } '
					. 'function select(' . $type . ' $v, int &$calls): ' . $family . ' { return source($v, $calls) ?? fallback($calls); }',
				'([&]() { using B = ' . $native . '; using R = ' . $cpp . '; scpp::int_t<> calls{0LL}; '
					. 'auto nil = function_select(R{B{}}, calls); auto zero = function_select(R{B{scpp::int_t<>{0LL}}}, calls); '
					. 'auto failed = function_select(R{' . $failure . '}, calls); return nil.is_null().native_value() '
					. '&& zero.get_int().native_value() == 0 && failed.get_int().native_value() == 9 && calls.native_value() == 13; }())',
			];
		}
	}
}
return $boxed_result_fixtures;
