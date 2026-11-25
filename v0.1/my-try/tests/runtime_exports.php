<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';
require_once __DIR__ . '/cpp_probe.php';

function export_project(string $text, string $library = ''): Compiler
{
	Compiler_Lifecycle::reset();
	$module = new module('/exports', '/exports', '/exports');
	foreach (['main.phs' => $text, 'library.phs' => $library] as $path => $content) {
		$file = new file(); $file->path = $path; $file->content = $content;
		Source_Registry::add($module, $file);
	}
	Model::$modules->add($module->name, $module);
	$compiler = new Compiler(new cpp_lowering_options(false));
	$compiler->select_entry('main', 'main.phs');
	$compiler->exec_cpp();
	return $compiler;
}

$source = <<<'PHS'
namespace App;
use vector as ListValue;
use function strlen as length;
use const STR_PAD_LEFT as LEFT;
$items ListValue<int> = [1, 2];
$n = length("hello");
$count = \count($items);
if ($n !== 5 || $count !== 2 || LEFT !== 0) { return 1; }
return 0;
PHS;
$compiler = export_project($source);
file_put_contents($argv[1] . '/runtime_exports.cpp', CPP_Probe::text(Model::$cpp_files));

// Adding/removing a source shadow must invalidate the observed root miss too.
$consumer = 'namespace App; use function strlen as length; return length("abc");';
$compiler = export_project($consumer);
$before = CPP_Probe::text(Model::$cpp_files);
$library = null;
foreach (Model::sources() as $record) { if ($record->file->path === 'library.phs') { $library = $record; } }
if ($library === null) { throw new \LogicException('Missing fixture library'); }
foreach (['function strlen(string $value): int { return 7; }', ''] as $replacement) {
	$library->file->content = $replacement;
	$library->file->changes = SYNC_CHANGED;
	$library->changes = change_state::changed;
	$compiler->tokenize(); $compiler->parse(); $compiler->prepare(true); $compiler->cpp();
	$after = CPP_Probe::text(Model::$cpp_files);
	if (($replacement === '') !== ($before === $after)) { throw new \LogicException('Runtime export shadow invalidation failed'); }
}

foreach ([
	'namespace App; use function STR_PAD_LEFT as wrong;',
	'namespace App; use const strlen as wrong;',
	'namespace App; use Missing\vector as wrong;',
	'namespace App; $v Missing\vector<int>;',
	'namespace App; use vector as Alias; namespace Other; $v Alias<int>;',
	'namespace App; use vector as Alias; $v \App\Alias<int>;',
] as $invalid) {
	$failed = false;
	try { export_project($invalid); } catch (\RuntimeException $error) { $failed = true; }
	if (!$failed) { throw new \LogicException('Invalid runtime import accepted: ' . $invalid); }
}
echo "Runtime export imports passed\n";
