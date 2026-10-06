<?php
namespace scpp\compiler;
require_once dirname(__DIR__) . '/boot.php';

function project_check(bool $condition, string $message): void
{
	if (!$condition) { throw new \LogicException($message); }
}

function project_input(array $sources, ?string $entry = 'main.phs'): Compiler
{
	Compiler_Lifecycle::reset();
	$module = new module('/project', '/project', '/project');
	foreach ($sources as $path => $text) {
		$file = new file();
		$file->path = $path;
		$file->content = $text;
		Source_Registry::add($module, $file);
	}
	Model::$modules->add($module->name, $module);
	$compiler = new Compiler();
	if ($entry !== null) { $compiler->select_entry('main', $entry); }
	return $compiler;
}

function project_snapshot(): array
{
	$result = [];
	foreach (Model::$cpp_files as $artifact) {
		project_check(!isset($result[$artifact->identity]), 'Duplicate artifact identity');
		$result[$artifact->identity] = [$artifact->file_name, $artifact->text];
		preg_match_all('/^#include "([^"]+)"/m', $artifact->text, $matches);
		project_check(count($matches[1]) === count(array_unique($matches[1])), 'Duplicate includes within an artifact');
	}
	return $result;
}

$cases = [
	'calls' => [[
		'main.phs' => 'return caller();',
		'caller.phs' => 'function caller(): int { return target(); }',
		'lib.phs' => 'function target(): int { return 7; }',
	], 7],
	'defaults' => [[
		'main.phs' => 'return add();',
		'lib.phs' => 'function add(int $x = 3, int $y = 4): int { return $x + $y; }',
	], 7],
	'mutual' => [[
		'main.phs' => 'return left(4);',
		'left.phs' => 'function left(int $n): int { if ($n === 0) { return 7; } return right($n - 1); }',
		'right.phs' => 'function right(int $n): int { if ($n === 0) { return 8; } return left($n - 1); }',
	], 7],
	'reference' => [[
		'main.phs' => '$x int = 3; $a int =& pass($x); $a = 9; return $x;',
		'lib.phs' => 'function &pass(int &$x): int { return $x; }',
	], 9],
	'record' => [[
		'main.phs' => '$box Box; $box->value = 11; return read($box);',
		'type.phs' => 'struct Box { int $value; }',
		'lib.phs' => 'function read(const Box &$box): int { return $box->value; }',
	], 11],
	'container' => [[
		'main.phs' => '$v vector<int> = [7]; return first($v);',
		'lib.phs' => 'function first(vector<int> $v): int { return $v[0]; }',
	], 7],
	'ownership' => [[
		'main.phs' => '$p unique<Box> = new Box(); consume(move($p)); return 7;',
		'type.phs' => 'struct Box { int $value; }',
		'lib.phs' => 'function consume(unique<Box> $p): void { return; }',
	], 7],
];
$manifest = [];
foreach ($cases as $name => [$sources, $exit]) {
	$compiler = project_input($sources);
	$compiler->exec_cpp();
	project_snapshot();
	project_check(count(Model::$cpp_files) > 1, 'Multi-source program was flattened');
	if (isset($argv[1])) {
		$path = $argv[1] . '/' . $name . '.json';
		file_put_contents($path, CPP_Output::encode(Model::$cpp_files));
		$manifest[] = ['path' => $path, 'exit_code' => $exit];
	}
}

// Body edits preserve signatures, other implementation groups and entry bytes.
$sources = $cases['calls'][0];
$compiler = project_input($sources);
$compiler->exec_cpp();
$original = project_snapshot();
$compiler->exec_cpp();
project_check(project_snapshot() === $original, 'No-op generation changed artifacts');
$lib = Model::$modules['/project']->sources['lib.phs'];
$lib->file->content = 'function target(): int { return 9; }';
$compiler->update_cpp(['/project/lib.phs']);
$changed = project_snapshot();
$unit_id = 'implementation:' . CPP_Artifacts::unit_path($lib->parsed->collection);
foreach ($original as $identity => $bytes) {
	project_check(($changed[$identity] !== $bytes) === ($identity === $unit_id), 'Body edit changed wrong artifact: ' . $identity);
}
$sources['lib.phs'] = $lib->file->content;
$clean = project_input($sources);
$clean->exec_cpp();
project_check(project_snapshot() === $changed, 'Clean/incremental output mismatch');
$lib = Model::$modules['/project']->sources['lib.phs'];
$lib->file->content .= ' function unused(): int { return 1; }';
$clean->update_cpp(['/project/lib.phs']);
$added = project_snapshot();
foreach ($changed as $identity => $bytes) {
	if ($identity !== $unit_id) { project_check($added[$identity] === $bytes, 'Unrelated insertion changed existing artifact'); }
}
$lib->file->content = 'function target(): uint8 { return 9; }';
$clean->update_cpp(['/project/lib.phs']);
$signature_change = project_snapshot();
project_check(!isset($signature_change['callable:unused']), 'Deleted declaration retained an artifact');
project_check($signature_change['callable:target'] !== $changed['callable:target'], 'Signature change retained stale header');
foreach (Model::$prepared_files as $file) {
	project_check(count($file->source->preparation_changes) === 0, 'Successful publication did not acknowledge all sources');
}
$lib->file->content = '';
$failed = false;
try { $clean->update_cpp(['/project/lib.phs']); }
catch (\RuntimeException $error) { $failed = true; }
project_check($failed && Model::$cpp_files->is_empty(), 'Deleted called declaration published stale output');
$lib->file->content = $sources['lib.phs'];
$clean->update_cpp(['/project/lib.phs']);
project_check(project_snapshot() === $changed, 'Repair failed to recover original artifact set');

// A failed backend invocation must not acknowledge any member of the prepared batch.
$caller_record = Model::$modules['/project']->sources['caller.phs'];
$lib->file->content = 'function target(): int { return 10; }';
$caller_record->file->content = 'function caller(): int { $n = target(); return $n + 1; }';
$clean->sync(['/project/lib.phs', '/project/caller.phs']);
$clean->prepare(true);
$lib_handoff = $lib->parsed->collection->preparation_changes;
$caller_handoff = $caller_record->parsed->collection->preparation_changes;
$caller_work = Model::$global_scope->functions_named('caller')[0]->syntax()->body->work();
$caller_work->failed = true;
$failed = false;
try { $clean->cpp(); }
catch (\RuntimeException $error) { $failed = true; }
project_check($failed && Model::$cpp_files->is_empty()
	&& count($lib_handoff) > 0 && count($caller_handoff) > 0
	&& $lib->parsed->collection->preparation_changes === $lib_handoff
	&& $caller_record->parsed->collection->preparation_changes === $caller_handoff,
	'Failed batch publication consumed source handoffs');
$caller_work->failed = false;
$clean->cpp();
foreach (Model::$prepared_files as $file) {
	project_check(count($file->source->preparation_changes) === 0, 'Retry did not acknowledge all sources');
}

foreach ([
	[$cases['calls'][0], null],
	[$cases['calls'][0], 'missing.phs'],
	[['main.phs' => 'return 0;', 'lib.phs' => 'return 1;'], 'main.phs'],
] as [$sources, $entry]) {
	$compiler = project_input($sources, $entry);
	$failed = false;
	try { $compiler->exec_cpp(); }
	catch (\RuntimeException $error) { $failed = true; }
	project_check($failed && Model::$prepared_files->is_empty() && Model::$cpp_files->is_empty(), 'Invalid program selection escaped preparation');
}

// Explicit logical names distinguish equal relative paths across modules.
$compiler = project_input(['main.phs' => 'return answer();'], null);
$app = Model::$modules['/project'];
$app->logical_name = 'app';
$library = new module('/library', '/library', '/library');
$library->logical_name = 'tools';
$file = new file();
$file->path = 'main.phs';
$file->content = 'function answer(): int { return 13; }';
Source_Registry::add($library, $file);
Model::$modules->add($library->name, $library);
$compiler->select_entry('app', 'main.phs');
$compiler->exec_cpp();
$named = project_snapshot();
if (isset($argv[1])) {
	$path = $argv[1] . '/modules.json';
	file_put_contents($path, CPP_Output::encode(Model::$cpp_files));
	$manifest[] = ['path' => $path, 'exit_code' => 13];
}
$reordered = new Keyed_Storage();
$reordered->add($library->name, $library);
$reordered->add($app->name, $app);
Model::$modules = $reordered;
$compiler->exec_cpp();
$reordered_snapshot = project_snapshot();
ksort($named);
ksort($reordered_snapshot);
project_check($named === $reordered_snapshot, 'Module order changed artifact names/bytes');
$library->logical_name = '';
$failed = false;
try { $compiler->exec_cpp(); }
catch (\RuntimeException $error) { $failed = str_contains($error->getMessage(), 'explicit logical names'); }
project_check($failed && Model::$cpp_files->is_empty(), 'Unnamed multi-module program was accepted');

if (isset($argv[1])) { file_put_contents($argv[1] . '/executions.json', json_encode($manifest)); }
echo "Project C++: entry admission, partitioning, exact body isolation, insertion/deletion, signature changes and recovery passed\n";
