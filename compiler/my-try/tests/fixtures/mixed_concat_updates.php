<?php

/* Text replacement changes mixed kind; snapshots do not alias the updated place. */
$fixtures = [];
foreach (['null' => ['scpp::mixed_t{scpp::null}', ''],
	'bool' => ['scpp::mixed_t{true}', '1'],
	'int' => ['scpp::mixed_t{scpp::int_t<>{42LL}}', '42'],
	'float' => ['scpp::mixed_t{scpp::float_t{1.5}}', '1.5'],
	'string' => ['scpp::mixed_t{scpp::string_t{"hello"}}', 'hello']]
	as $name => [$value, $expected])
{
	$fixtures['mixed_concat_update_' . $name] = [
		'function update(mixed &$v, mixed $r): mixed { return $v .= $r; } '
			. 'function text(string &$v, mixed $r): string { return $v .= $r; } '
			. 'function twice(mixed &$v): mixed { return $v .= $v; } '
			. 'function copy(mixed $v): mixed { return $v .= "!"; }',
		'([&]() { auto v = ' . $value . '; auto original = v; scpp::mixed_t suffix{scpp::string_t{"!"}}; '
			. 'if (function_copy(v).get_string().native_value() != "' . $expected . '!" || v.kind() != original.kind()) return false; '
			. 'auto result = function_update(v, suffix); '
			. 'if (v.get_string().native_value() != "' . $expected . '!" || result.get_string().native_value() != "' . $expected . '!") return false; '
			. 'function_update(v, suffix); if (result.get_string().native_value() != "' . $expected . '!") return false; '
			. 'auto doubled = function_twice(original); if (doubled.get_string().native_value() != "' . $expected . $expected . '") return false; '
			. 'scpp::string_t s{"x"}; auto snapshot = function_text(s, ' . $value . '); function_text(s, suffix); '
			. 'return snapshot.native_value() == "x' . $expected . '" && s.native_value() == "x' . $expected . '!" '
			. '&& suffix.get_string().native_value() == "!"; }())',
	];
}
$fixtures['mixed_concat_update_boundaries'] = [
	'function peers(mixed &$v): mixed { $v .= null; $v .= false; $v .= true; $v .= 2; return $v .= 1.5; } '
		. 'function failure(mixed &$v): void { $v .= (1 / 0); } '
		. 'function outer(mixed &$v): int { return $v .= "!"; } '
		. 'function skip(mixed &$v): bool { return false && ($v .= "!"); } '
		. 'function alias(mixed &$v, mixed &$r): mixed { return $v .= $r; }',
	'([&]() { scpp::mixed_t v{scpp::int_t<>{42LL}}; int caught = 0; '
		. 'function_skip(v); if (v.int_value().native_value() != 42) return false; '
		. 'try { function_failure(v); } catch (const std::runtime_error&) { ++caught; } '
		. 'if (v.int_value().native_value() != 42) return false; '
		. 'if (function_alias(v, v).get_string().native_value() != "4242") return false; '
		. 'try { function_outer(v); } catch (const std::runtime_error&) { ++caught; } '
		. 'if (v.get_string().native_value() != "4242!") return false; '
		. 'v = scpp::mixed_t{scpp::null}; return caught == 2 && function_peers(v).get_string().native_value() == "121.5"; }())',
];
$fixtures['mixed_concat_update_tables'] = [
	'function update(mixed &$v): mixed { return $v .= "!"; }',
	'([&]() { auto table = scpp::shared<scpp::hash_t<scpp::mixed_t>>(); '
		. 'scpp::mixed_t a{table}, b{scpp::dynamic_box(table)}, alias{table}; '
		. 'if (function_update(a).get_string().native_value() != "Array!" '
		. '|| function_update(b).get_string().native_value() != "Object!") return false; '
		. 'return table->empty().native_value() && alias.shared_table_if()->get() == table.get(); }())',
];
return $fixtures;
