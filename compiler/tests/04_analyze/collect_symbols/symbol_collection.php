<?php
declare(strict_types=1);

require_once __DIR__ . '/../../support/bootstrap.php';

use collect_symbols\Declaration_Collector as Collector;
use collect_symbols\Symbol_Store;
use collect_symbols\symbol_kind;
use collect_symbols\change_status;

class Symbol_Collection_Test
{
    public static function check(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new Exception($message);
        }
    }

    public static function rejects(callable $action, string $reason): Exception
    {
        try {
            $action();
        }
        catch (Exception $error) {
            self::check(str_contains($error->getMessage(), $reason), $error->getMessage());
            return $error;
        }
        throw new Exception('Expected rejection: ' . $reason);
    }

    public static function edit(string $path, string $text): void
    {
        clearstatcache(true, $path);
        $mtime = is_file($path) ? filemtime($path) : time();
        file_put_contents($path, $text);
        touch($path, $mtime + 2); // Explicit metadata change, no production sleeps.
        clearstatcache(true, $path);
    }

    public static function normalized(Symbol_Store $store): array
    {
        $rows = [];
        foreach ($store->records() as $symbol) {
            $row = $symbol->to_array();
            unset($row['symbol_id'], $row['source_file_id']);
            $row['path'] = $symbol->frontend->tokens->source->path;
            $rows[] = $row;
        }
        usort($rows, static fn($a, $b) => [$a['path'], $a['kind'], $a['name']] <=> [$b['path'], $b['kind'], $b['name']]);
        return $rows;
    }
}

$manifest = '../fixtures/three_files/project.json';

// Keep collection mutation scenarios independent of call validity; resolution has its own tests.
Symbol_Collection_Test::edit('../fixtures/three_files/src/answer.phs', 'function answer(): int { return 42; }');
$session = new \compile\Compiler_Session();
$first = $session->compile($manifest);
$store = $first->symbols->current;
$before = serialize($first);
Symbol_Collection_Test::check(($first->stopped_before === 'build_native') && (!$first->completed)
    && ($session->generation === 0) && ($session->published === null)
    && ($session->observed?->symbols === $store), 'Collection is accepted progress, not published compilation');
Symbol_Collection_Test::check((count($store->records()) === 5) && (count($first->symbols->changes) === 5),
    'Collect three unnamed entries and both named functions');
$answer_id = $store->find_symbol('answer', '', symbol_kind::function_symbol);
$value_id = $store->find_symbol('value', '', symbol_kind::function_symbol);
Symbol_Collection_Test::check(($answer_id !== 0) && ($value_id !== 0) && ($answer_id !== $value_id)
    && ($store->find_symbol('missing', '', symbol_kind::function_symbol) === 0)
    && ($store->find_symbol('', '', symbol_kind::file_entry) === 0),
    'Project lookup finds cross-file declarations without fabricating named entries');
foreach ($first->inputs->sources->files as $file)
{
    $entry_id = $store->entry_symbol_id($file->id);
    $entry = $store->symbol_by_id($entry_id);
    Symbol_Collection_Test::check(($entry->kind === symbol_kind::file_entry) && ($entry->name === '')
        && ($entry->declaration_node_id === 0) && ($entry->frontend === $first->inputs->frontends->for_file($file->id))
        && ($entry->body_node_id === $entry->frontend->entry_body_id)
        && in_array($entry_id, $store->file_symbol_ids($file->id), true),
        'Every file, including a declaration-only file, has an exact-snapshot entry owner');
}
Symbol_Collection_Test::check((count($store->child_symbol_ids(0)) === 5) && ($store->child_symbol_ids(999) === []),
    'Semantic owner index shares the same records');
foreach ($first->symbols->changes as $change) {
    Symbol_Collection_Test::check(($change->own_status === change_status::added) && ($change->previous === null),
        'Initial collection catalogs genuine additions');
}
foreach ([-1, 0, 999] as $invalid) {
    Symbol_Collection_Test::rejects(static fn() => $store->symbol_by_id($invalid), 'Unknown symbol ID');
}

$again = $session->compile($manifest);
Symbol_Collection_Test::check(($again->symbols->changes === [])
    && (\Step_Test::select(Collector::class, $store, $again->inputs->sources, $again->inputs->frontends, false) === []),
    'Unchanged files require no declaration extraction and no change rows');
foreach ($store->records() as $symbol) {
    Symbol_Collection_Test::check($again->symbols->current->symbol_by_id($symbol->symbol_id) === $symbol,
        'Unchanged declarations share the same snapshot records');
}

// Independent workers, deterministic join, exact task/result membership.
$empty = new Symbol_Store();
$tasks = \Step_Test::select(Collector::class, $empty, $first->inputs->sources, $first->inputs->frontends, false);
$results = [];
foreach (array_reverse($tasks) as $task) {
    $results[] = \collect_symbols\File_Collector::collect_file($task);
}
$joined = (new \collect_symbols\Declaration_Join($empty, $first->inputs->sources, $first->inputs->frontends, $tasks))->join($results);
Symbol_Collection_Test::check(($joined->to_json() === $first->symbols->to_json()) && (serialize($first) === $before),
    'Reverse worker completion yields identical identities, catalog and exports without baseline mutation');
$reordered = clone $first->inputs->sources;
$reordered->files = array_reverse($reordered->files);
$reordered->refresh_indexes();
$reordered_symbols = \Step_Test::run(new Collector($store, $reordered, $first->inputs->frontends, true))->current;
Symbol_Collection_Test::check(($reordered_symbols->find_symbol('answer', '', symbol_kind::function_symbol) === $answer_id)
    && ($reordered_symbols->find_symbol('value', '', symbol_kind::function_symbol) === $value_id)
    && (Symbol_Collection_Test::normalized($reordered_symbols) === Symbol_Collection_Test::normalized($store)),
    'Source enumeration changes neither project identity nor lookup facts');
Symbol_Collection_Test::rejects(static fn() => (new \collect_symbols\Declaration_Join($empty, $first->inputs->sources, $first->inputs->frontends, $tasks))->join([]), 'Incomplete');
Symbol_Collection_Test::rejects(static fn() => (new \collect_symbols\Declaration_Join($empty, $first->inputs->sources, $first->inputs->frontends, $tasks))->join([...$results, $results[0]]), 'duplicate');
Symbol_Collection_Test::rejects(static fn() => (new \collect_symbols\Declaration_Join($empty, $first->inputs->sources, $first->inputs->frontends, []))->join($results), 'Unexpected');
Symbol_Collection_Test::rejects(static fn() => (new \collect_symbols\Declaration_Join($empty, $first->inputs->sources, $first->inputs->frontends, []))->join([]), 'Incomplete');

$path = $first->inputs->manifest->directory . '/src/nested/value.phs';
$file_id = $first->inputs->sources->find_file_id($path);
Symbol_Collection_Test::edit($path, 'function value(): int { return 43; }');
$edited = $session->compile($manifest);
$new_store = $edited->symbols->current;
Symbol_Collection_Test::check(($new_store->find_symbol('value', '', symbol_kind::function_symbol) === $value_id)
    && ($new_store->symbol_by_id($value_id)->frontend !== $store->symbol_by_id($value_id)->frontend)
    && ($new_store->symbol_by_id($answer_id) === $store->symbol_by_id($answer_id)),
    'Body edit preserves identity, refreshes its file references and retains untouched records');
$raw = \Step_Test::run(new Collector($store, $edited->inputs->sources, $edited->inputs->frontends, false));
Symbol_Collection_Test::check(count($raw->changes) === 2, 'Collection selects the reparsed entry and function for comparison');
foreach ($raw->changes as $change) {
    Symbol_Collection_Test::check(($change->own_status === change_status::uncompared)
        && ($change->children_changed === null),
        'Matching identity is not falsely classified as equality or a body-only change');
}
Symbol_Collection_Test::check((count($edited->symbols->changes) === 1)
    && ($edited->symbols->changes[0]->current->symbol_id === $value_id)
    && ($edited->symbols->changes[0]->own_status === change_status::unchanged)
    && ($edited->symbols->changes[0]->children_changed === true),
    'Completed comparison retains only the actual executable child change');
Symbol_Collection_Test::rejects(static fn() => (new \collect_symbols\Declaration_Join($store, $edited->inputs->sources, $edited->inputs->frontends, [$tasks[0]]))->join($results), 'Unexpected');
$old_task = $first->inputs->frontends->for_file($file_id);
Symbol_Collection_Test::rejects(static fn() => (new \collect_symbols\Declaration_Join($store, $edited->inputs->sources, $edited->inputs->frontends, [$old_task]))->join([]), 'stale');

// Duplicate failures cannot replace either accepted input or semantic state.
$retained_inputs = $session->observed?->inputs;
$retained_symbols = $session->observed?->symbols;
$retained_dump = serialize([$retained_inputs, $retained_symbols]);
$duplicate = 'function value(): int { return 1; } function answer(): int { return 2; }';
Symbol_Collection_Test::edit($path, $duplicate);
$error = Symbol_Collection_Test::rejects(static fn() => $session->compile($manifest), 'Duplicate');
Symbol_Collection_Test::check(($error instanceof \diagnostics\Source_Error) && ($error->path === $path)
    && ($error->start === strpos($duplicate, 'answer')) && ($error->length === strlen('answer'))
    && str_contains($error->getMessage(), 'first defined at'),
    'Cross-file duplicate diagnostic anchors the conflicting name and identifies the first declaration');
Symbol_Collection_Test::check(($session->observed?->inputs === $retained_inputs) && ($session->observed?->symbols === $retained_symbols)
    && (serialize([$retained_inputs, $retained_symbols]) === $retained_dump), 'Duplicate failure preserves all accepted state and identity allocation');
$same_file_duplicate = "/* prefix */\nfunction value(): int {}\nfunction value(): int {}";
Symbol_Collection_Test::edit($path, $same_file_duplicate);
$error = Symbol_Collection_Test::rejects(static fn() => $session->compile($manifest), 'Duplicate');
Symbol_Collection_Test::check(($error instanceof \diagnostics\Source_Error) && ($error->path === $path)
    && ($error->start === strrpos($same_file_duplicate, 'value')) && ($error->length === strlen('value'))
    && str_ends_with($error->getMessage(), 'first defined at ' . $path . ': byte ' . strpos($same_file_duplicate, 'function')),
    'Same-file duplicate anchors the second name and preserves the first declaration offset');
Symbol_Collection_Test::edit($path, 'function number(): int { return 123; }');
$renamed = $session->compile($manifest);
$number_id = $renamed->symbols->current->find_symbol('number', '', symbol_kind::function_symbol);
Symbol_Collection_Test::check(($number_id >= $retained_symbols->next_symbol_id())
    && (!$renamed->symbols->current->contains($value_id))
    && ($renamed->symbols->current->find_symbol('value', '', symbol_kind::function_symbol) === 0),
    'Rename creates a new identity and withdraws the old definition; existing calls remain valid');
$statuses = [];
foreach ($renamed->symbols->changes as $change) {
    $statuses[($change->current ?? $change->previous)->symbol_id] = $change->own_status;
}
Symbol_Collection_Test::check(($statuses[$value_id] === change_status::removed) && ($statuses[$number_id] === change_status::added),
    'Rename is cataloged as deletion plus addition');

// Moving a definition between files preserves its project identity after the join.
$extra = $first->inputs->manifest->directory . '/src/extra.phs';
Symbol_Collection_Test::edit($path, '// definition moved');
Symbol_Collection_Test::edit($extra, 'function number(): int { return 123; }');
$moved = $session->compile($manifest);
$extra_id = $moved->inputs->sources->find_file_id($extra);
Symbol_Collection_Test::check(($moved->symbols->current->find_symbol('number', '', symbol_kind::function_symbol) === $number_id)
    && ($moved->symbols->current->symbol_by_id($number_id)->frontend->source_file_id === $extra_id)
    && (!in_array($number_id, $moved->symbols->current->file_symbol_ids($file_id), true)),
    'Project identity does not depend on source file; source index is replaced without a transient duplicate');
$extra_entry = $moved->symbols->current->entry_symbol_id($extra_id);
$old_tree = WeakReference::create($moved->inputs->frontends->for_file($extra_id)->syntax);
$old_symbol = $moved->symbols->current->symbol_by_id($number_id);
$old_max = $moved->symbols->current->next_symbol_id();
unset($moved);
unlink($extra);
$removed = $session->compile($manifest);
$remaining = $removed->symbols->current;
Symbol_Collection_Test::check((!$remaining->contains($number_id)) && (!$remaining->contains($extra_entry))
    && ($remaining->file_symbol_ids($extra_id) === []) && ($remaining->entry_symbol_id($extra_id) === 0)
    && (!in_array($number_id, $remaining->child_symbol_ids(0), true)),
    'Deleted file contributions disappear from all current indexes even during full selection');
$found = false;
foreach ($removed->symbols->changes as $change) {
    if ($change->previous?->symbol_id === $number_id) {
        $found = ($change->current === null) && ($change->previous === $old_symbol);
    }
}
Symbol_Collection_Test::check(($found) && ($old_tree->get() !== null), 'Removal catalog retains the exact old origin for its reader');
unset($change, $old_symbol, $removed);
Symbol_Collection_Test::check($old_tree->get() === null, 'Session keeps no history chain after catalog readers finish');
Symbol_Collection_Test::edit($extra, 'function number(): int { return 987; }');
$recreated = $session->compile($manifest);
Symbol_Collection_Test::check(($recreated->symbols->current->find_symbol('number', '', symbol_kind::function_symbol) >= $old_max)
    && ($recreated->inputs->sources->find_file_id($extra) !== $extra_id), 'Removed identities are never reused on recreation');
$fresh = (new \compile\Compiler_Session())->compile($manifest);
Symbol_Collection_Test::check(Symbol_Collection_Test::normalized($recreated->symbols->current) === Symbol_Collection_Test::normalized($fresh->symbols->current),
    'Current declaration facts match a fresh build, independently of historical IDs');

// Full selection recollects through the same workers while keeping logical IDs.
$full_tasks = \Step_Test::select(Collector::class, $recreated->symbols->current, $recreated->inputs->sources, $recreated->inputs->frontends, true);
Symbol_Collection_Test::check(count($full_tasks) === 4, 'Full mode selects every current file');
$full = \Step_Test::run(new Collector($recreated->symbols->current, $recreated->inputs->sources, $recreated->inputs->frontends, true));
Symbol_Collection_Test::check($full->current->to_json() === $recreated->symbols->current->to_json(),
    'Full collection preserves live identities and declaration facts');
$exhausted = new Symbol_Store(\collect_symbols\MAX_SYMBOL_ID + 1);
Symbol_Collection_Test::rejects(static fn() => \Step_Test::run(new Collector($exhausted, $first->inputs->sources, $first->inputs->frontends, true)), 'Symbol ID space exhausted');
Symbol_Collection_Test::check(($exhausted->records() === []) && (serialize($first) === $before),
    'ID exhaustion and subsequent refreshes leave retained state intact');

// Exercise declaration loops and indexes beyond a tiny fixture, including
// distinct exact spellings and independent implicit-entry storage.
$bulk = 'function Distinct(): int { return 0; } function distinct(): int { return 0; }';
for ($i = 0; $i < 2000; $i++) {
    $bulk .= 'function item_' . $i . '(): int { return ' . $i . '; }';
}
Symbol_Collection_Test::edit($path, $bulk);
$large = $session->compile($manifest);
Symbol_Collection_Test::check((count($large->symbols->current->file_symbol_ids($file_id)) === 2003)
    && ($large->symbols->current->find_symbol('Distinct', '', symbol_kind::function_symbol)
        !== $large->symbols->current->find_symbol('distinct', '', symbol_kind::function_symbol))
    && ($large->symbols->current->find_symbol('item_1999', '', symbol_kind::function_symbol) !== 0),
    'Large file collection indexes every declaration with exact identifier spelling');
Symbol_Collection_Test::check(\Step_Test::select(Collector::class, $large->symbols->current, $large->inputs->sources,
        $large->inputs->frontends, false) === [], 'Large unchanged file requires no repeated extraction');

echo "symbol collection ok: cross-file lookup, implicit entries, independent workers, stable identities, uncompared matches, duplicates, rollback, removals/renames, origin retention, fresh equivalence and ID bounds\n";
