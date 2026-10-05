<?php

/* Native-bool short circuit, condition truthiness, ordered effects and skipped failures. */
$fixtures = [];
foreach (['and' => '&&', 'or' => '||', 'wordand' => 'and', 'wordor' => 'or'] as $name => $op)
{
	$is_and = $op === '&&' || $op === 'and';
	$source = 'function left(mixed $v, int &$n): mixed { $n++; return $v; } '
		. 'function right(mixed $v, int &$n): mixed { $n++; return $v; } '
		. 'function evaluate(mixed $a, mixed $b, int &$l, int &$r): bool { return (left($a, $l) ' . $op . ' right($b, $r)); } '
		. 'function mutate(mixed &$v): bool { return ($v++ ' . $op . ' $v++); } '
		. 'function failure(mixed $a): bool { return ($a ' . $op . ' (1 / 0)); } ';
	$probe = '([&]() { std::vector<scpp::mixed_t> values{scpp::mixed_t{scpp::null}, scpp::mixed_t{false}, '
		. 'scpp::mixed_t{scpp::int_t<>{0LL}}, scpp::mixed_t{scpp::float_t{0.0}}, scpp::mixed_t{scpp::string_t{""}}, '
		. 'scpp::mixed_t{scpp::string_t{"0"}}, scpp::mixed_t{true}, scpp::mixed_t{scpp::int_t<>{2LL}}, '
		. 'scpp::mixed_t{scpp::float_t{-1.5}}, scpp::mixed_t{scpp::string_t{"false"}}}; '
		. 'for (int i = 0; i < 10; ++i) for (int j = 0; j < 10; ++j) { '
		. 'scpp::int_t<> l{0LL}, r{0LL}; bool a = i >= 6, b = j >= 6; '
		. 'bool expected = a ' . ($is_and ? '&&' : '||') . ' b; bool needed = ' . ($is_and ? 'a' : '!a') . '; '
		. 'if (function_evaluate(values[i], values[j], l, r).native_value() != expected '
		. '|| l.native_value() != 1 || r.native_value() != int(needed)) return false; } '
		. 'scpp::mixed_t bad{scpp::unique<scpp::hash_t<scpp::mixed_t>>()}; '
		. 'scpp::mixed_t skip{' . ($is_and ? 'false' : 'true') . '}, need{' . ($is_and ? 'true' : 'false') . '}; '
		. 'scpp::int_t<> l{0LL}, r{0LL}; function_evaluate(skip, bad, l, r); '
		. 'if (l.native_value() != 1 || r.native_value() != 0) return false; int caught = 0; '
		. 'try { function_evaluate(need, bad, l, r); } catch (const std::runtime_error&) { ++caught; } '
		. 'if (l.native_value() != 2 || r.native_value() != 1) return false; '
		. 'try { function_evaluate(bad, need, l, r); } catch (const std::runtime_error&) { ++caught; } '
		. 'if (l.native_value() != 3 || r.native_value() != 1) return false; function_failure(skip); '
		. 'try { function_failure(need); } catch (const std::runtime_error&) { ++caught; } '
		. 'scpp::mixed_t zero{scpp::int_t<>{0LL}}, one{scpp::int_t<>{1LL}}; '
		. 'function_mutate(zero); function_mutate(one); '
		. 'return caught == 3 && zero.int_value().native_value() == ' . ($is_and ? '1' : '2')
		. ' && one.int_value().native_value() == ' . ($is_and ? '3' : '2') . '; }())';
	$fixtures['mixed_logical_' . $name] = [$source, $probe];
}
foreach (['bool' => 'scpp::bool_t{true}', 'int' => 'scpp::int_t<>{2LL}', 'float' => 'scpp::float_t{0.5}'] as $type => $native)
{
	$fixtures['mixed_logical_peer_' . $type] = [
		'function evaluate(mixed $a, ' . $type . ' $b): bool { return ($a && $b) && ($b && $a) && ($a || $b) && ($b || $a); }',
		'function_evaluate(scpp::mixed_t{scpp::string_t{"false"}}, ' . $native . ').native_value()',
	];
}
$fixtures['mixed_logical_effects'] = [
	'function probe(int &$n): bool { $n++; return true; } '
		. 'function skipped(bool $a, int &$n): bool { return $a && probe($n); } '
		. 'function update(mixed $a, mixed &$v): bool { return $a && ($v += 1); } '
		. 'function assign(mixed $a, mixed &$v): bool { return $a || ($v = 3); }',
	'([&]() { scpp::int_t<> n{0LL}; if (function_skipped(scpp::bool_t{false}, n).native_value() || n.native_value() != 0) return false; '
		. 'if (!function_skipped(scpp::bool_t{true}, n).native_value() || n.native_value() != 1) return false; '
		. 'scpp::mixed_t v{scpp::int_t<>{1LL}}, yes{true}, no{false}; '
		. 'function_update(no, v); if (v.int_value().native_value() != 1) return false; '
		. 'function_update(yes, v); if (v.int_value().native_value() != 2) return false; '
		. 'function_assign(yes, v); if (v.int_value().native_value() != 2) return false; '
		. 'function_assign(no, v); return v.int_value().native_value() == 3; }())',
];
return $fixtures;
