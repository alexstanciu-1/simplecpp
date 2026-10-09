<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

function strings_check(bool $condition, string $message): void
{
	if (!$condition) { throw new \LogicException($message); }
}

/** Use the ordinary startup configuration and full frontend/backend pipeline. */
function strings_compile(string $source): void
{
	Compiler_Lifecycle::reset();
	$module = new module('/strings', '/strings', '/strings');
	$file = new file(); $file->path = 'main.phs'; $file->content = $source;
	Source_Registry::add($module, $file);
	Model::$modules->add($module->name, $module);
	(new Compiler())->exec_cpp();
}

$cases = [
	'split_join' => <<<'PHS'
echo implode("|", explode(",", "a,,b,")), ";", implode("|", explode(",", "a,b,c", 2)), ";",
    implode("|", explode(",", "a,b,c", 0)), ";", implode("|", explode(",", "a,b,c", -1)), ";";
$empty = explode(",", "abc", -1); echo count($empty), ";";
$one = explode(",", ""); echo count($one), ";";
const MIN = -9223372036854775808;
$dropped = explode(",", "a,b", MIN); echo count($dropped), ";";
echo implode("/", explode("::", "a::b::c")), ";", bin2hex(implode("-", explode("\0", "a\0b")));
return 0;
PHS,
	'join_containers' => <<<'PHS'
$vector vector<string> = ["a", "b"];
$names hash<string> = ["z" => "last", "a" => "first"];
$numbers hash<string, int> = [8 => "eight", 2 => "two"];
echo implode("/", $vector), "|", implode("/", $names), "|", implode("/", $numbers), "|",
    implode("/", vector<string>::[]), "|", implode("/", hash<string>::[]);
return 0;
PHS,
	'split_order' => <<<'PHS'
function source(int &$calls, string &$separator): string { $calls++; $separator = ":"; return "a,b"; }
$separator = ","; $calls = 0;
$parts = explode($separator, source($calls, $separator));
echo implode("|", $parts), ";", $separator, ";", $calls;
return 0;
PHS,
	'slice_compare' => <<<'PHS'
echo substr("é中😀", 1), "|", substr("é中😀", 1, 1), "|", substr("é中😀", -1), "|",
    substr("é中😀", 0, -1), "|", substr("abc", 99), "|", substr("abc", -99), ";";
$same = substr_compare("abc", "bc", 1);
$limited = substr_compare("abc", "bc!", 1, 2);
$folded = substr_compare("aBC", "bc", 1, 2, true);
$negative = substr_compare("abc", "b", -2, -1);
$fallback = substr_compare("abc", "a", 99);
if ($same !== 0 || $limited !== 0 || $folded !== 0 || $negative !== 0 || $fallback >= 0) { return 1; }
const MIN = -9223372036854775808;
echo substr("abc", MIN), "|", substr("abc", 0, MIN);
return 0;
PHS,
	'hex_decode' => <<<'PHS'
$text string = "";
if (take($text) = hex2bin("00fF41")) { echo bin2hex($text); } else { return 1; }
if (take($text) = hex2bin("")) { echo "|", strlen($text); } else { return 2; }
if (take($text) = hex2bin("0")) { return 3; }
if (take($text) = hex2bin("0g")) { return 4; }
return 0;
PHS,
	'byte_helpers' => <<<'PHS'
echo string_byte_len("é中😀"), "|", string_byte_at("\xff", 0), "|", string_byte_at("x", -1), "|",
    string_byte_at("x", 1), "|", bin2hex(string_byte_slice("é", 1, 9)), "|",
    string_byte_slice("abc", -1, 1), "|", string_byte_slice("abc", 0, -1);
$equal = string_byte_slice_equals("a\0b", 1, 1, "\0");
$outside = string_byte_slice_equals("abc", 2, 3, "cde");
$negative = string_byte_slice_equals("abc", -1, 1, "a");
if (!$equal || $outside || $negative) { return 1; }
return 0;
PHS,
	'byte_search' => <<<'PHS'
$position int = -1;
if (take($position) = string_byte_find("abc", "a")) { echo $position; } else { return 1; }
if (take($position) = string_byte_find("é中😀", "😀", 0)) { echo "|", $position; } else { return 2; }
if (take($position) = string_byte_find("aba", "a", 1)) { echo "|", $position; } else { return 3; }
if (take($position) = string_byte_find("abc", "", 3)) { echo "|", $position; } else { return 4; }
if (take($position) = string_byte_find("abc", "z")) { return 5; }
return 0;
PHS,
	'unicode_helpers' => <<<'PHS'
echo string_utf8_codepoint_count("é中😀"), "|", string_utf8_codepoint_at("é中😀", 2), "|",
    string_utf8_codepoint_at("x", -1), "|", string_utf8_codepoint_at("x", 1), "|",
    string_utf8_slice_codepoints("é中😀", 1, 1), "|", string_utf8_slice_codepoints("abc", -1, 1), "|",
    string_grapheme_count("éx"), "|", string_grapheme_slice("éx", 0, 1), "|",
    string_grapheme_slice("abc", 9, 1);
return 0;
PHS,
	'pad' => 'echo "[", str_pad("x", 4), "]|", str_pad("x", 6, "ab"), "|", str_pad("x", 4, "ab", STR_PAD_LEFT), "|", str_pad("x", 4, "ab", STR_PAD_RIGHT), "|", str_pad("x", 4, "ab", STR_PAD_BOTH), "|", str_pad("abc", -1), "|", str_pad("abc", 1), "|", str_pad("", 3, "ab"), "|", bin2hex(str_pad("é", 3, "中")); return 0;',
	'format' => 'const NEG = -1234.5; echo number_format(1234), "|", number_format(1234, 2), "|", number_format(1234, 2, ",", "_"), "|", number_format(1234.5), "|", number_format(1234.5, 2), "|", number_format(1234.5, 2, ",", "_"), "|", number_format(NEG, 2), "|", number_format(1234, -2); return 0;',
	'constant_shadow' => 'const STR_PAD_LEFT = 8; if (STR_PAD_LEFT !== 8 || STR_PAD_RIGHT !== 1 || STR_PAD_BOTH !== 2) { return 1; } return 0;',
	'constant_use' => 'const MODE = STR_PAD_BOTH; $mode = MODE; switch ($mode) { case STR_PAD_BOTH: return 0; default: return 1; }',
	'trim_default' => 'echo "[", trim(" \t\n\r\v\0abc \t\n\r\v\0"), "]|[", ltrim("  abc  "), "]|[", rtrim("  abc  "), "]|[", trim(" \t\0"), "]|[", trim(""), "]|[", trim(" x "), "]"; return 0;',
	'trim_masks' => 'echo trim("xyabcxy", "xy"), "|", ltrim("xyabcxy", "xy"), "|", rtrim("xyabcxy", "xy"), "|", trim("abc", ""), "|", trim("a..b..z", "a..z"), "|", trim("xxx", "x"); return 0;',
	'hex_bytes' => 'echo "[", bin2hex(""), "]|", bin2hex("Az"), "|", bin2hex("\0\xff"), "|", bin2hex("é中😀"); return 0;',
	'replace' => 'echo str_replace("aa", "X", "aaaaa"), "|", str_replace("", "X", "abc"), "|", str_replace("z", "X", "abc"), "|", str_replace("a", "", "aba"), "|", str_replace("a", "aa", "a"), "|", bin2hex(str_replace("\0", "X", "a\0b")); return 0;',
	'trim_order' => 'function mask(int &$calls, string &$value): string { $calls++; $value = "changed"; return "x"; } $value = "xxxabcxxx"; $calls = 0; $result = trim($value, mask($calls, $value)); if ($calls !== 1) { return 1; } echo $result, "|", $value; return 0;',
	'case' => 'echo strtolower("AbC-é中😀"), "|", strtoupper("aBc-é中😀"), "|", lcfirst("HELLO"), "|", ucfirst("hello"), "|", lcfirst("ÉX"), "|", ucfirst("éx"); return 0;',
	'empty' => 'echo "[", strtolower(""), strtoupper(""), lcfirst(""), ucfirst(""), "]"; if (!str_contains("", "") || !str_starts_with("", "") || !str_ends_with("", "")) { return 1; } return 0;',
	'predicates' => 'if (!str_contains("abc", "b") || !str_starts_with("abc", "ab") || !str_ends_with("abc", "bc")) { return 1; } if (str_contains("abc", "B") || str_starts_with("abc", "bc") || str_ends_with("abc", "ab")) { return 2; } if (str_starts_with("a", "abc") || str_ends_with("a", "abc") || str_contains("", "a")) { return 3; } if (!str_contains("é中😀", "中") || !str_starts_with("é中😀", "é") || !str_ends_with("é中😀", "😀")) { return 4; } if (!str_contains("abc", "") || !str_starts_with("abc", "") || !str_ends_with("abc", "")) { return 5; } return 0;',
	'order' => 'function first(int &$order, string &$text): string { $order = $order * 10 + 1; return $text; } function second(int &$order, string &$text): string { $order = $order * 10 + 2; $text = "changed"; return "b"; } $order = 0; $text = "abc"; $found = str_contains(first($order, $text), second($order, $text)); if (!$found || $order !== 12) { return 1; } echo $text; return 0;',
	'purity' => '$text = "AbC"; $lower = strtolower($text); $upper = strtoupper($text); echo $text, "|", $lower, "|", $upper; return 0;',
	'shadow' => 'function strtolower(int $value): int { return $value; } return strtolower(0);',
];
foreach ($cases as $name => $source) {
	strings_compile($source);
	file_put_contents($argv[1] . '/' . $name . '.cpp', CPP_Probe::text(Model::$cpp_files));
}
strings_compile('$value dynamic = {a: 1}; $text = ucfirst($value); return 91;');
$cpp = str_replace('int main(int argc, char** argv)', 'int observed_entry(int argc = 0, char** argv = nullptr)', CPP_Probe::text(Model::$cpp_files));
$cpp = '#include <stdexcept>' . "\n" . $cpp
	. "\nint main() { try { observed_entry(); } catch (const std::runtime_error&) { return 0; } return 92; }\n";
file_put_contents($argv[1] . '/boxed_failure.cpp', $cpp);
foreach (['pad_empty' => 'str_pad("abc", 1, "");', 'pad_mode' => 'str_pad("abc", 1, "x", 99);',
	'split_empty' => 'explode("", "abc");', 'byte_search_negative' => 'string_byte_find("abc", "a", -1);',
	'byte_search_outside' => 'string_byte_find("abc", "a", 4);'] as $name => $source) {
	strings_compile($source . ' return 91;');
	$cpp = str_replace('int main(int argc, char** argv)', 'int observed_entry(int argc = 0, char** argv = nullptr)', CPP_Probe::text(Model::$cpp_files));
	$cpp .= "\nint main() { try { observed_entry(); } catch (const scpp::ValueError&) { return 0; } return 92; }\n";
	file_put_contents($argv[1] . '/' . $name . '.cpp', $cpp);
}
foreach (['strtolower();', 'strtoupper("x", "y");', 'lcfirst(vector<int>::[1]);',
	'strpos("abc", "a");', 'strrpos("abc", "a");', 'substr_replace("abc", "x", 1);',
	'string_parts_builder_create();', 'text_builder_create();',
	'explode(",");', 'explode(",", "abc", "2");', 'implode(vector<string>::[]);',
	'implode(",", vector<int>::[1]);', 'implode(",", hash<int>::["a" => 1]);',
	'$parts mixed = ["a"]; implode(",", $parts);', '$parts dynamic = ["a"]; implode(",", $parts);',
	'substr("abc");', 'substr_compare("abc", "a");', 'hex2bin(vector<int>::[]);',
	'string_byte_find("a");', 'string_byte_at("a", vector<int>::[]);',
	'string_utf8_slice_codepoints("x", 0);', 'string_grapheme_count(vector<int>::[]);',
	'str_pad("x");', 'str_pad("x", "4");', 'str_pad("x", 4, " ", 1, 2);',
	'number_format();', 'number_format(1, 2, ".");', 'number_format(true);', 'number_format("123");',
	'$n mixed = 1; number_format($n);', '$n dynamic = 1; number_format($n);',
	'ucfirst("x", "y");', 'str_contains("x");', 'str_starts_with("x", "y", "z");',
	'str_ends_with(vector<int>::[], "x");', 'trim();', 'ltrim("x", "x", "x");',
	'rtrim(vector<int>::[]);', 'trim("x", vector<int>::[]);', 'bin2hex("x", "y");',
	'str_replace("x", "y");', 'str_replace("x", "y", "x", 0);',
	'str_replace(vector<string>::["x"], "y", "x");'] as $source) {
	$failed = false;
	try { strings_compile($source); }
	catch (\RuntimeException $error) { $failed = true; }
	strings_check($failed && Model::$cpp_files->is_empty(), 'Invalid string call published artifacts: ' . $source);
}

// Explicit catalog selection remains meaningful: a core-only installation has no strings.
$root = dirname(__DIR__, 3) . '/runtime/metadata/modules';
$core = Runtime_Catalog_Loader::load($root, ['core']);
$types = Type_Definition_Registration::install(new Type_Registry());
$catalog = Runtime_Catalog_Registration::resolve($core, $types);
strings_check(Runtime_Catalog_Registration::resolve_constants($core, $types)->is_empty(), 'Unselected constants were installed');
$failed = false;
try { $catalog->function_definition('strings.lower'); }
catch (\Exception $error) { $failed = true; }
strings_check($failed, 'Unselected strings module was installed');
$selected = Runtime_Catalog_Loader::load($root, ['strings', 'core', 'strings']);
strings_check(count($selected->modules) === 2, 'Strings dependencies were not deduplicated');
echo "Runtime strings passed\n";
