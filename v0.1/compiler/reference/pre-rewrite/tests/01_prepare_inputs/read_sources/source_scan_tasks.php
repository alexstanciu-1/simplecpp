<?php
declare(strict_types=1);

require_once __DIR__ . '/../../support/bootstrap.php';

use read_sources\Source_Scanner;
use read_sources\Source_Scan_Result;
use read_sources\Source_Reader;
use read_sources\Source_Set;
use read_sources\source_scan_task;
use read_sources\source_folder;

class Source_Scan_Test
{
    public static function check(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new Exception($message);
        }
    }

    public static function reject_batch(Source_Set $current, array $tasks, array $results): void
    {
        $before = serialize($current);
        $rejected = false;
        try {
            (new \read_sources\Source_Scan_Join($current, new Source_Set(), $tasks))->join($results);
        }
        catch (Exception $exception) {
            $rejected = str_contains($exception->getMessage(), 'source scan');
        }
        self::check(($rejected) && (serialize($current) === $before),
            'Missing, duplicate or foreign results must fail before joining');
    }
}

$manifest = \Step_Test::run(new \read_manifest\Manifest_Reader('../fixtures/three_files/project.json'));
$root = $manifest->directory . '/src';
mkdir($root . '/sibling/deep', 0777, true);
mkdir($manifest->directory . '/other');
file_put_contents($root . '/sibling/extra.phs', 'function extra_4(): int { return 4; }');
file_put_contents($root . '/sibling/deep/last.phs', 'function extra_5(): int { return 5; }');
file_put_contents($root . '/sibling/notes.txt', 'ignored');
file_put_contents($manifest->directory . '/other/helper.phs', 'function extra_6(): int { return 6; }');
$manifest->source_folder_paths[] = 'other';
$empty = new Source_Set();
$expected = \Step_Test::run(new \read_sources\Source_Discovery($manifest, $empty));

// Construct the candidate's roots; exercise actual worker and join boundaries
// independently of the serial executor, with reverse completion order.
$current = new Source_Set();
$tasks = [];
foreach ($manifest->source_folder_paths as $index => $configured)
{
    $resolved = \read_sources\Source_Paths::resolve($manifest->directory, $configured);
    $folder = new source_folder();
    $folder->path = $configured;
    $folder->resolved_path = $resolved;
    $current->folders[] = $folder;
    $tasks[] = new source_scan_task($index, $index, $resolved, '');
}
$saw_parallel_subdirectories = false;
while ($tasks !== [])
{
    $before_candidate = serialize($current);
    $before_inputs = serialize([$empty, $tasks]);
    $results = [];
    foreach (array_reverse($tasks) as $task)
    {
        $results[] = Source_Scanner::scan($task);
        if ($task->relative_directory === '') {
            foreach ($results[array_key_last($results)]->files as $file) {
                Source_Scan_Test::check(!str_contains($file->relative_path, '/'),
                    'A root task must not scan its child directories');
            }
        }
    }
    Source_Scan_Test::check((serialize($current) === $before_candidate)
        && (serialize([$empty, $tasks]) === $before_inputs), 'Workers must not mutate shared inputs');
    $before_results = serialize($results);
    Source_Scan_Test::reject_batch($current, $tasks, array_slice($results, 1));
    Source_Scan_Test::reject_batch($current, $tasks, [...$results, $results[0]]);
    Source_Scan_Test::reject_batch($current, $tasks,
        [...$results, new Source_Scan_Result(new source_scan_task(count($tasks), 0, $root, ''), [], [])]);

    // Batch-local indexes and even identical task fields cannot identify the originating task.
    $foreign = $results;
    $foreign[0] = new Source_Scan_Result(clone $results[0]->task, $results[0]->files, $results[0]->directories);
    Source_Scan_Test::reject_batch($current, $tasks, $foreign);
    $foreign[0] = new Source_Scan_Result(
        new source_scan_task($results[0]->task->index, 0, $root . '/different-root', ''), [], []);
    Source_Scan_Test::reject_batch($current, $tasks, $foreign);
    $tasks = (new \read_sources\Source_Scan_Join($current, $empty, $tasks))->join($results);
    Source_Scan_Test::check(serialize($results) === $before_results, 'Joins must not mutate worker outputs');
    $source_children = array_filter($tasks, static fn($task) => $task->top_folder_index === 0);
    if (count($source_children) >= 2) {
        $saw_parallel_subdirectories = true;
    }
}
$current->refresh_indexes();
$current->set_entry_file($current->find_file_id(\read_sources\Source_Paths::resolve($manifest->directory, $manifest->entry_path)));
Source_Scan_Test::check($saw_parallel_subdirectories, 'A single source root must expose independent subtree tasks');
Source_Scan_Test::check($current->to_json() === $expected->to_json(),
    'Reverse completion must preserve metadata, IDs, ordering, exports and module indexes');

// Failure after scheduling must stay a failure, never an empty directory result.
$parent = Source_Scanner::scan(new source_scan_task(0, 0, $root, 'sibling'));
Source_Scan_Test::check($parent->directories === ['sibling/deep'], 'Parent discovers child work only');
rename($root . '/sibling/deep', $root . '/parked');
$failed = false;
try {
    Source_Scanner::scan(new source_scan_task(0, 0, $root, 'sibling/deep'));
}
catch (Exception $exception) {
    $failed = str_starts_with($exception->getMessage(), 'Cannot scan source directory:');
}
Source_Scan_Test::check($failed, 'A disappeared queued directory must fail the scan');
rename($root . '/parked', $root . '/sibling/deep');

// A later worker failure cannot replace the resident session's previous result.
$session = new \compile\Compiler_Session();
$session->compile($manifest->path);
$retained = serialize($session->observed?->inputs);
symlink($root . '/sibling/deep', $root . '/sibling/unsupported-link');
$failed = false;
try {
    $session->compile($manifest->path);
}
catch (Exception $exception) {
    $failed = str_starts_with($exception->getMessage(), 'Symbolic links inside source roots are unsupported:');
}
Source_Scan_Test::check(($failed) && (serialize($session->observed?->inputs) === $retained),
    'A failed directory worker must preserve the retained snapshot');
unlink($root . '/sibling/unsupported-link');
$session->compile($manifest->path);
echo "source scan tasks ok: independent directories, reverse completion, batch completeness, input purity and failure recovery\n";
