<?php

/* Stored-kind write-back and old/new mutation snapshots, not ordinary mixed assignment. */
$fixtures = [];
foreach (['add' => ['+=', 11, 11.0], 'subtract' => ['-=', 7, 7.0],
	'multiply' => ['*=', 18, 18.0], 'divide' => ['/=', 4, 4.5], 'remainder' => ['%=', 1, 0.0]] as $name => [$op, $integer, $floating])
{
	$source = 'function update(mixed &$a, mixed $b): mixed { return $a ' . $op . ' $b; } '
		. 'function native(mixed &$a, int $b): mixed { return $a ' . $op . ' $b; } '
		. 'function store(mixed &$a, mixed $b, mixed &$out): void { $out = ($a ' . $op . ' $b); } ';
	$probe = '([&]() { scpp::mixed_t target{scpp::int_t<>{9LL}}, right{scpp::int_t<>{2LL}}; '
		. 'auto result = function_update(target, right); '
		. 'if (result.int_value().native_value() != ' . $integer . ' || target.int_value().native_value() != ' . $integer . ') return false; '
		. 'target = scpp::mixed_t{scpp::int_t<>{9LL}}; '
		. 'if (result.int_value().native_value() != ' . $integer
		. ' || function_native(target, scpp::int_t<>{2LL}).int_value().native_value() != ' . $integer . ') return false; ';
	if ($op !== '%=')
	{
		$source .= 'function floating(mixed &$a, float $b): mixed { return $a ' . $op . ' $b; } ';
		$probe .= 'target = scpp::mixed_t{scpp::float_t{9.0}}; '
			. 'if (function_update(target, right).float_value().native_value() != ' . $floating . ') return false; '
			. 'target = scpp::mixed_t{scpp::float_t{9.0}}; '
			. 'if (function_floating(target, scpp::float_t{2.0}).float_value().native_value() != ' . $floating . ') return false; ';
	}
	$probe .= 'target = scpp::mixed_t{scpp::int_t<>{9LL}}; scpp::mixed_t out{scpp::string_t{"old"}}; int caught = 0; '
		. 'for (const auto& bad : {scpp::mixed_t{scpp::float_t{2.0}}, scpp::mixed_t{scpp::string_t{"2"}}, '
		. 'scpp::mixed_t{true}, scpp::mixed_t{scpp::null}}) { '
		. 'try { function_store(target, bad, out); } catch (const std::runtime_error&) { ++caught; } '
		. 'if (target.int_value().native_value() != 9 || out.get_string().native_value() != "old") return false; } '
		. 'return caught == 4 && right.int_value().native_value() == 2; }())';
	$fixtures['mixed_update_' . $name] = [$source, $probe];
}
foreach (['preincrement' => ['++$v', 5, 5, 2.5, 2.5], 'postincrement' => ['$v++', 4, 5, 1.5, 2.5],
	'predecrement' => ['--$v', 3, 3, 0.5, 0.5], 'postdecrement' => ['$v--', 4, 3, 1.5, 0.5]] as $name => [$expression, $result, $stored, $float_result, $float_stored])
{
	$fixtures['mixed_update_' . $name] = [
		'function update(mixed &$v): mixed { return ' . $expression . '; }',
		'([&]() { scpp::mixed_t value{scpp::int_t<>{4LL}}; auto result = function_update(value); '
			. 'if (result.int_value().native_value() != ' . $result . ' || value.int_value().native_value() != ' . $stored . ') return false; '
			. 'value = scpp::mixed_t{scpp::float_t{1.5}}; auto floating = function_update(value); '
			. 'if (floating.float_value().native_value() != ' . $float_result . ' || value.float_value().native_value() != ' . $float_stored . ') return false; '
			. 'auto table = scpp::shared<scpp::hash_t<scpp::mixed_t>>(); int caught = 0; '
			. 'for (auto bad : {scpp::mixed_t{true}, scpp::mixed_t{scpp::null}, scpp::mixed_t{scpp::string_t{"2"}}, '
			. 'scpp::mixed_t{scpp::dynamic_box(table)}}) { auto kind = bad.kind(); '
			. 'try { function_update(bad); } catch (const std::runtime_error&) { ++caught; } '
			. 'if (bad.kind() != kind) return false; } '
			. 'return caught == 4 && result.int_value().native_value() == ' . $result . '; }())',
	];
}
$fixtures['mixed_update_boundaries'] = [
	'function twice(mixed &$v): mixed { return $v += $v; } '
		. 'function divide(mixed &$v, mixed $b): void { $v /= $b; } '
		. 'function modulo(mixed &$v, mixed $b): void { $v %= $b; } '
		. 'function typed(mixed &$v): int { return ++$v; } '
		. 'function typedcompound(mixed &$v): int { return $v += 1; } '
		. 'function ordinary(mixed &$v): void { $v = $v + 0.5; }',
	'([&]() { scpp::mixed_t value{scpp::int_t<>{4LL}}; '
		. 'if (function_twice(value).int_value().native_value() != 8 || value.int_value().native_value() != 8) return false; '
		. 'int caught = 0; scpp::mixed_t zero{scpp::int_t<>{0LL}}; '
		. 'try { function_divide(value, zero); } catch (const std::runtime_error&) { ++caught; } '
		. 'try { function_modulo(value, zero); } catch (const std::runtime_error&) { ++caught; } '
		. 'if (value.int_value().native_value() != 8) return false; function_ordinary(value); '
		. 'try { function_typed(value); } catch (const std::runtime_error&) { ++caught; } '
		. 'if (value.float_value().native_value() != 9.5) return false; '
		. 'try { function_typedcompound(value); } catch (const std::runtime_error&) { ++caught; } '
		. 'return caught == 4 && value.float_value().native_value() == 10.5; }())',
];
return $fixtures;
