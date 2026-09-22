<?php
declare(strict_types=1);

require_once __DIR__ . '/../../support/bootstrap.php';

use read_sources\Source_Reader;
use read_sources\Source_Set;

class Source_Snapshot_Test
{
    public static function check(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new Exception($message);
        }
    }

    public static function rejects(callable $action, string $message): void
    {
        try {
            $action();
        }
        catch (Exception $exception) {
            self::check(str_contains($exception->getMessage(), $message), $exception->getMessage());
            return;
        }
        throw new Exception('Expected rejection: ' . $message);
    }
}

$manifest = \Step_Test::run(new \read_manifest\Manifest_Reader('../fixtures/three_files/project.json'));
file_put_contents($manifest->directory . '/src/empty.phs', '');
$discovered = \Step_Test::run(new \read_sources\Source_Discovery($manifest, new Source_Set()));
$before = serialize($discovered);
$tasks = \read_sources\Source_Read_Selection::select($discovered, false);
$task_before = serialize($tasks);
Source_Snapshot_Test::check(count($tasks) === 4, 'Missing buffers select every current file');
$results = [];
foreach (array_reverse($tasks) as $task) {
    $results[] = \read_sources\Snapshot_Reader::read($task);
}
Source_Snapshot_Test::check((serialize($tasks) === $task_before) && (serialize($discovered) === $before),
    'Source workers must not mutate tasks or shared data');
$read = (new \read_sources\Snapshot_Join($discovered, $tasks))->join($results);
$ordered = (new \read_sources\Snapshot_Join($discovered, $tasks))->join(array_reverse($results));
Source_Snapshot_Test::check(serialize($read) === serialize($ordered), 'Source joins must ignore arrival order');
foreach ($read->files as $file) {
    $buffer = $file->buffer;
    Source_Snapshot_Test::check(($buffer->content === file_get_contents($buffer->path))
        && ($buffer->source_file_id === $file->id), 'Snapshots contain exact source bytes, including empty files');
}
Source_Snapshot_Test::rejects(static fn() => (new \read_sources\Snapshot_Join($discovered, $tasks))->join([]), 'Incomplete');
Source_Snapshot_Test::rejects(static fn() => (new \read_sources\Snapshot_Join($discovered, $tasks))->join([...$results, $results[0]]), 'duplicate');
Source_Snapshot_Test::check(serialize($discovered) === $before, 'Failed joins leave the candidate untouched');

$unchanged = \Step_Test::run(new \read_sources\Source_Discovery($manifest, $read));
Source_Snapshot_Test::check(\read_sources\Source_Read_Selection::select($unchanged, false) === [], 'Unchanged files require no reads');
Source_Snapshot_Test::check(count(\read_sources\Source_Read_Selection::select($unchanged, true)) === 4, 'Full rebuild selects all through the same path');
$reused = (new \read_sources\Snapshot_Join($unchanged, []))->join([]);
foreach ($read->files as $file) {
    Source_Snapshot_Test::check($file->buffer
        === $reused->file_by_id($file->id)->buffer, 'Unchanged payloads share identity');
    Source_Snapshot_Test::check($unchanged->file_by_id($file->id)
        === $reused->file_by_id($file->id), 'Joining unchanged snapshots must share metadata rows too');
}

$baseline = serialize($reused);
$path = $manifest->directory . '/src/nested/value.phs';
$id = $reused->find_file_id($path);
$mtime = $reused->file_by_id($id)->mtime;
file_put_contents($path, 'return 123456;');
touch($path, $mtime); // Same-second size-only change, without a timing-dependent test.
$changed = \Step_Test::run(new \read_sources\Source_Discovery($manifest, $reused));
$tasks = \read_sources\Source_Read_Selection::select($changed, false);
Source_Snapshot_Test::check((count($tasks) === 1) && ($tasks[0]->source_file_id === $id), 'Only the changed file needs bytes');
$replacement = \read_sources\Snapshot_Reader::read($tasks[0]);
$changed_before = serialize($changed);
$next = (new \read_sources\Snapshot_Join($changed, $tasks))->join([$replacement]);
Source_Snapshot_Test::check(serialize($changed) === $changed_before, 'Successful join leaves its input untouched');
foreach ($changed->files as $file) {
    Source_Snapshot_Test::check(($file === $next->file_by_id($file->id)) === ($file->id !== $id),
        'Only a replaced buffer needs a new metadata row');
}
Source_Snapshot_Test::check((count(array_filter($next->files, static fn($file) => $file->buffer !== null)) === 4) && (serialize($reused) === $baseline),
    'Replacement dataset keeps only live payloads and leaves retained snapshots intact');
Source_Snapshot_Test::check($next->file_by_id($id)->buffer->content === 'return 123456;', 'Adopt edited bytes');

// A selected read must reject edits/deletion after discovery, rather than adopt stale metadata.
file_put_contents($path, 'return 123456789;');
Source_Snapshot_Test::rejects(static fn() => \read_sources\Snapshot_Reader::read($tasks[0]), 'Source changed during reading');
unlink($path);
Source_Snapshot_Test::rejects(static fn() => \read_sources\Snapshot_Reader::read($tasks[0]), 'not a regular file');
$removed = \Step_Test::run(new \read_sources\Source_Discovery($manifest, $next));
$removed_row = $removed->file_by_id($id);
$removed = (new \read_sources\Snapshot_Join($removed, []))->join([]);
Source_Snapshot_Test::check($removed->file_by_id($id) === $removed_row, 'Already-cleared tombstone is shared');
Source_Snapshot_Test::check(($removed->file_by_id($id)->buffer === null) && (count(array_filter($removed->files, static fn($file) => $file->buffer !== null)) === 3),
    'Removed rows retain no source buffer');
Source_Snapshot_Test::check(serialize($reused) === $baseline, 'Failure and removal leave old bytes usable');

// The join also clears a deleted row whose buffer has not yet been withdrawn.
$pending = clone $next;
$pending->files = [];
foreach ($next->files as $file)
{
    if ($file->id === $id) {
        $file = clone $file;
        $file->change_state = \read_sources\file_change::deleted;
        $file->top_folder_index = -1;
        $file->needs_recompile = false;
    }
    $pending->files[] = $file;
}
$pending->refresh_indexes();
$pending_before = serialize($pending);
$cleared = (new \read_sources\Snapshot_Join($pending, []))->join([]);
Source_Snapshot_Test::check(($cleared->file_by_id($id)->buffer === null)
    && ($cleared->file_by_id($id) !== $pending->file_by_id($id))
    && (serialize($pending) === $pending_before), 'Clearing a tombstone clones only its record');

// Sharing retained rows must not skip their metadata validation.
$stale = clone $reused;
$stale->files = $reused->files;
$stale->files[0] = clone $stale->files[0];
$stale->files[0]->size++;
Source_Snapshot_Test::rejects(static fn() => (new \read_sources\Snapshot_Join($stale, []))->join([]), 'Stale retained');

echo "source snapshots ok: exact reads, empty files, task isolation, deterministic joins, reuse, replacement, missing results and changed/deleted input rejection\n";
