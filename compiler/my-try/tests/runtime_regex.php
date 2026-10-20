<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

function regex_check(bool $condition, string $message): void
{
	if (!$condition) { throw new \LogicException($message); }
}

function regex_compile(string $source): void
{
	Compiler_Lifecycle::reset();
	$module = new module('/regex', '/regex', '/regex');
	$file = new file();
	$file->path = 'main.phs';
	$file->content = $source;
	Source_Registry::add($module, $file);
	Model::$modules->add($module->name, $module);
	(new Compiler())->exec_cpp();
}

$root = dirname(__DIR__, 3) . '/runtime/metadata/modules';
if (($argv[2] ?? '') === '--unselected') {
	$failed = false;
	try { regex_compile('regex_match("/a/", "a");'); }
	catch (\RuntimeException $error) { $failed = str_contains($error->getMessage(), 'regex_match'); }
	regex_check($failed && Model::$cpp_files->is_empty(), 'Regex use without module selection emitted');
	echo "Unselected regex rejected\n";
	exit(0);
}
// Opt-in configuration precedes the first compilation, and depends only on core.
$selected = Runtime_Catalog_Loader::load($root, ['regex', 'core', 'regex']);
regex_check(count($selected->modules) === 2, 'Regex dependency deduplication failed');
Compiler_Lifecycle::configure_runtime($selected);

$cases = [
	'quote' => <<<'PHS'
$jit bool = regex_jit_available();
echo regex_quote("a+b?."), "|", regex_quote("a/b", "/"), "|";
echo regex_quote("plain", "");
return 0;
PHS,
	'match' => <<<'PHS'
$captures vector<string> = regex_match("/(ab+)-(cd+)/i", "xxAbb-cDDyy");
echo count($captures), "|", $captures[0], "|", $captures[1], "|", $captures[2], "|";
$offset vector<string> = regex_match("/ab/i", "ab xx AB", 3);
$negative vector<string> = regex_match("/ab/i", "ab xx AB", -2);
echo $offset[0], "|", $negative[0], "|";
$unicode vector<string> = regex_match("/./u", "😀", 0);
echo $unicode[0];
return 0;
PHS,
	'named' => <<<'PHS'
$named hash<string> = regex_match_named("/(?<word>ab)/i", "AB ab");
$offset hash<string> = regex_match_named("/(?<word>ab)/i", "AB ab", 3);
echo $named["0"], "|", $named["1"], "|", $named["word"], "|", $offset["word"];
return 0;
PHS,
	'rows' => <<<'PHS'
$rows vector<vector<string>> = regex_match_all("/(a)(b)/", "ab ab");
$offset vector<vector<string>> = regex_match_all("/(a)(b)/", "ab ab", 3);
$columns vector<vector<string>> = regex_match_all_pattern_order("/(a)(b)/", "ab ab");
$offset_columns vector<vector<string>> = regex_match_all_pattern_order("/(a)(b)/", "ab ab", 3);
$named vector<hash<string>> = regex_match_all_named("/(?<word>ab)/", "ab ab");
$offset_named vector<hash<string>> = regex_match_all_named("/(?<word>ab)/", "ab ab", 3);
echo count($rows), "|", count($offset), "|", $rows[1][2], "|";
echo count($columns), "|", count($columns[0]), "|", count($offset_columns[0]), "|";
echo count($named), "|", $named[1]["word"], "|", count($offset_named);
return 0;
PHS,
	'states' => <<<'PHS'
$value vector<string> = ["preserved"];
if (take($value) = regex_match("/unterminated", "abc")) { return 1; }
echo $value[0], "|";
if (!take($value) = regex_match("/z/", "abc")) { return 2; }
echo count($value), "|";
$bad = regex_match_all("/unterminated", "abc");
echo isset($bad), "|";
$fallback vector<string> = regex_match("/unterminated", "abc") ?? vector<string>::["fallback"];
echo $fallback[0], "|";
$empty vector<string> = regex_match("/z/", "abc") ?? vector<string>::["wrong"];
echo count($empty);
return 0;
PHS,
	'grep_replace' => <<<'PHS'
$input vector<string> = ["ab", "x", "abb"];
$matches vector<string> = regex_grep("/ab/", $input);
echo count($matches), "|", $matches[1], "|", count($input), "|";
$text string = regex_replace("/(a)(b)/", "$2$1", "ab ab");
echo $text;
return 0;
PHS,
	'limited_filter' => <<<'PHS'
$n=1;
$text string = regex_replace("/a/", "b", "aa", $n);
$rows vector<string> = regex_filter("/a/", "b", vector<string>::["a"]);
echo $text, "|", $rows[0], "|", $n;
return 0;
PHS,
	'split' => <<<'PHS'
$parts vector<string> = regex_split("/,/", "a,b,");
$limited vector<string> = regex_split("/,/", "a,b,c", 2);
$nonempty vector<string> = regex_split("/,/", ",a,,b,", 0, PREG_SPLIT_NO_EMPTY);
$captured vector<string> = regex_split("/(,)/", "a,b", -1, PREG_SPLIT_DELIM_CAPTURE);
echo count($parts), "|", $parts[2], "|", $limited[1], "|", count($nonempty), "|", $captured[1];
return 0;
PHS,
	'order' => <<<'PHS'
function change(string &$pattern, int &$calls): string { $pattern = "/b/"; $calls++; return "a"; }
$pattern = "/a/"; $calls = 0;
$matched vector<string> = regex_match($pattern, change($pattern, $calls));
echo $matched[0], "|", $pattern, "|", $calls;
return 0;
PHS,
	'shadow' => 'function regex_quote(int $value): int { return $value; } return regex_quote(0);',
];
foreach ($cases as $name => $source) {
	regex_compile($source);
	file_put_contents($argv[1] . '/' . $name . '.cpp', CPP_Probe::text(Model::$cpp_files));
}

foreach (['regex_match();', 'regex_match("/a/", 1);', 'regex_grep("/a/", vector<int>::[1]);',
	'regex_replace_callback("/a/", "b", "a");', 'regex_replace_callback_array("a", "a");',
	'preg_match("/a/", "a");', 'if (regex_match("/a/", "a")) { return 1; }',
	'empty(regex_match("/a/", "a"));'] as $source) {
	$failed = false;
	try { regex_compile($source); }
	catch (\RuntimeException $error) { $failed = true; }
	regex_check($failed && Model::$cpp_files->is_empty(), 'Invalid/held regex call emitted: ' . $source);
}

$types = Type_Definition_Registration::install(new Type_Registry());
$core_input = Runtime_Catalog_Loader::load($root, ['core']);
Runtime_Type_Registration::install($core_input, $types);
$core = Runtime_Catalog_Registration::resolve($core_input, $types);
$failed = false;
try { $core->function_definition('regex.match.2'); }
catch (\Exception $error) { $failed = true; }
regex_check($failed, 'Unselected regex module leaked');
Compiler_Lifecycle::reset();
$original = Model::$runtime_callables->function_definition('regex.match.2');
$metadata = Model::$runtime_metadata;
Compiler_Lifecycle::reset_syntax();
regex_check($original !== Model::$runtime_callables->function_definition('regex.match.2'), 'Stale regex identity');
regex_check($metadata === Model::$runtime_metadata, 'Regex metadata was not retained');
echo "Runtime regex passed\n";
