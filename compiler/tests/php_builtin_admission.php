<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
use scpp\compiler\{Cpp_Emitter, Source_Frontend, entry_target, flavor, source_file, diagnostic, diagnostic_group};

$compile = static function (string $body): string {
	$program = (new Source_Frontend())->prepare(new source_file('builtins', $body, flavor::php), new entry_target(31));
	return (new Cpp_Emitter())->emit($program);
};
$source = <<<'SCPP'
class LengthCheck { public function measure(string $s):int { return strlen($s); } }
function length(string $s): int { return strlen($s); }
function first(mixed &$s): string { echo "F"; $s = "changed"; return "Y"; }
function fail_early(?string $s, mixed &$peer, int &$n): void { str_replace($s, first($peer), "X", $n); }
function fail_box(mixed $s, int &$n): void { str_replace("x", $s, "x", $n); }
function late(?string &$slot): string { echo "L"; $slot = "z"; return "!"; }
$boxed mixed = "a\0b";
$s result<string> = "é";
echo strlen($boxed), strlen($s), length("");
$key ?string = "x";
$n int = 99;
echo str_replace($key, late($key), "xx", $n), $n;
$replace dynamic = "+";
$subject result_or_false<string> = "zz";
echo str_replace($key, $replace, $subject);
$flag mixed = false;
echo (int)is_bool($flag);
$check=new LengthCheck();
$text mixed = " ab ";
echo length($text), $check->measure($text), trim($text);
$v vector<int> = [1,2];
$index result<int> = 1;
$v->remove($index);
echo count($v);
return 8;
SCPP;
$cpp = $compile($source);
foreach (['scpp::str::byte_length(var_s_0)', 'scpp::boxed_mixed_typed_destination_string(', '::scpp::is_bool(',
	'scpp::str::byte_length((var_s_1).value())', 'var_n_3)', 'method_receiver.remove((var_index_10).value())'] as $fragment) {
	if (!str_contains($cpp, $fragment)) throw new RuntimeException('Missing builtin lowering: ' . $fragment);
}
if (substr_count($cpp, 'scpp::boxed_mixed_typed_destination_string(var_text_8)') !== 3) {
	throw new RuntimeException('Expected one mixed string extraction each for a source function, method and builtin');
}
$cpp = "int check_builtins();\n" . str_replace('::App::scpp_entry_body,', '::check_builtins,', $cpp) . <<<'CPP'

#include <stdexcept>
#include <string_view>
int check_builtins() {
    scpp::int64 n{77};
    auto peer = scpp::mixed::from_integer(n);
    scpp::nullable_string empty{};
    try { App::fail_early_(empty, peer, n); return 81; }
    catch (const std::runtime_error& e) { if (std::string_view{e.what()} != "carrier_no_value") return 82; }
    try { App::fail_box_(peer, n); return 83; }
    catch (const std::runtime_error& e) { if (std::string_view{e.what()} != "boxed_scalar_kind") return 84; }
    if (n != 77) return 85;
    return App::scpp_entry_body();
}
CPP;
foreach ([
	'strlen(7)' => 'strlen argument 1 (string) requires string, received int64. Use an explicit (string) cast',
	'str_replace("x", 7, "x")' => 'str_replace argument 2 (replace) requires string, received int64',
	'$n ?int = 0; str_replace("x","y","x",$n)' => 'str_replace argument 4 (count) requires writable int64 storage, received nullable<int64>',
	'str_replace("x","y","x",1)' => 'str_replace argument 4 (count)',
	'is_bool(false)' => 'is_bool argument 1 (value) requires exact mixed, received bool_t',
	'$v dynamic = false; is_bool($v)' => 'requires exact mixed, received dynamic',
	'$v result<bool> = false; is_bool($v)' => 'requires exact mixed, received result<bool_t>',
] as $expression => $expected) {
	try {
		$compile($expression . '; return 8;');
	} catch (diagnostic|diagnostic_group $e) {
		if (!str_contains($e->getMessage(), $expected)) throw $e;
		continue;
	}
	throw new RuntimeException('Unexpected builtin admission: ' . $expression);
}
file_put_contents($argv[1], json_encode(['php' => ['cpp' => $cpp, 'stdout_hex' => bin2hex('320L!!2++144ab1')]], JSON_THROW_ON_ERROR));
