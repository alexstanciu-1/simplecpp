<?php
declare(strict_types=1);

require_once __DIR__ . '/../support/bootstrap.php';

use read_sources\Source_Reader;
use tokenize\Tokenizer;
use tokenize\Token_Set;

class Lexical_Update_Test
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
        throw new Exception('Expected rejection: ' . $reason);
    }
}

$path = '../fixtures/three_files/project.json';

// This frontend test removes value(); keep callers independent of that fixture.
file_put_contents('../fixtures/three_files/src/answer.phs', 'function answer(): int { return 42; }');
$session = new \compile\Compiler_Session();
$first = $session->compile($path);
$before = serialize($first);
Lexical_Update_Test::check(($first->stopped_before === 'build_native') && (!$first->completed) && ($session->generation === 0),
    'Tokenization is real progress, not completed compilation');
$second = $session->compile($path);
foreach ($first->inputs->sources->files as $file) {
    $id = $file->id;
    Lexical_Update_Test::check($first->inputs->tokens->for_file($id) === $second->inputs->tokens->for_file($id),
        'Unchanged updates reuse the actual token buffer');
    Lexical_Update_Test::check($second->inputs->sources->file_by_id($id)->needs_recompile,
        'Lexical validity must not clear unfinished semantic/backend work');
}
Lexical_Update_Test::check((\read_sources\Source_Read_Selection::select($second->inputs->sources, false) === [])
    && (\tokenize\Token_Selection::select($second->inputs->sources, $second->inputs->tokens, false) === []),
    'An unchanged update selects no read or tokenize tasks');

// Exercise the same token worker/join interfaces with reversed completion.
$sources = $second->inputs->sources;
$empty = new Token_Set();
$tasks = \tokenize\Token_Selection::select($sources, $empty, false);
$inputs_before = serialize([$sources, $tasks, $empty]);
$results = [];
foreach (array_reverse($tasks) as $source) {
    $results[] = \tokenize\File_Tokenizer::tokenize($source);
}
$tokens = (new \tokenize\Token_Join($sources, $empty, $tasks))->join($results);
Lexical_Update_Test::check(($tokens->to_json() === $second->inputs->tokens->to_json())
    && (serialize([$sources, $tasks, $empty]) === $inputs_before),
    'Independent token workers and deterministic join preserve inputs and exports');
Lexical_Update_Test::rejects(static fn() => (new \tokenize\Token_Join($sources, $empty, $tasks))->join([]), 'Incomplete');
Lexical_Update_Test::rejects(static fn() => (new \tokenize\Token_Join($sources, $empty, $tasks))->join([...$results, $results[0]]), 'duplicate');

$changed_path = $first->inputs->manifest->directory . '/src/nested/value.phs';
$id = $sources->find_file_id($changed_path);
$old_mtime = $sources->file_by_id($id)->mtime;
$old_tokens = $tokens->for_file($id);
file_put_contents($changed_path, 'function value(): int { return 76543210; }');
touch($changed_path, $old_mtime);
clearstatcache(true, $changed_path);
Lexical_Update_Test::check(filesize($changed_path) !== $sources->file_by_id($id)->size, 'Fixture must change size while preserving mtime');
$changed = $session->compile($path);
Lexical_Update_Test::check(!$changed->inputs->context->full_rebuild, 'Body edit can refresh lexical inputs selectively');
foreach ($sources->files as $file) {
    $a = $second->inputs->tokens->for_file($file->id);
    $b = $changed->inputs->tokens->for_file($file->id);
    Lexical_Update_Test::check(($a === $b) === ($file->id !== $id), 'Only edited file replaces its source and token buffers');
}
Lexical_Update_Test::check(str_contains($changed->inputs->tokens->for_file($id)->to_json(), '76543210'), 'Export reflects actual edited tokens');
Lexical_Update_Test::check(serialize($first) === $before, 'Earlier snapshots retain exact bytes and tokens');
$changed_sources = $changed->inputs->sources;
$new_source = $changed_sources->file_by_id($id)->buffer;
Lexical_Update_Test::rejects(static fn() => (new \tokenize\Token_Join($changed_sources, $tokens, [$new_source]))->join([$old_tokens]), 'stale');
Lexical_Update_Test::rejects(static fn() => (new \tokenize\Token_Join($changed_sources, $tokens, []))->join([]), 'stale');

$retained = $session->observed?->inputs;
$retained_before = serialize($retained);
file_put_contents($changed_path, 'return @;');
Lexical_Update_Test::rejects(static fn() => $session->compile($path), 'byte 7');
Lexical_Update_Test::check(($session->observed?->inputs === $retained) && (serialize($retained) === $retained_before),
    'Lexical failure leaves the entire last successful observation intact');
file_put_contents($changed_path, 'function value(): int { return 9876543210; }');
$repaired = $session->compile($path);
Lexical_Update_Test::check(str_contains($repaired->inputs->tokens->for_file($id)->to_json(), '9876543210'), 'Repair replaces failed work');
$fresh = (new \compile\Compiler_Session())->compile($path);
Lexical_Update_Test::check($repaired->inputs->tokens->to_json() === $fresh->inputs->tokens->to_json(),
    'Incremental tokens match fresh compilation of the same source');

file_put_contents($path, $first->inputs->manifest->content . "\n");
$full = $session->compile($path);
Lexical_Update_Test::check($full->inputs->context->full_rebuild, 'Manifest edit requests all work');
foreach ($full->inputs->sources->files as $file) {
    Lexical_Update_Test::check($full->inputs->tokens->for_file($file->id) !== $repaired->inputs->tokens->for_file($file->id),
        'Full rebuild recomputes every file through the common stages');
}
Lexical_Update_Test::check($full->inputs->tokens->to_json() === $fresh->inputs->tokens->to_json(), 'Full and selective token output agree');

// Repeated replacement must not accumulate unreachable payloads in the session.
for ($i = 0; $i < 12; $i++) {
    file_put_contents($changed_path, 'function value(): int { return ' . str_repeat('9', $i + 1) . '; }');
    $current = $session->compile($path);
    Lexical_Update_Test::check(count(array_filter($current->inputs->sources->files, static fn($file) => $file->buffer !== null)) === 3, 'Keep one current source buffer per live file');
}
$old_source_ref = WeakReference::create($current->inputs->sources->file_by_id($id)->buffer);
$old_tokens_ref = WeakReference::create($current->inputs->tokens->for_file($id));
unset($current);
file_put_contents($changed_path, 'function value(): int { return 123456789012345678; }');
$current = $session->compile($path);
Lexical_Update_Test::check(($old_source_ref->get() !== null) && ($old_tokens_ref->get() !== null),
    'Returned comparison catalog retains the old source/token origin while needed');
$removed_source_ref = WeakReference::create($current->inputs->sources->file_by_id($id)->buffer);
$removed_tokens_ref = WeakReference::create($current->inputs->tokens->for_file($id));
unset($current);
Lexical_Update_Test::check(($old_source_ref->get() === null) && ($old_tokens_ref->get() === null),
    'Discarding the catalog releases old payloads; the session retains only current symbols');
unlink($changed_path);
$removed = $session->compile($path);
Lexical_Update_Test::check(($removed_source_ref->get() !== null) && ($removed_tokens_ref->get() !== null),
    'Removal catalog preserves previous origins for downstream retirement');
Lexical_Update_Test::check(($removed->inputs->tokens->for_file($id) === null)
    && ($removed->inputs->sources->file_by_id($id)->buffer === null)
    && (count(array_filter($removed->inputs->sources->files, static fn($file) => $file->buffer !== null)) === 2), 'Removal excludes both source and tokens');
unset($removed);
Lexical_Update_Test::check(($removed_source_ref->get() === null) && ($removed_tokens_ref->get() === null),
    'Removed payloads are released after the catalog reader finishes');
file_put_contents($changed_path, 'function value(): int { return 101; }');
$added = $session->compile($path);
$new_id = $added->inputs->sources->find_file_id($changed_path);
Lexical_Update_Test::check(($new_id !== $id) && ($added->inputs->tokens->for_file($new_id) !== null)
    && ($added->inputs->tokens->for_file($id) === null), 'Recreated paths get new source/token identity');
echo "lexical updates ok: zero-work reuse, reversed completion, partial/stale-result rejection, body edit, failure/repair, fresh equivalence, full rebuild and bounded buffer retention\n";
