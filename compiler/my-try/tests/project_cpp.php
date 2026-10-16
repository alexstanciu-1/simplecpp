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
	'lookup_collisions' => [[
		'main.phs' => 'namespace App; use function Lib\strlen as length; $v LibBox; $v->value = length(4); return $v->value;',
		'type.phs' => 'namespace App; struct LibBox { int $value; }',
		'lib.phs' => 'namespace Lib; function strlen(int $value): int { return $value + 3; }',
	], 7],
	'legal_shadowing' => [[
		'main.phs' => 'return check();',
		'lib.phs' => 'function check(): int { $n = 99; $f = fn(int $n): int => $n + 1; $captured = 3; $g = fn(): int => $captured; { $captured int = 12; } $items vector<int> = [2, 3]; $sum = 0; foreach ($items as $n => $item) { $sum += $item; } if ($n !== 99) { return 1; } return $f(4) + $g() + $sum; }',
	], 13],
	'catch_shadowing' => [[
		'main.phs' => 'return check();',
		'lib.phs' => 'function check(): int { $e = 7; try { throw Exception::make("shadow"); } catch (Exception $e) { $m = $e->get_message(); if ($m !== "shadow") { return 2; } } return $e; }',
	], 7],
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
	'record_dependencies' => [[
		'main.phs' => '$box Box; $box->value = 17; $alias Box =& forward($box); $copy = copy_value($alias); $nested Nest; $nested->box = $copy; return nested_read($nested);',
		'type.phs' => 'struct Box { int $value; } struct Nest { Box $box; }',
		'identity.phs' => 'function &identity(Box &$box): Box { return $box; }',
		'forward.phs' => 'function &forward(Box &$box): Box { $alias Box =& $box; return identity($alias); }',
		'copy.phs' => 'function copy_value(Box $box): Box { return $box; }',
		'nested.phs' => 'function nested_read(const Nest &$nest): int { return $nest->box->value; }',
	], 17],
	'inferred_copy' => [[
		'main.phs' => '$box Box; $box->value = 19; return copy_unused($box);',
		'type.phs' => 'struct Box { int $value; }',
		'copy.phs' => 'function copy_unused(const Box &$box): int { $copy = $box; return 19; }',
	], 19],
	'record_replacement' => [[
		'main.phs' => '$box Box; $other Box; $other->value = 29; replace($box, $other); return $box->value;',
		'type.phs' => 'struct Box { int $value; }',
		'lib.phs' => 'function replace(Box &$box, const Box &$other): void { $box = $other; }',
	], 29],
	'template_dependencies' => [[
		'main.phs' => '$box Box; $box->value = 23; $items vector<Box> = [$box]; return first_box($items);',
		'type.phs' => 'struct Box { int $value; }',
		'lib.phs' => 'function first_box(const vector<Box> &$items): int { $copy = $items[0]; return $copy->value; }',
	], 23],
];
$manifest = [];
if (isset($argv[1])) {
	file_put_contents($argv[1] . '/native_projects.json', json_encode($cases, JSON_THROW_ON_ERROR));
}
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

// Header visibility is a backend concern: layout edits do not rewrite callable declarations.
$sources = $cases['record_dependencies'][0];
$compiler = project_input($sources);
$compiler->exec_cpp();
$before_layout = project_snapshot();
$box_header = CPP_Artifacts::type_header('Box');
foreach (['identity', 'forward', 'copy_value', 'nested_read'] as $name) {
	$header_text = $before_layout['callable:' . $name][1];
	project_check(!str_contains($header_text, '#include "types/'), 'Bare record signature included a layout');
	project_check(str_contains($header_text, 'struct record_'), 'Record signature omitted forward declaration');
}
$module = Model::$modules['/project'];
$forward_unit = 'implementation:' . CPP_Artifacts::unit_path($module->sources['forward.phs']->parsed->collection);
$copy_unit = 'implementation:' . CPP_Artifacts::unit_path($module->sources['copy.phs']->parsed->collection);
project_check(!str_contains($before_layout[$forward_unit][1], $box_header), 'Reference-only forwarding required a layout');
project_check(str_contains($before_layout[$copy_unit][1], $box_header), 'By-value definition omitted a layout');
project_check(str_contains($before_layout['type:Nest'][1], $box_header), 'Embedded value omitted its layout dependency');
$type_source = $module->sources['type.phs'];
$type_source->file->content = 'struct Box { int $value; string $label; } struct Nest { Box $box; }';
$compiler->update_cpp(['/project/type.phs']);
$after_layout = project_snapshot();
foreach ($before_layout as $identity => $artifact) {
	project_check(($after_layout[$identity] !== $artifact) === ($identity === 'type:Box'), 'Layout edit rewrote unrelated artifact: ' . $identity);
}
$sources['type.phs'] = $type_source->file->content;
$compiler = project_input($sources);
$compiler->exec_cpp();
project_check(project_snapshot() === $after_layout, 'Layout edit differs from clean generation');
if (isset($argv[1])) {
	$path = $argv[1] . '/record_layout_edit.json';
	file_put_contents($path, CPP_Output::encode(Model::$cpp_files));
	$manifest[] = ['path' => $path, 'exit_code' => 17];
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
