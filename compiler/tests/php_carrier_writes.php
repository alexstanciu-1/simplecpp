<?php
declare(strict_types=1);

require_once __DIR__ . '/../boot.php';
use scpp\compiler\{Cpp_Emitter, Source_Frontend, entry_target, flavor, source_file, diagnostic, diagnostic_group};

$compile = static function (string $source): string {
	$program = (new Source_Frontend())->prepare(new source_file('writes', $source, flavor::php), new entry_target(31));
	return (new Cpp_Emitter())->emit($program);
};
$source = <<<'SCPP'
function replace(?int &$i): result<int> { $i = 90; return 3; }
function append(result<string> &$s): string { $s = "other"; return "!"; }
function mutate(?int &$i): void { ++$i; }
function fail_write(result<string> &$s, ?string &$rhs): void { $s .= $rhs; }
function bump_error(result<int> &$v): void { $v++; }
function effect(result<string> &$s): ?string { $s = "changed"; return null; }
function rhs_failure(result<string> &$s): void { $s .= effect($s); }
$i ?int = 7;
$i += replace($i);
echo $i + 0, "|";
$old = $i++;
$new = ++$i;
mutate($i);
echo $old + 0, "|", $new + 0, "|", $i + 0, "|";
$i **= 2;
$i >>= 1;
$i &= 15;
echo $i + 0, "|";
$f ?float = 0.0;
$big result<int> = 9007199254740993;
$f += $big;
echo (int)($f == 9007199254740992.0), "|";
$f--;
$plain int = 2;
$plain *= $i;
echo $plain, "|";
$s result<string> = "a\0";
$s .= append($s);
$other result_or_false<string> = "b";
$s .= $other;
echo $s . "!";
return 8;
SCPP;
$cpp = $compile($source);
foreach (['compound_previous = (var_i_0).value();', 'replace_(var_i_0)', '& slot) { auto payload = (slot).value();',
	'compound_previous = (var_s_0).value();', 'static_cast<scpp::float64>((var_big_'] as $fragment) {
	if (!str_contains($cpp, $fragment)) throw new RuntimeException('Missing checked write: ' . $fragment);
}
$cpp = "int check_writes();\n" . str_replace('::App::scpp_entry_body,', '::check_writes,', $cpp) . <<<'CPP'

#include <stdexcept>
#include <string_view>
int check_writes() {
    scpp::result_string failure{scpp::error_t{scpp::string{"old"}, scpp::int64{3}}};
    scpp::nullable_string empty{};
    scpp::result_int64 integer{scpp::error_t{scpp::string{"old"}, scpp::int64{4}}};
    auto requires_payload = [](auto operation) {
        try { operation(); } catch (const std::runtime_error& e) { return std::string_view{e.what()} == "carrier_no_value"; }
        return false;
    };
    if (!requires_payload([&] { App::fail_write_(failure, empty); }) || failure.error().get_line() != 3) return 81;
    if (!requires_payload([&] { App::bump_error_(integer); }) || integer.error().get_line() != 4) return 82;
    if (!requires_payload([&] { App::rhs_failure_(failure); }) || failure.error().get_line() != 3) return 83;
    scpp::result_string present{scpp::string{"initial"}};
    if (!requires_payload([&] { App::rhs_failure_(present); }) || present.value() != scpp::string{"changed"}) return 84;
    return App::scpp_entry_body();
}
CPP;
$fixtures = ['php' => ['cpp' => $cpp, 'stdout_hex' => bin2hex("10|10|12|13|4|1|8|a\0!b!")]];
foreach (['$s ?string = null;', '$s result_or_false<string> = false_sentinel;', '$s result_or_bool<string> = true_sentinel;'] as $i => $decl) {
	$fixtures["missing-$i"] = ['cpp' => $compile('function rhs(): string { echo "unexpected"; return "!"; }' . $decl . '$s .= rhs(); return 8;'),
		'stdout_hex' => '', 'catch_runtime_error' => 'carrier_no_value'];
}
foreach (['$s ?string = null; $s .= 7;', '$n ?int = 7; $n += 1.5;', '$s ?string = "a"; ++$s;'] as $invalid) {
	try {
		$compile($invalid . 'return 8;');
	} catch (diagnostic|diagnostic_group $e) {
		continue;
	}
	throw new RuntimeException('Unexpected carrier write: ' . $invalid);
}
file_put_contents($argv[1], json_encode($fixtures, JSON_THROW_ON_ERROR));
