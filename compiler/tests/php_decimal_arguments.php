<?php

declare(strict_types=1);
require_once __DIR__ . '/../boot.php';
use scpp\compiler\{Cpp_Emitter, Source_Frontend, entry_target, flavor, source_file, diagnostic, diagnostic_group};

function compile_decimal(string $source): string
{
	$entry = (new Source_Frontend())->prepare(new source_file('decimal', $source, flavor::php), new entry_target(31));
	return (new Cpp_Emitter())->emit($entry);
}

$bad = [
	'$i int = "12x";' => 'Invalid decimal integer text',
	'$i = (int)"9223372036854775808";' => 'outside int64 range',
	'$i = (int)"-9223372036854775809";' => 'outside int64 range',
	'$i = 0; $i = "123";' => 'conversion',
	'function target(int $n): void {} target("123");' => 'conversion',
	'function target(): int { return "123"; }' => 'conversion',
	'number_format("123");' => 'Unsupported first argument type',
	'number_format(true);' => 'Unsupported first argument type',
];
foreach ($bad as $source => $message) {
	try {
		compile_decimal($source . 'return 8;');
	} catch (diagnostic|diagnostic_group $error) {
		if (!str_contains($error->getMessage(), $message)) throw $error;
		continue;
	}
	throw new RuntimeException('Unexpected decimal admission: ' . $source);
}
$folded = compile_decimal('const string INPUT = "+007"; $n int = "-9223372036854775808"; echo $n, (int)INPUT; return 8;');
if (str_contains($folded, 'parse_decimal_int64') || !str_contains($folded, '7LL')) throw new RuntimeException('Decimal literal was not folded');

$cpp = compile_decimal(<<<'SCPP'
function read_text(string $text, int &$count): string { $count++; return $text; }
function parse_text(string $text, int &$slot, int &$count): void { $slot = (int)read_text($text, $count); }
function declare_int(string $text): int { $n int = $text; return $n; }
function fmt_miss(result_or_false<int> $v): string { return number_format($v); }
function fmt(result<int> $v): string { return number_format($v); }
function fmt_mixed(mixed $v): string { return number_format($v, 0, ".", ""); }
function fmt_dynamic(dynamic $v): string { return number_format($v, 2); }
$n int = "1234"; echo $n; $n = (int)"654"; echo $n;
$r result<int> = 9007199254740993; echo fmt($r);
$m mixed = 9007199254740993; echo fmt_mixed($m);
$d dynamic = 1.25; echo fmt_dynamic($d); return 8;
SCPP);
if (!str_contains($cpp, '(var_v_0).value()') || !str_contains($cpp, 'number_format_mixed') || !str_contains($cpp, 'number_format_dynamic')) {
	throw new RuntimeException('Missing prepared numeric selector');
}
$cpp = "#include <stdexcept>\n#include <string_view>\nint audit();\n" . str_replace('::App::scpp_entry_body,', '::audit,', $cpp) . <<<'CPP'

int audit() {
    scpp::int64 n{17}, count{}, expected{};
    for (auto text : {"", "+", "+-1", "--1", " 42", "42 ", "1.0", "1e3", "12items"}) {
        try { App::parse_text_(scpp::string{text}, n, count); return 90; }
        catch (const std::invalid_argument&) { if (n != 17 || count != ++expected) return 91; }
    }
    try { App::parse_text_(scpp::string{"9223372036854775808"}, n, count); return 92; }
    catch (const std::out_of_range&) { if (n != 17 || count != ++expected) return 93; }
    if (App::declare_int_(scpp::string{"+007"}) != 7 ||
        App::declare_int_(scpp::string{"-9223372036854775808"}) != (-9223372036854775807LL - 1)) return 94;
    if (App::declare_int_(scpp::string{"9223372036854775807"}) != 9223372036854775807LL) return 97;
    try { App::parse_text_(scpp::string{"42\0", 3}, n, count); return 98; }
    catch (const std::invalid_argument&) { if (n != 17 || count != ++expected) return 99; }
    try { App::fmt_miss_(scpp::result_or_false_int64::from_false()); return 100; }
    catch (const std::runtime_error& e) { if (std::string_view{e.what()} != "carrier_no_value") return 101; }
    try { App::fmt_mixed_(scpp::mixed::from_string(scpp::string{"123"})); return 95; }
    catch (const std::runtime_error& e) { if (std::string_view{e.what()} != "boxed_scalar_kind") return 96; }
    return App::scpp_entry_body();
}
CPP;
file_put_contents($argv[1], json_encode(['php' => ['cpp' => $cpp, 'stdout_hex' => bin2hex('12346549,007,199,254,740,99390071992547409931.25')]], JSON_THROW_ON_ERROR));
