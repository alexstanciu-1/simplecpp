<?php
declare(strict_types=1);

require_once __DIR__ . '/../support/bootstrap.php';

use parse\File_Parser;
use parse\Frontend_Set;
use parse\Frontend_Join;

class Parse_Update_Test
{
    public static function check(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new Exception($message);
        }
    }

    public static function rejects(callable $action, string $reason): void
    {
        try {
            $action();
        }
        catch (Exception $exception) {
            self::check(str_contains($exception->getMessage(), $reason), $exception->getMessage());
            return;
        }
        throw new Exception('Expected failure: ' . $reason);
    }
}

$path = '../fixtures/three_files/project.json';

// This frontend test removes value(); keep callers independent of that fixture.
file_put_contents('../fixtures/three_files/src/answer.phs', 'function answer(): int { return 42; }');
$session = new \compile\Compiler_Session();
$first = $session->compile($path);
Parse_Update_Test::check(($first->stopped_before === 'build_native') && (!$first->completed)
    && ($session->generation === 0) && ($session->published === null), 'Real parsing must not publish semantic compilation');
$sources = $first->inputs->sources;
$tokens = $first->inputs->tokens;
$before = serialize($first);
$second = $session->compile($path);
foreach ($sources->files as $file) {
    Parse_Update_Test::check($first->inputs->frontends->for_file($file->id) === $second->inputs->frontends->for_file($file->id),
        'Unchanged files reuse the actual AST result');
}

$empty = new Frontend_Set();
// Explicit whole-project batch for independent worker and segmented-join proofs.
$tasks = array_map(static fn($file) => $tokens->for_file($file->id), $sources->files);
$results = [];
foreach (array_reverse($tasks) as $task) {
    $results[] = (new File_Parser($task))->parse();
}
$join = new Frontend_Join($empty, $sources, $tokens, $tasks);
$join->merge($results, 1, 1);
Parse_Update_Test::rejects(static fn() => $join->finish(), 'Incomplete');
$join->merge($results, 0, 1);
Parse_Update_Test::rejects(static fn() => $join->finish(), 'Incomplete');
$join->merge($results, 2, 1);
$joined = $join->finish();
foreach ($results as $result) {
    Parse_Update_Test::check($joined->for_file($result->source_file_id) === $result, 'Join shares exact worker results');
}
Parse_Update_Test::check(($joined->to_json() === $first->inputs->frontends->to_json())
    && (serialize($first) === $before), 'Reversed execution and segmented joins preserve order, exports and retained inputs');
foreach ([[-1, 1], [0, -1], [4, 0], [2, 2], [0, PHP_INT_MAX]] as [$index, $count]) {
    Parse_Update_Test::rejects(static fn() => $join->merge($results, $index, $count), 'Invalid frontend result segment');
}
$prepared_before = serialize($join);
Parse_Update_Test::rejects(static fn() => $join->merge([$results[0], $results[0]], 0, 2), 'Duplicate');
Parse_Update_Test::check(serialize($join) === $prepared_before, 'Rejected segment preserves accepted preparation');
$joined_before = serialize($joined);
$replacement = (new File_Parser($results[0]->tokens))->parse();
Parse_Update_Test::rejects(static fn() => $join->merge([$replacement], 0, 1), 'Duplicate');
Parse_Update_Test::rejects(static fn() => $join->merge($results, 0, count($results)), 'Duplicate');
$join->merge([], 0, 0);
Parse_Update_Test::check(($join->finish()->to_json() === $joined->to_json()) && (serialize($joined) === $joined_before),
    'Duplicate segments cannot replace accepted results; empty segments and finish preserve snapshots');

// A forced parse of the same token objects still requires its own completed work.
$forced = new Frontend_Join($first->inputs->frontends, $sources, $tokens, $tasks);
Parse_Update_Test::rejects(static fn() => $forced->finish(), 'Incomplete');
$forced->merge($results, 0, count($results));
Parse_Update_Test::check($forced->finish()->for_file($results[0]->source_file_id) === $results[0],
    'Matching retained tokens never substitute for a selected reparse');
$unselected = new Frontend_Join($first->inputs->frontends, $sources, $tokens, []);
Parse_Update_Test::rejects(static fn() => $unselected->merge([$results[0]], 0, 1), 'unselected');
Parse_Update_Test::rejects(static fn() => (new Frontend_Join($empty, $sources, $tokens, [$tasks[0], $tasks[0]]))->join([]), 'Duplicate');

// A valid first result followed by malformed syntax must adopt neither result.
$atomic = new Frontend_Join($empty, $sources, $tokens, $tasks);
$atomic->merge($results, 1, 1);
$atomic_before = serialize($atomic);
$invalid = clone $results[2];
$invalid->defined_entities = [$invalid->entry_body_id];
Parse_Update_Test::rejects(static fn() => $atomic->merge([$results[0], $invalid], 0, 2), 'Invalid');
Parse_Update_Test::check(serialize($atomic) === $atomic_before, 'Malformed later result cannot partially edit a segment');
$atomic->merge($results, 2, 1);
Parse_Update_Test::rejects(static fn() => $atomic->finish(), 'Incomplete');
$atomic->merge($results, 0, 1);
Parse_Update_Test::check($atomic->finish()->to_json() === $joined->to_json(), 'Preparation can recover after a rejected segment');
$unfinished = clone $results[0];
$unfinished->entry_body_id = 0;
Parse_Update_Test::rejects(static fn() => new Frontend_Set([$unfinished]), 'Invalid');

$changed_path = $first->inputs->manifest->directory . '/src/nested/value.phs';
$id = $sources->find_file_id($changed_path);
file_put_contents($changed_path, 'function value(): int { return 123456789; }');
$changed = $session->compile($path);
Parse_Update_Test::check(!$changed->inputs->context->full_rebuild, 'Body edit passes the incremental gate with selective frontend work');
foreach ($sources->files as $file) {
    Parse_Update_Test::check(($first->inputs->frontends->for_file($file->id) === $changed->inputs->frontends->for_file($file->id)) === ($file->id !== $id),
        'A file edit replaces exactly its own frontend');
}
$new_sources = $changed->inputs->sources;
$new_tokens = $changed->inputs->tokens;
$stale = $first->inputs->frontends->for_file($id);
$partial = new Frontend_Join($first->inputs->frontends, $new_sources, $new_tokens, [$new_tokens->for_file($id)]);
Parse_Update_Test::rejects(static fn() => $partial->merge([$stale], 0, 1), 'stale');
Parse_Update_Test::rejects(static fn() => $partial->finish(), 'Incomplete');
$fresh = (new \compile\Compiler_Session())->compile($path);
Parse_Update_Test::check($fresh->inputs->frontends->to_json() === $changed->inputs->frontends->to_json(), 'Selective ASTs equal a fresh parse');
Parse_Update_Test::check(serialize($first) === $before, 'Source/token/AST snapshots stay intact after edits');

$retained = $session->observed?->inputs;
$retained_before = serialize($retained);
file_put_contents($changed_path, 'return 9'); // Lexically valid, missing semicolon.
Parse_Update_Test::rejects(static fn() => $session->compile($path), "Expected ';'");
Parse_Update_Test::check(($session->observed?->inputs === $retained) && (serialize($retained) === $retained_before),
    'Parsing failure must not retain partially refreshed bytes, tokens or ASTs');
file_put_contents($changed_path, 'function value(): int { return 999; }');
$repaired = $session->compile($path);
Parse_Update_Test::check(str_contains($repaired->inputs->frontends->for_file($id)->to_json(), '999'), 'Repair resumes through the common compiler path');
file_put_contents($path, $first->inputs->manifest->content . "\n");
$full = $session->compile($path);
foreach ($sources->files as $file) {
    Parse_Update_Test::check($full->inputs->frontends->for_file($file->id) !== $repaired->inputs->frontends->for_file($file->id),
        'Full rebuild reparses every file through the same worker');
}
Parse_Update_Test::check($full->inputs->frontends->to_json() === $repaired->inputs->frontends->to_json(), 'Full/selective outputs agree');
$removed_frontend = $full->inputs->frontends->for_file($id);
unlink($changed_path);
$removed = $session->compile($path);
Parse_Update_Test::check($removed->inputs->frontends->for_file($id) === null, 'Deleted files are excluded from frontends even in full mode');
$with_deleted = [$removed_frontend];
foreach ($removed->inputs->sources->files as $file) {
    $live = $removed->inputs->frontends->for_file($file->id);
    if ($live !== null) {
        $with_deleted[] = $live;
    }
}
$removal_join = new Frontend_Join(new Frontend_Set($with_deleted), $removed->inputs->sources, $removed->inputs->tokens, []);
Parse_Update_Test::rejects(static fn() => $removal_join->merge([$removed_frontend], 0, 1), 'removed');
Parse_Update_Test::check($removal_join->finish()->for_file($id) === null, 'Finalization withdraws deleted contributions even without a segment');
file_put_contents($changed_path, 'function value(): int { return 123; }');
$added = $session->compile($path);
$new_id = $added->inputs->sources->find_file_id($changed_path);
Parse_Update_Test::check(($new_id !== $id) && ($added->inputs->frontends->for_file($new_id) !== null)
    && ($added->inputs->frontends->for_file($id) === null), 'Recreated file receives a new frontend identity');

// An independent resident session must not retain obsolete syntax without readers.
$isolated = new \compile\Compiler_Session();
$initial = $isolated->compile($path);
$initial_id = $initial->inputs->sources->find_file_id($changed_path);
$old_tree = WeakReference::create($initial->inputs->frontends->for_file($initial_id)->syntax);
unset($initial);
file_put_contents($changed_path, 'function value(): int { return 1234567890; }');
$isolated->compile($path);
Parse_Update_Test::check($old_tree->get() === null, 'Unreferenced replaced ASTs must be released');
echo "parse updates ok: snapshot reuse, full/selective equivalence, independent workers, segment joins, completeness, failure/repair, removal and AST release\n";
