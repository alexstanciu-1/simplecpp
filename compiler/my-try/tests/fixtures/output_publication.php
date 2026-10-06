<?php
namespace scpp\compiler;
require_once dirname(__DIR__, 2) . '/boot.php';

function publication_check(bool $condition, string $message): void
{
	if (!$condition) { throw new \LogicException($message); }
}
function publication_reject(callable $operation, string $message): void
{
	$failed = false;
	try { $operation(); } catch (\RuntimeException $error) { $failed = true; }
	publication_check($failed, $message);
}
function publication_set(array $files): Storage
{
	$artifacts = new Storage();
	foreach ($files as $path => $text) {
		$kind = str_ends_with($path, '.hpp') ? cpp_artifact_kind::header : cpp_artifact_kind::translation_unit;
		$artifacts->append(new cpp_artifact($path, $kind, $path, $text));
	}
	return $artifacts;
}
function publication_write(string $root, array $files): void
{
	$worker = new Output_Publication($root);
	try { $worker->publish(publication_set($files)); } finally { $worker->close(); }
}
function publication_stats(string $root, array $paths): array
{
	clearstatcache();
	$result = [];
	foreach ($paths as $path) {
		$stat = stat($root . '/' . $path);
		$result[$path] = [$stat['ino'], $stat['mtime'], file_get_contents($root . '/' . $path)];
	}
	return $result;
}

$root = $argv[1];
$first = ['entry.cpp' => 'first', 'decl.hpp' => 'stable', 'old.cpp' => 'obsolete'];
publication_write($root, $first);
Output_Publication::require_ready($root);
$paths = [...array_keys($first), '.scpp-output.manifest'];
foreach ($paths as $path) { touch($root . '/' . $path, 1000000000); }
$before = publication_stats($root, $paths);
publication_write($root, $first);
publication_check(publication_stats($root, $paths) === $before, 'No-op changed artifact/manifest identity or mtime');
file_put_contents($root . '/notes.txt', 'user-owned');
$second = ['entry.cpp' => 'second', 'decl.hpp' => 'stable', 'new.cpp' => 'added'];
publication_write($root, $second);
publication_check(!file_exists($root . '/old.cpp') && file_get_contents($root . '/new.cpp') === 'added', 'Retirement/addition failed');
publication_check(publication_stats($root, ['decl.hpp'])['decl.hpp'] === $before['decl.hpp'], 'Unchanged header replaced');
publication_check(file_get_contents($root . '/notes.txt') === 'user-owned', 'User output removed');
file_put_contents($root . '/collision.cpp', 'owned by user');
publication_reject(fn() => publication_write($root, $second + ['collision.cpp' => 'ours']), 'Adopted unowned artifact');
publication_reject(fn() => Output_Publication::require_ready($root), 'Failed revision is ready');
publication_check(file_get_contents($root . '/collision.cpp') === 'owned by user', 'Collision overwritten');
publication_write($root, $second);
file_put_contents($root . '/entry.cpp', 'user edit');
publication_reject(fn() => publication_write($root, $second), 'Edited output replaced');
publication_reject(fn() => publication_write($root, $second), 'Retry forgot edited output');
publication_check(file_get_contents($root . '/entry.cpp') === 'user edit', 'Edited output lost');
file_put_contents($root . '/entry.cpp', 'second');
unlink($root . '/decl.hpp');
publication_write($root, $second);
publication_check(file_get_contents($root . '/decl.hpp') === 'stable', 'Missing owned output not restored');

// Exact persisted states at crash boundaries: some writes/removals have happened,
// then manifest replacement may or may not have happened. Never inject runtime hooks.
foreach ([false, true] as $manifest_replaced) {
	$rows = [];
	foreach ($second as $path => $text) {
		$rows[] = ['path' => $path, 'before' => hash('sha256', $text), 'after' => $path === 'new.cpp' ? '' : hash('sha256', 'partial')];
	}
	$rows[] = ['path' => 'partial.cpp', 'before' => '', 'after' => hash('sha256', 'partial')];
	$journal = json_encode(['format' => 'scpp-output-journal-v1', 'files' => $rows]);
	file_put_contents($root . '/.scpp-output.status', 'scpp-output-v1:pending:' . hash('sha256', $journal) . "\n" . $journal);
	file_put_contents($root . '/entry.cpp', 'partial');
	file_put_contents($root . '/partial.cpp', 'partial');
	unlink($root . '/new.cpp');
	if ($manifest_replaced) { file_put_contents($root . '/.scpp-output.manifest', '{interrupted manifest revision}'); }
	publication_reject(fn() => Output_Publication::require_ready($root), 'Interrupted revision is ready');
	publication_write($root, $second);
	publication_check(!file_exists($root . '/partial.cpp'), 'Interrupted new output not retired');
	publication_check(file_get_contents($root . '/entry.cpp') === 'second', 'Interrupted write not recovered');
	Output_Publication::require_ready($root);
}

$manifest = file_get_contents($root . '/.scpp-output.manifest');
file_put_contents($root . '/.scpp-output.manifest', $manifest . ' ');
for ($attempt = 0; $attempt < 2; $attempt++) {
	publication_reject(fn() => publication_write($root, $second), 'Corrupt manifest accepted on retry');
}
file_put_contents($root . '/.scpp-output.manifest', $manifest);
$pending = new Output_Publication($root);
$pending->close();
$status = file_get_contents($root . '/.scpp-output.status');
file_put_contents($root . '/.scpp-output.status', $status . ' ');
for ($attempt = 0; $attempt < 2; $attempt++) {
	publication_reject(fn() => publication_write($root, $second), 'Corrupt journal accepted on retry');
}
file_put_contents($root . '/.scpp-output.status', $status);
publication_write($root, $second);

// Real coordinator failure must retain preparation handoffs, then retry once fixed.
Compiler_Lifecycle::reset();
$module = new module('/project', '/project', '/project');
$file = new file();
$file->path = 'main.phs';
$file->content = 'function answer(): int { return 7; } return answer();';
Source_Registry::add($module, $file);
Model::$modules->add($module->name, $module);
$compiler = new Compiler();
$compiler->sync(['/project/main.phs']);
$compiler->prepare(true);
$source = $module->sources['main.phs'];
$handoff = $source->parsed->collection->preparation_changes;
publication_check(count($handoff) > 0, 'No preparation handoff to test');
$coordinator_root = $root . '/coordinator';
mkdir($coordinator_root);
file_put_contents($coordinator_root . '/entry.cpp', 'unowned');
$worker = new Output_Publication($coordinator_root);
try { publication_reject(fn() => $compiler->cpp($worker), 'Coordinator accepted collision'); }
finally { $worker->close(); }
publication_check(Model::$cpp_files->is_empty() && $source->parsed->collection->preparation_changes === $handoff
	&& count($handoff) > 0, 'Failed publication acknowledged preparation');
rename($coordinator_root . '/entry.cpp', $coordinator_root . '/saved-user-file');
$worker = new Output_Publication($coordinator_root);
try { $compiler->cpp($worker); } finally { $worker->close(); }
publication_check(count($source->parsed->collection->preparation_changes) === 0, 'Success did not acknowledge preparation');
Output_Publication::require_ready($coordinator_root);
echo "Output publication: preservation, ownership, recovery, corruption and coordinator handoff passed\n";
