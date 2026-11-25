<?php

/* Text context differs from required extraction and from explicit string casting. */
$fixtures = [];
foreach (['null' => ['scpp::mixed_t{scpp::null}', ''],
	'false' => ['scpp::mixed_t{false}', ''], 'true' => ['scpp::mixed_t{true}', '1'],
	'int' => ['scpp::mixed_t{scpp::int_t<>{42LL}}', '42'],
	'float' => ['scpp::mixed_t{scpp::float_t{1.5}}', '1.5'],
	'string' => ['scpp::mixed_t{scpp::string_t{"hello"}}', 'hello'],
	'table' => ['scpp::mixed_t{scpp::shared<scpp::hash_t<scpp::mixed_t>>()}', 'Array'],
	'owned_table' => ['scpp::mixed_t{scpp::unique<scpp::hash_t<scpp::mixed_t>>()}', 'Array']]
	as $name => [$value, $expected])
{
	$fixtures['mixed_concat_' . $name] = [
		'function left(mixed $v): string { return "[" . $v; } '
			. 'function right(mixed $v): string { return $v . "]"; } '
			. 'function pair(mixed $v): string { return $v . $v; } '
			. 'function boxed(mixed $v): mixed { return null . $v . null; }',
		'([&]() { auto v = ' . $value . '; '
			. 'return function_left(v).native_value() == "[' . $expected . '" '
			. '&& function_right(v).native_value() == "' . $expected . ']" '
			. '&& function_pair(v).native_value() == "' . $expected . $expected . '" '
			. '&& function_boxed(v).get_string().native_value() == "' . $expected . '"; }())',
	];
}
$fixtures['mixed_concat_scalars'] = [
	'function text(): string { return null . false . true . 42 . 1.5 . "!"; } '
		. 'function peers(mixed $v): string { return 2 . $v . false . $v . 1.5; } '
		. 'function snapshot(mixed &$v): string { $s = "[" . $v . "]"; $v = 9; return $s; }',
	'([&]() { scpp::mixed_t v{scpp::string_t{"x"}}; '
		. 'if (function_text().native_value() != "1421.5!" || function_peers(v).native_value() != "2xx1.5") return false; '
		. 'return function_snapshot(v).native_value() == "[x]" && v.int_value().native_value() == 9; }())',
];
return $fixtures;
