<?php

/* Source calls and independent native checks for the registered JSON boundary. */
$json_fixtures = [];
foreach (['mixed' => 'json_decode_mixed', 'dynamic' => 'json_decode'] as $family => $decode)
{
	$native = $family === 'dynamic' ? 'scpp::dynamic_t<>' : 'scpp::mixed_t';
	$json_fixtures['json_' . $family . '_roots'] = [
		'function parse(string $text): result<' . $family . '> { return ' . $decode . '($text); } '
			. 'function encode(' . $family . ' $value): result<string> { return json_encode($value); } '
			. 'function takevalue(string $text, ' . $family . ' &$out, error &$err): bool { return take($out, $err) = ' . $decode . '($text); }',
		'([&]() { using B = ' . $native . '; '
			. 'for (const char* text : {"null", "false", "true", "123", "1.5", "\"hello\"", "[1,2]", "{\"a\":1}"}) { '
			. 'auto parsed = function_parse(scpp::string_t{text}); if (!parsed.has_value().native_value()) return false; '
			. 'auto encoded = function_encode(parsed.value()); if (!encoded.has_value().native_value() || encoded.value().native_value() != text) return false; } '
			. 'B out{scpp::int_t<>{7LL}}; scpp::error_t err; '
			. 'if (function_takevalue(scpp::string_t{"{"}, out, err).native_value() || !err.has_error().native_value() || out.get_int().native_value() != 7) return false; '
			. 'if (!function_takevalue(scpp::string_t{"null"}, out, err).native_value() || err.has_error().native_value() || !out.is_null().native_value()) return false; '
			. 'return function_takevalue(scpp::string_t{"false"}, out, err).native_value() && !out.get_bool().native_value(); }())',
	];
	$json_fixtures['json_' . $family . '_containers'] = [
		'function run(): int { $root ' . $family . '; $err error; '
			. 'if (take($root, $err) = ' . $decode . '(\'{"child":{"n":1},"items":[2,3]}\')) { '
			. '$copy = $root; $copy["child"]["n"] = 9; $out int = $root["child"]["n"]; return $out; } return 99; } '
			. 'function nested(): result<string> { return json_encode(' . $family . '::{name: "Alex", settings: {enabled: true}}); } '
			. 'function source(int &$calls): ' . $family . ' { $calls++; return {n: 1}; } '
			. 'function once(int &$calls): result<string> { return json_encode(source($calls)); }',
		'([&]() { scpp::int_t<> calls{0LL}; auto encoded = function_once(calls); '
			. 'return function_run().native_value() == ' . ($family === 'dynamic' ? '9' : '1')
			. ' && function_nested().value().native_value() == "{\"name\":\"Alex\",\"settings\":{\"enabled\":true}}" '
			. '&& calls.native_value() == 1 && encoded.value().native_value() == "{\"n\":1}"; }())',
	];
	$json_fixtures['json_' . $family . '_failures'] = [
		'function parse(string $text): result<' . $family . '> { return ' . $decode . '($text); } '
			. 'function encode(' . $family . ' $v, string &$text, error &$err): bool { return take($text, $err) = json_encode($v); } '
			. 'function selected(): ' . $family . ' { return ' . $decode . '("broken") ?? ' . $family . '::{fallback: 7}; }',
		'([&]() { using B = ' . $native . '; for (const char* bad : {"", "[1,]", "1 trailing", "{", "tru"}) '
			. 'if (function_parse(scpp::string_t{bad}).has_value().native_value()) return false; '
			. 'scpp::string_t text{"preserved"}; scpp::error_t err; '
			. 'if (function_encode(B{scpp::float_t{std::numeric_limits<double>::infinity()}}, text, err).native_value() '
			. '|| !err.has_error().native_value() || text.native_value() != "preserved") return false; '
			. 'if (!function_encode(B{}, text, err).native_value() || err.has_error().native_value() || text.native_value() != "null") return false; '
			. 'return function_selected().table_if()->at(scpp::string_t{"fallback"}).get_int().native_value() == 7; }())',
	];
}
$json_fixtures['json_dynamic_cycle'] = [
	'function cyclic(): result<string> { $d dynamic = {}; $d["self"] = $d; $encoded = json_encode($d); unset($d["self"]); return $encoded; } '
		. 'function aliases(): result<string> { $child dynamic = {n: 1}; $d dynamic = {left: $child, right: $child}; return json_encode($d); }',
	'!function_cyclic().has_value().native_value() && function_aliases().value().native_value() == "{\"left\":{\"n\":1},\"right\":{\"n\":1}}"',
];
return $json_fixtures;
