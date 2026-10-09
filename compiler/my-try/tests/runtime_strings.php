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
$cpp = str_replace('int main()', 'int observed_entry()', CPP_Probe::text(Model::$cpp_files));
$cpp = '#include <stdexcept>' . "\n" . $cpp
	. "\nint main() { try { observed_entry(); } catch (const std::runtime_error&) { return 0; } return 92; }\n";
file_put_contents($argv[1] . '/boxed_failure.cpp', $cpp);
foreach (['strtolower();', 'strtoupper("x", "y");', 'lcfirst(vector<int>::[1]);',
	'ucfirst("x", "y");', 'str_contains("x");', 'str_starts_with("x", "y", "z");',
	'str_ends_with(vector<int>::[], "x");'] as $source) {
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
$failed = false;
try { $catalog->function_definition('strings.lower'); }
catch (\Exception $error) { $failed = true; }
strings_check($failed, 'Unselected strings module was installed');
$selected = Runtime_Catalog_Loader::load($root, ['strings', 'core', 'strings']);
strings_check(count($selected->modules) === 2, 'Strings dependencies were not deduplicated');
echo "Runtime strings passed\n";
