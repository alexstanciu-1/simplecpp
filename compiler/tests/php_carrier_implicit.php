<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
use scpp\compiler\{Cpp_Emitter, Source_Frontend, entry_target, flavor, source_file, type_registry, type_mixed, type_dynamic};

$functions = <<<'SCPP'
function init(?int &$v): mixed { $x mixed = $v; return $x; }
function store(result<int> &$v, mixed &$x): void { $x = $v; }
function consume(mixed $v): int { return $v; }
function send(result_or_bool<int> &$v): int { return consume($v); }
function box_return(result_or_false<int> &$v): dynamic { return $v; }
function copy_state(result<int> $v): result<int> { $x result<int> = $v; $x = $v; return $x; }
function once(): ?int { echo "X"; return 7; }
function widen(float $x): float { return $x; }
function read_optional(?int $v, int &$calls): int { $calls++; return $v ?? -1; }
function pass_error(result<int> $r, int &$calls): int { return read_optional($r, $calls); }
function pass_miss(result_or_false<int> $r, int &$calls): int { return read_optional($r, $calls); }
class Reader {
    public function read(?bool $v): int { return $v === null ? 2 : (int)$v; }
    public function status(result_or_bool<bool> $r): int { $expected result_or_bool<bool> = true_sentinel; return (int)($r === $expected); }
}
function pass_flag(result_or_bool<bool> $r): int { $reader = new Reader(); return $reader->read($r); }
function position(): result_or_false<int> { echo "P"; return strpos("abc", "a"); }
SCPP;
$native = '';
$fragments = ['scpp::mixed::from_integer((var_v_0).value())', 'scpp::dynamic::from_integer((var_v_0).value())'];
if (type_registry::specialize('result', type_mixed::instance()) !== null) {
	$functions .= <<<'SCPP'
function from_scalar(?int &$x): result<mixed> { return $x; }
function from_box(mixed &$x): result<mixed> { return $x; }
function extract(result<mixed> &$v, int &$out): void { $out = $v; }
function into_nullable(result<mixed> &$v): ?int { return $v; }
SCPP;
	$native .= <<<'CPP'
    auto boxed = App::from_scalar_(seven);
    if (App::into_nullable_(boxed).value() != 7) return 85;
    scpp::result_mixed bad{info};
    if (!missing([&] { App::extract_(bad, out); }) || out != 23) return 86;
    auto text = scpp::mixed::from_string(scpp::string{"wrong tag"});
    auto wrong = App::from_box_(text);
    try { App::extract_(wrong, out); return 87; }
    catch (const std::runtime_error& e) { if (std::string_view{e.what()} != "boxed_scalar_kind" || out != 23) return 88; }
CPP;
	$fragments[] = 'scpp::boxed_mixed_assignment_int64((var_v_0).value())';
	$fragments[] = 'scpp::boxed_mixed_typed_destination_int64((var_v_0).value())';
}
if (type_registry::specialize('result', type_dynamic::instance()) !== null) {
	$functions .= 'function dynamic_read(result<dynamic> &$v): int { return $v; }';
	$native .= 'scpp::result_dynamic number{scpp::dynamic::from_integer(scpp::int64{9})}; if (App::dynamic_read_(number) != 9) return 89;';
}
$main = <<<'SCPP'
$boxed mixed = once();
$n int = $boxed;
echo $n, (int)widen(once());
$calls = 0;
echo read_optional(position(), $calls), read_optional(null, $calls), read_optional(7, $calls), $calls;
$false_value result_or_bool<bool> = false;
echo pass_flag($false_value);
$reader = new Reader();
$command result_or_bool<bool> = true_sentinel;
echo $reader->status($command);
return 8;
SCPP;
$entry = (new Source_Frontend())->prepare(new source_file('implicit', $functions . $main, flavor::php), new entry_target(31));
try {
	(new Source_Frontend())->prepare(new source_file('bad_method', $functions . '$reader = new Reader(); $reader->status(true); return 8;', flavor::php), new entry_target(31));
	throw new RuntimeException('Method accepted a plain boolean for a result parameter');
} catch (\scpp\compiler\diagnostic $error) {
	if (!str_contains($error->getMessage(), 'Cannot pass bool_t to a parameter of type result_or_bool<bool_t>')) throw $error;
}
$cpp = (new Cpp_Emitter())->emit($entry);
foreach ($fragments as $fragment) {
	if (!str_contains($cpp, $fragment)) throw new RuntimeException('Missing implicit conversion: ' . $fragment);
}
$cpp = "int check_implicit();\n" . str_replace('::App::scpp_entry_body,', '::check_implicit,', $cpp) . <<<'CPP'

#include <stdexcept>
#include <string_view>
int check_implicit() {
    auto missing = [](auto op) {
        try { op(); } catch (const std::runtime_error& e) { return std::string_view{e.what()} == "carrier_no_value"; }
        return false;
    };
    scpp::nullable_int64 seven{scpp::int64{7}}, empty{};
    scpp::int64 out{23};
    auto slot = scpp::mixed::from_integer(out);
    scpp::error_t info{scpp::string{"failure"}, out};
    scpp::result_int64 failed{info};
    auto sentinel = scpp::result_or_bool_int64::from_true();
    auto miss = scpp::result_or_false_int64::from_false();
    if (!missing([&] { App::init_(empty); }) || !missing([&] { App::send_(sentinel); })) return 81;
    if (!missing([&] { App::box_return_(miss); }) || !missing([&] { App::store_(failed, slot); })) return 82;
    if (App::consume_(slot) != out || App::copy_state_(failed).error().get_line() != out) return 83;
    if (App::consume_(App::init_(seven)) != 7) return 84;
    if (!missing([&] { App::pass_error_(failed, out); }) || !missing([&] { App::pass_miss_(miss, out); }) || out != 23) return 90;
    if (!missing([] { App::pass_flag_(scpp::result_or_bool_bool_t::from_true()); })
        || !missing([] { App::pass_flag_(scpp::result_or_bool_bool_t::from_false()); })) return 91;
CPP;
$cpp .= "\n" . $native . "\n    return App::scpp_entry_body();\n}\n";
file_put_contents($argv[1], json_encode(['php' => ['cpp' => $cpp, 'stdout_hex' => bin2hex('X7X7P0-17301')]], JSON_THROW_ON_ERROR));
