<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
use scpp\compiler\{Cpp_Emitter, Source_Frontend, entry_target, flavor, source_file, diagnostic, diagnostic_group, type_registry, type_mixed, type_dynamic};

$compile = static function (string $source): string {
	$entry = (new Source_Frontend())->prepare(new source_file('casts', $source, flavor::php), new entry_target(31));
	return (new Cpp_Emitter())->emit($entry);
};

$functions = '';
$checks = '';
$fragments = ['(once_()).value()', '(var_v_0).value() != 0'];
$invalid_cases = ['function f(?string $v): bool { return (bool)$v; }'];
$carriers = ['nullable<int>' => 'empty', 'result<int>' => 'error', 'result_or_false<int>' => 'miss', 'result_or_bool<int>' => 'sentinel'];
$boxed_setup = '';
if (type_registry::specialize('result', type_mixed::instance()) !== null) {
	$carriers['result<mixed>'] = 'boxed';
	$fragments[] = 'scpp::boxed_mixed_explicit_cast_bool_t((var_v_0).value())';
	$invalid_cases[] = 'function f(result<mixed> $v): dynamic { return (dynamic)$v; }';
	$boxed_setup .= 'scpp::result_mixed boxed{info}; scpp::result_mixed present{scpp::mixed::from_boolean(false)};
		if (App::boxed_bool_(present)) return 83;';
}
if (type_registry::specialize('result', type_dynamic::instance()) !== null) {
	$functions .= 'function read_dynamic(result<dynamic> &$x): int { return (int)$x; }';
	$boxed_setup .= 'scpp::result_dynamic number{scpp::dynamic::from_integer(scpp::int64{7})};
		if (App::read_dynamic_(number) != 7) return 84;';
}
if (type_registry::specialize('result_or_false', type_mixed::instance()) !== null) {
	$carriers['result_or_false<mixed>'] = 'regex_miss';
	$boxed_setup .= 'auto regex_miss = scpp::result_or_false_mixed::from_false();';
}
foreach ($carriers as $carrier => $name) {
	$targets = ['bool', 'int', 'float', 'string', 'mixed'];
	if (!str_contains($carrier, '<mixed>')) array_push($targets, 'byte', 'dynamic');
	foreach ($targets as $target) {
		$fn = $name . '_' . $target;
		$functions .= "function $fn($carrier &\$v): $target { return ($target)\$v; }";
		$checks .= "    if (!throws_missing([&] { App::{$fn}_($name); })) return 81;\n";
		if ($name === 'sentinel') $checks .= "    if (!throws_missing([&] { App::{$fn}_(positive); })) return 82;\n";
	}
}
$cpp = $compile($functions . <<<'SCPP'
function once(): result<int> { echo "X"; return 7; }
echo (int)(bool)once();
$zero ?int = 0;
echo (int)(bool)$zero;
$boolean result_or_bool<bool> = false;
echo (string)$boolean;
$string ?string = "a\0b";
$boxed = (mixed)$string;
echo (string)$boxed;
$d = (dynamic)$zero;
echo (int)$d;
$frac ?float = 9.75;
echo (int)(byte)$frac;
return 8;
SCPP);
foreach ($fragments as $fragment) {
	if (!str_contains($cpp, $fragment)) throw new RuntimeException('Missing cast lowering: ' . $fragment);
}
$cpp = "int check_casts();\n" . str_replace('::App::scpp_entry_body,', '::check_casts,', $cpp) . <<<'CPP'

#include <stdexcept>
#include <string_view>
int check_casts() {
    scpp::nullable_int64 empty{};
    scpp::error_t info{scpp::string{"failure"}, scpp::int64{4}};
    scpp::result_int64 error{info};
    auto miss = scpp::result_or_false_int64::from_false();
    auto sentinel = scpp::result_or_bool_int64::from_false();
    auto positive = scpp::result_or_bool_int64::from_true();
    auto throws_missing = [](auto op) {
        try { op(); }
        catch (const std::runtime_error& e) { return std::string_view{e.what()} == "carrier_no_value"; }
        return false;
    };
CPP;
$cpp .= "\n" . $boxed_setup . "\n" . $checks . "    return App::scpp_entry_body();\n}\n";
foreach ($invalid_cases as $invalid) {
	try {
		$compile($invalid . 'return 8;');
	} catch (diagnostic|diagnostic_group $error) {
		if (!str_contains($error->getMessage(), 'Unsupported')) throw $error;
		continue;
	}
	throw new RuntimeException('Unexpected carrier cast admission');
}
file_put_contents($argv[1], json_encode(['php' => ['cpp' => $cpp, 'stdout_hex' => bin2hex("X10falsea\0b09")]], JSON_THROW_ON_ERROR));
