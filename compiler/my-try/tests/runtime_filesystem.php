<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

function filesystem_check(bool $condition, string $message): void
{
	if (!$condition) { throw new \LogicException($message); }
}

function filesystem_compile(string $source): void
{
	Compiler_Lifecycle::reset();
	$module = new module('/filesystem', '/filesystem', '/filesystem');
	$file = new file(); $file->path = 'main.phs'; $file->content = $source;
	Source_Registry::add($module, $file);
	Model::$modules->add($module->name, $module);
	(new Compiler(new cpp_lowering_options(false)))->exec_cpp();
}

$cases = [
	'roundtrip' => <<<'PHS'
$problem error; $bytes int = -1; $text string = "";
$path = $root . "/data";
if (take($bytes, $problem) = fs_put($path, "payload")) {} else { return 1; }
if ($bytes !== 7) { return 2; }
if ($problem) { return 29; }
if (!fs_exists($path) || !fs_is_file($path) || fs_is_dir($path) || fs_is_link($path)) { return 3; }
if (take($bytes, $problem) = fs_put($path, "p")) {} else { return 4; }
if ($bytes !== 1) { return 5; }
if (take($text, $problem) = fs_get($path)) {} else { return 6; }
echo $text, "|";
if (take($bytes, $problem) = fs_size($path)) {} else { return 8; }
if ($bytes !== 1) { return 9; }
if (take($bytes, $problem) = fs_mtime($path)) {} else { return 10; }
if ($bytes <= 0) { return 11; }
if (!fs_copy($path, $root . "/copy")) { return 12; }
if (!fs_rename($root . "/copy", $root . "/moved")) { return 13; }
if (fs_exists($root . "/copy")) { return 14; }
if (fs_rmdir($path)) { return 15; }
if (!fs_is_file($path)) { return 16; }
if (!fs_remove($root . "/moved")) { return 17; }
if (!fs_touch($root . "/empty")) { return 18; }
if (take($text, $problem) = fs_get($root . "/empty")) {} else { return 19; }
echo $text, "|";
if (take($bytes, $problem) = fs_put($path, "")) {} else { return 21; }
if ($bytes !== 0) { return 22; }
if (!fs_mkdir($root . "/subdir")) { return 23; }
if (fs_mkdir($root . "/subdir")) { return 24; }
if (!fs_touch($root . "/subdir/child")) { return 25; }
if (fs_rmdir($root . "/subdir")) { return 26; }
if (!fs_remove($root . "/subdir/child")) { return 27; }
if (!fs_rmdir($root . "/subdir")) { return 28; }
return 0;
PHS,
	'paths' => <<<'PHS'
$problem error; $text string = "";
$names vector<string> = [];
if (take($names, $problem) = fs_scan($root . "/scan")) {} else { return 1; }
foreach ($names as $name) { echo $name, "|"; }
if (take($names, $problem) = fs_scan($root . "/empty")) {} else { return 7; }
if ($problem) { return 8; }
echo count($names), "|";
if (take($text, $problem) = fs_realpath($root . "/scan/../scan/A")) {} else { return 2; }
echo $text, "|";
echo fs_dirname("a/b/c"), "|", fs_basename("a/b/c"), "|", fs_dirname("bare");
if (!fs_is_link($root . "/broken") || fs_exists($root . "/broken")) { return 4; }
if (fs_rmdir($root . "/link")) { return 5; }
if (!fs_is_link($root . "/link") || !fs_is_dir($root . "/scan")) { return 6; }
return 0;
PHS,
	'failures' => <<<'PHS'
$missing = $root . "/missing"; $problem error; $text string = ""; $bytes int = 0;
$names vector<string> = ["preserved"];
if (take($names, $problem) = fs_scan($missing)) { return 11; }
if (!$problem) { return 12; }
echo $names[0];
if (take($text, $problem) = fs_get($missing)) { return 1; }
if (!$problem) { return 2; }
if (take($bytes, $problem) = fs_put($missing . "/child", "x")) { return 3; }
if (take($bytes, $problem) = fs_size($missing)) { return 5; }
if (take($bytes, $problem) = fs_mtime($missing)) { return 6; }
if (take($text, $problem) = fs_realpath($missing)) { return 7; }
if (fs_touch($missing . "/child") || fs_mkdir($missing . "/child")) { return 8; }
if (fs_copy($missing, $root . "/copy") || fs_rename($missing, $root . "/moved")) { return 9; }
if (fs_rmdir($missing) || fs_remove($missing)) { return 10; }
return 0;
PHS,
	'snapshot' => <<<'PHS'
$problem error; $text string = "";
if (take($text, $problem) = fs_read_snapshot($root . "/data", 1700000000, 7)) {} else { return 1; }
echo $text, "|";
if ($problem) { return 8; }
if (take($text, $problem) = fs_read_snapshot($root . "/data", 1700000001, 7)) { return 3; }
if (take($text, $problem) = fs_read_snapshot($root . "/data", 1700000000, 6)) { return 4; }
if (take($text, $problem) = fs_read_snapshot($root . "/link", 1700000000, 7)) { return 5; }
if (take($text, $problem) = fs_read_snapshot($root . "/empty", 1700000000, 0)) {} else { return 6; }
echo $text, "|";
if ($problem) { return 9; }
return 0;
PHS,
	'shadow' => 'function fs_exists(int $value): int { return $value; } return fs_exists(0);',
];
foreach ($cases as $name => $source) {
	$prefix = '$root string = ' . json_encode($argv[1] . '/data-' . $name, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES) . ';';
	filesystem_compile($prefix . $source);
	file_put_contents($argv[1] . '/' . $name . '.cpp', CPP_Probe::text(Model::$cpp_files));
}

foreach (['fs_get(1);', 'fs_put("x");', 'fs_scan("x", 1);', 'fs_read_snapshot("x", 1);',
	'fs_copy("x", 1);', 'fs_rmdir();', 'file_get_contents("x");', 'scandir("x");',
	'scpp_output_root("x");', 'fs_lock_release(1);'] as $source) {
	$failed = false;
	try { filesystem_compile($source); }
	catch (\RuntimeException $error) { $failed = true; }
	filesystem_check($failed && Model::$cpp_files->is_empty(), 'Invalid/unregistered filesystem call emitted: ' . $source);
}
$root = dirname(__DIR__, 3) . '/runtime/metadata/modules';
$selected = Runtime_Catalog_Loader::load($root, ['filesystem', 'core', 'filesystem']);
filesystem_check(count($selected->modules) === 2, 'Filesystem dependency deduplication failed');
$types = Type_Definition_Registration::install(new Type_Registry());
$core = Runtime_Catalog_Loader::load($root, ['core']);
Runtime_Type_Registration::install($core, $types);
$catalog = Runtime_Catalog_Registration::resolve($core, $types);
$failed = false;
try { $catalog->function_definition('filesystem.fs_get'); }
catch (\Exception $error) { $failed = true; }
filesystem_check($failed, 'Unselected filesystem module leaked');
Compiler_Lifecycle::reset();
$original = Model::$runtime_callables->function_definition('filesystem.fs_get');
$metadata = Model::require_runtime_metadata();
Compiler_Lifecycle::reset_syntax();
filesystem_check($original !== Model::$runtime_callables->function_definition('filesystem.fs_get'), 'Stale filesystem identity');
filesystem_check($metadata === Model::require_runtime_metadata(), 'Metadata was not retained across reset');
echo "Runtime filesystem passed\n";
