<?php
declare(strict_types=1);

require_once __DIR__ . '/../../support/bootstrap.php';

use collect_symbols\Declaration_Collector;
use collect_symbols\Declaration_Syntax;
use collect_symbols\Symbol_Comparer;
use collect_symbols\change_status;
use collect_symbols\symbol_kind;
use parse\Syntax_Comparer;

class Symbol_Comparison_Test
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
        catch (Exception $error) {
            self::check(str_contains($error->getMessage(), $reason), $error->getMessage());
            return;
        }
        throw new Exception('Expected rejection: ' . $reason);
    }

    public static function edit(string $path, string $text): void
    {
        if (str_ends_with($path, '/src/nested/value.phs')) {
            $text .= "\nreturn 0;";
        }
        clearstatcache(true, $path);
        $mtime = is_file($path) ? filemtime($path) : time();
        file_put_contents($path, $text);
        touch($path, $mtime + 2);
        clearstatcache(true, $path);
    }

    public static function expect(\compile\Compile_Result $result, array $expected): void
    {
        $actual = [];
        foreach ($result->symbols->changes as $change) {
            $id = ($change->current ?? $change->previous)->symbol_id;
            $actual[$id] = [$change->own_status, $change->children_changed];
        }
        ksort($actual);
        ksort($expected);
        self::check($actual === $expected, 'Catalog must contain exactly the expected own/child changes: ' . json_encode($actual));
        self::check((!$result->completed) && ($result->stopped_before === 'build_native'), 'Syntax comparison does not complete compilation');
    }

    public static function parse(string $text): \parse\File_Frontend
    {
        return (new \parse\File_Parser(\tokenize\File_Tokenizer::tokenize(new \read_sources\Source_Buffer(1, 'compare.phs', 0, $text))))->parse();
    }
}

$manifest = '../fixtures/three_files/project.json';
$value_path = realpath('../fixtures/three_files/src/nested/value.phs');
$configuration = json_decode(file_get_contents($manifest), true, 512, JSON_THROW_ON_ERROR);
$configuration['entry'] = 'src/nested/value.phs';
Symbol_Comparison_Test::edit($manifest, json_encode($configuration, JSON_THROW_ON_ERROR));
Symbol_Comparison_Test::edit('../fixtures/three_files/src/main.phs', '');
$baseline_text = 'value(); function value(): int { return 10; } function spare(): int { return 20; }';
Symbol_Comparison_Test::edit($value_path, $baseline_text);
Symbol_Comparison_Test::edit('../fixtures/three_files/src/answer.phs', 'function answer(): int { value(); return 42; }');
$session = new \compile\Compiler_Session();
$first = $session->compile($manifest);
$before = serialize($first);
$old_store = $first->symbols->current;
$value_id = $old_store->find_symbol('value', '', symbol_kind::function_symbol);
$spare_id = $old_store->find_symbol('spare', '', symbol_kind::function_symbol);
$file_id = $first->inputs->sources->find_file_id($value_path);
$entry_id = $old_store->entry_symbol_id($file_id);
$again = $session->compile($manifest);
Symbol_Comparison_Test::expect($again, []);
Symbol_Comparison_Test::check(\Step_Test::select(Symbol_Comparer::class, $again->symbols, false) === [], 'Unchanged input needs no comparison task');

// Formatting and definition reordering change positions and node IDs, not the
// logical contents of either symbol or the entry's execution sequence.
$reordered_text = "/* moved */ function spare ( ) : int { return /* x */ 20 ; }\nvalue ( );\nfunction value(): int {\n return 10;\n}";
Symbol_Comparison_Test::edit($value_path, $reordered_text);
$reordered = $session->compile($manifest);
Symbol_Comparison_Test::expect($reordered, []);
$new_value = $reordered->symbols->current->symbol_by_id($value_id);
$old_value = $old_store->symbol_by_id($value_id);
Symbol_Comparison_Test::check(($new_value->declaration_node_id !== $old_value->declaration_node_id)
    && ($new_value->frontend !== $old_value->frontend)
    && ($reordered->resolutions->for_symbol($value_id) !== $first->resolutions->for_symbol($value_id))
    && (serialize($first) === $before),
    'Equal syntax retains current positions/AST bindings while leaving the previous snapshot intact');

Symbol_Comparison_Test::edit($value_path, str_replace('return 10;', 'return 11;', $reordered_text));
$literal = $session->compile($manifest);
Symbol_Comparison_Test::expect($literal, [$value_id => [change_status::unchanged, true]]);
Symbol_Comparison_Test::check(!$literal->inputs->context->full_rebuild, 'Comparison records facts without adding rebuild reactions');

// Name changes have the same node kind and text length; spelling still matters.
$text = 'value(); function value(): int { return spare(); } function spare(): int { return 20; }';
Symbol_Comparison_Test::edit($value_path, $text);
$call = $session->compile($manifest);
Symbol_Comparison_Test::expect($call, [$value_id => [change_status::unchanged, true]]);
$text = str_replace('return spare();', 'return value();', $text);
Symbol_Comparison_Test::edit($value_path, $text);
$callee = $session->compile($manifest);
Symbol_Comparison_Test::expect($callee, [$value_id => [change_status::unchanged, true]]);

$text = str_replace('value(): int', 'value(): uint32', $text);
Symbol_Comparison_Test::edit($value_path, $text);
$annotation = $session->compile($manifest);
Symbol_Comparison_Test::expect($annotation, [$value_id => [change_status::changed, false]]);
$text = str_replace(['value(): uint32', 'return value();'], ['value(): int', 'spare(); return 91;'], $text);
Symbol_Comparison_Test::edit($value_path, $text);
$both = $session->compile($manifest);
Symbol_Comparison_Test::expect($both, [$value_id => [change_status::changed, true]]);

$text = str_replace('value(); function', 'spare(); value(); function', $text);
Symbol_Comparison_Test::edit($value_path, $text);
$entry_add = $session->compile($manifest);
Symbol_Comparison_Test::expect($entry_add, [$entry_id => [change_status::unchanged, true]]);
$text = str_replace('spare(); value(); function', 'value(); spare(); function', $text);
Symbol_Comparison_Test::edit($value_path, $text);
$entry_order = $session->compile($manifest);
Symbol_Comparison_Test::expect($entry_order, [$entry_id => [change_status::unchanged, true]]);
$text = str_replace('value(); spare(); function', 'function', $text);
Symbol_Comparison_Test::edit($value_path, $text);
$entry_remove = $session->compile($manifest);
Symbol_Comparison_Test::expect($entry_remove, [$entry_id => [change_status::unchanged, true]]);

$text = str_replace('spare(); return 91;', 'return value();', $text);
Symbol_Comparison_Test::edit($value_path, $text);
$bare = $session->compile($manifest);
Symbol_Comparison_Test::expect($bare, [$value_id => [change_status::unchanged, true]]);
$text = str_replace('return value();', 'return 011;', $text);
Symbol_Comparison_Test::edit($value_path, $text);
$spelling = $session->compile($manifest);
Symbol_Comparison_Test::expect($spelling, [$value_id => [change_status::unchanged, true]]);
$text = str_replace('return 011;', 'return 11;', $text);
Symbol_Comparison_Test::edit($value_path, $text);
$spelling = $session->compile($manifest);
Symbol_Comparison_Test::expect($spelling, [$value_id => [change_status::unchanged, true]]);

$without_helper = $text;
Symbol_Comparison_Test::edit($value_path, $text . ' function helper(): int { return 1; }');
$added = $session->compile($manifest);
$helper_id = $added->symbols->current->find_symbol('helper', '', symbol_kind::function_symbol);
Symbol_Comparison_Test::expect($added, [$helper_id => [change_status::added, null]]);
Symbol_Comparison_Test::edit($value_path, $text . ' function renamed(): int { return 1; }');
$renamed = $session->compile($manifest);
$renamed_id = $renamed->symbols->current->find_symbol('renamed', '', symbol_kind::function_symbol);
Symbol_Comparison_Test::expect($renamed, [$helper_id => [change_status::removed, null], $renamed_id => [change_status::added, null]]);
Symbol_Comparison_Test::edit($value_path, $without_helper);
$removed = $session->compile($manifest);
Symbol_Comparison_Test::expect($removed, [$renamed_id => [change_status::removed, null]]);

// Raw collection still yields pending pairs; independent comparison workers
// describe them without mutating the catalog or its current store.
$raw = \Step_Test::run(new Declaration_Collector($old_store, $removed->inputs->sources, $removed->inputs->frontends, false));
$raw_before = serialize($raw);
$tasks = \Step_Test::select(Symbol_Comparer::class, $raw, false);
$results = [];
foreach (array_reverse($tasks) as $task) {
    $results[] = \collect_symbols\Comparison_Worker::compare($task);
}
$joined = (new \collect_symbols\Comparison_Join($raw, $tasks))->join($results);
$serial = \Step_Test::run(new Symbol_Comparer($raw, false));
Symbol_Comparison_Test::check(($joined->to_json() === $serial->to_json())
    && ($joined->current === $raw->current) && (serialize($raw) === $raw_before),
    'Comparison join is deterministic and leaves source records and input catalog unchanged');
Symbol_Comparison_Test::check((\Step_Test::select(Symbol_Comparer::class, $joined, false) === [])
    && (count(\Step_Test::select(Symbol_Comparer::class, $joined, true)) === count($joined->changes))
    && (\Step_Test::run(new Symbol_Comparer($joined, true))->to_json() === $joined->to_json()),
    'Completed comparisons are reusable; full selection uses the same worker');
Symbol_Comparison_Test::rejects(static fn() => (new \collect_symbols\Comparison_Join($raw, $tasks))->join([]), 'Incomplete');
Symbol_Comparison_Test::rejects(static fn() => (new \collect_symbols\Comparison_Join($raw, []))->join([]), 'Incomplete');
Symbol_Comparison_Test::rejects(static fn() => (new \collect_symbols\Comparison_Join($raw, $tasks))->join([...$results, $results[0]]), 'duplicate');
Symbol_Comparison_Test::rejects(static fn() => (new \collect_symbols\Comparison_Join($raw, [$tasks[0], $tasks[0]]))->join([]), 'duplicate');
$bad = new \collect_symbols\symbol_change(clone $results[0]->previous, $results[0]->current,
    $results[0]->own_status, $results[0]->children_changed);
Symbol_Comparison_Test::rejects(static fn() => (new \collect_symbols\Comparison_Join($raw, $tasks))->join([$bad]), 'stale');
Symbol_Comparison_Test::rejects(static fn() => (new \collect_symbols\Comparison_Join($raw, $tasks))->join([$tasks[0]]), 'stale');

// Initial additions and removals are passed through, not compared or copied.
$initial = \Step_Test::run(new Declaration_Collector(new \collect_symbols\Symbol_Store(), $removed->inputs->sources, $removed->inputs->frontends, true));
$initial_compared = \Step_Test::run(new Symbol_Comparer($initial, true));
Symbol_Comparison_Test::check((\Step_Test::select(Symbol_Comparer::class, $initial, true) === [])
    && ($initial_compared->changes[0] === $initial->changes[0]), 'Unmatched records have no comparison task');

// A full rebuild of identical source selects all frontends yet catalogs no change.
Symbol_Comparison_Test::edit($manifest, $removed->inputs->manifest->content . "\n");
$full = $session->compile($manifest);
Symbol_Comparison_Test::expect($full, []);
Symbol_Comparison_Test::check(($full->inputs->context->full_rebuild)
    && ($full->symbols->current->symbol_by_id($value_id)->frontend !== $removed->symbols->current->symbol_by_id($value_id)->frontend),
    'Full selection changes storage snapshots, not logical change facts');
$fresh = (new \compile\Compiler_Session())->compile($manifest);
Symbol_Comparison_Test::check($fresh->inputs->frontends->to_json() === $full->inputs->frontends->to_json(),
    'Comparison leaves the same AST output as a fresh compilation');

// Direct subtree checks: roots exclude their own siblings; optional children,
// node kinds, leaf contents, cardinality and large lists use the same comparator.
$left = Symbol_Comparison_Test::parse('return 1; function f(): int { return 2; }');
$right = Symbol_Comparison_Test::parse('return 1; function f(): int { return 3; }');
Symbol_Comparison_Test::check(Syntax_Comparer::equal($left, $left->entry_body_id, $right, $right->entry_body_id)
    && (!Syntax_Comparer::equal($left, $left->syntax->root_node_id, $right, $right->syntax->root_node_id)),
    'Entry comparison excludes definitions while complete file comparison includes them');
Symbol_Comparison_Test::check(Syntax_Comparer::equal($left, 0, $right, 0)
    && (!Syntax_Comparer::equal($left, 0, $right, $right->entry_body_id)), 'Optional subtree presence is meaningful');
$left = Symbol_Comparison_Test::parse(str_repeat('return 1;', 5000));
$right = Symbol_Comparison_Test::parse(str_repeat('return 1;', 4999) . 'return 2;');
Symbol_Comparison_Test::check(!Syntax_Comparer::equal($left, $left->entry_body_id, $right, $right->entry_body_id),
    'Iterative traversal reaches a difference at the end of a large statement list');
$left = Symbol_Comparison_Test::parse('return ' . str_repeat('9', 70000) . ';');
$right = Symbol_Comparison_Test::parse('return ' . str_repeat('9', 69999) . '8;');
Symbol_Comparison_Test::check(!Syntax_Comparer::equal($left, $left->entry_body_id, $right, $right->entry_body_id),
    'Literal comparison uses complete spelling, not host integer conversion');

// A formatting-only update keeps no redundant change records retaining old ASTs.
$isolated = new \compile\Compiler_Session();
$old = $isolated->compile($manifest);
$old_ast = WeakReference::create($old->inputs->frontends->for_file($file_id)->syntax);
$old_symbol = WeakReference::create($old->symbols->current->symbol_by_id($value_id));
unset($old);
Symbol_Comparison_Test::edit($value_path, '// formatting only' . "\n" . $without_helper);
$equal = $isolated->compile($manifest);
Symbol_Comparison_Test::expect($equal, []);
Symbol_Comparison_Test::check(($old_ast->get() === null) && ($old_symbol->get() === null),
    'Omitted equality rows release previous references once no reader needs them');
echo "symbol comparison ok: definition/child distinctions, logical subtree equality, positions and IDs, sparse catalogs, pure workers, join validation, full rebuild, large bodies and old-snapshot release\n";
