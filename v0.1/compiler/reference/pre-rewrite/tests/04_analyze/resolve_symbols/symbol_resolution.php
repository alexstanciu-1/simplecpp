<?php
declare(strict_types=1);

require_once __DIR__ . '/../../support/bootstrap.php';

use collect_symbols\symbol_kind;
use resolve_symbols\Resolution_Set;
use resolve_symbols\Symbol_Resolver as Resolver;

class Symbol_Resolution_Test
{
    public static function check(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new Exception($message);
        }
    }

    /** Require a rejected operation with the expected diagnostic. */
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
        touch($path, $mtime + 2);
        clearstatcache(true, $path);
    }

    /** Collect resolved target names for order-sensitive assertions. */
    public static function calls(\compile\Compile_Result $result, int $owner): array
    {
        $resolution = $result->resolutions->for_symbol($owner);
        $symbol = $result->symbols->current->symbol_by_id($owner);
        self::check(($resolution !== null) && ($resolution->syntax === $symbol->frontend->syntax),
            'Every callable has a result anchored in its exact current AST');
        $calls = [];
        foreach ($resolution->bindings as $binding)
        {
            $name = $resolution->syntax->nodes[$binding->use_node_id - 1];
            $spelling = substr($symbol->frontend->tokens->source->content, $name->start, $name->length);
            $target = $result->symbols->current->symbol_by_id($binding->target_symbol_id);
            self::check(($name->kind === \parse\syntax_kind::name) && ($target->name === $spelling),
                'Each call name binds to the actual target declaration');
            $calls[] = $target->name;
        }
        return $calls;
    }

    /** Export stable semantic facts without relying on source offsets. */
    public static function facts(\compile\Compile_Result $result): array
    {
        $facts = [];
        foreach ($result->symbols->current->records() as $owner) {
            $key = $owner->frontend->tokens->source->path . ':' . $owner->kind->name . ':' . $owner->name;
            $facts[$key] = self::calls($result, $owner->symbol_id);
        }
        ksort($facts);
        return $facts;
    }
}

$manifest = '../fixtures/three_files/project.json';
Symbol_Resolution_Test::edit('../fixtures/three_files/src/answer.phs', 'function answer(): int { value(); return 42; }');
$session = new \compile\Compiler_Session();
$first = $session->compile($manifest);
$store = $first->symbols->current;
$before = serialize($first);
$answer_id = $store->find_symbol('answer', '', symbol_kind::function_symbol);
$value_id = $store->find_symbol('value', '', symbol_kind::function_symbol);
$root = $first->inputs->manifest->directory;
$main_path = $root . '/src/main.phs';
$answer_path = $root . '/src/answer.phs';
$value_path = $root . '/src/nested/value.phs';
$main_file = $first->inputs->sources->find_file_id($main_path);
$answer_file = $first->inputs->sources->find_file_id($answer_path);
$value_file = $first->inputs->sources->find_file_id($value_path);
$main_id = $store->entry_symbol_id($main_file);
Symbol_Resolution_Test::check((!$first->completed) && ($first->stopped_before === 'build_native')
    && ($session->generation === 0) && ($session->observed?->resolutions === $first->resolutions)
    && ($session->published === null), 'Name resolution does not publish completed compilation');
Symbol_Resolution_Test::check((Symbol_Resolution_Test::calls($first, $main_id) === ['answer'])
    && (Symbol_Resolution_Test::calls($first, $answer_id) === ['value'])
    && (Symbol_Resolution_Test::calls($first, $value_id) === []), 'Resolve the real three-file call chain');
Symbol_Resolution_Test::check((Symbol_Resolution_Test::calls($first, $store->entry_symbol_id($answer_file)) === [])
    && (Symbol_Resolution_Test::calls($first, $store->entry_symbol_id($value_file)) === []),
    'Entry traversal must not enter neighboring function definitions');
$export = json_decode($first->to_json(), true, 512, JSON_THROW_ON_ERROR);
Symbol_Resolution_Test::check((count($export['resolutions']) === 5)
    && (array_sum(array_map(static fn($row) => count($row['bindings']), $export['resolutions'])) === 2),
    'Debug export contains one result per callable and actual call bindings only');

$again = $session->compile($manifest);
Symbol_Resolution_Test::check(\Step_Test::select(\resolve_symbols\Symbol_Resolver::class, $again->symbols->current, $again->resolutions, false, $first->types->catalog) === [],
    'Unchanged ASTs and lookup mappings need no resolution work');
foreach ($store->records() as $symbol) {
    Symbol_Resolution_Test::check($first->resolutions->for_symbol($symbol->symbol_id) === $again->resolutions->for_symbol($symbol->symbol_id),
        'Reuse unchanged resolution objects without copying binding rows');
}

$empty = new Resolution_Set();
$tasks = \Step_Test::select(\resolve_symbols\Symbol_Resolver::class, $store, $empty, false, $first->types->catalog);
Symbol_Resolution_Test::check((count($tasks) === 5) && (count(\Step_Test::select(\resolve_symbols\Symbol_Resolver::class, $store, $first->resolutions, true, $first->types->catalog)) === 5),
    'Missing results and full rebuild select the same callable tasks');
$results = [];
foreach (array_reverse($tasks) as $task) {
    $results[] = (new \resolve_symbols\Resolution_Worker($store, $task, $first->types->catalog))->run();
}
$joined = (new \resolve_symbols\Resolution_Join($empty, $store, $tasks, $first->types->catalog))->join($results);
Symbol_Resolution_Test::check(($joined->to_json() === $first->resolutions->to_json()) && (serialize($first) === $before),
    'Independent reversed workers and deterministic join preserve exports and phase inputs');
Symbol_Resolution_Test::rejects(static fn() => (new \resolve_symbols\Resolution_Join($empty, $store, $tasks, $first->types->catalog))->join([]), 'Incomplete');
Symbol_Resolution_Test::rejects(static fn() => (new \resolve_symbols\Resolution_Join($empty, $store, $tasks, $first->types->catalog))->join([...$results, $results[0]]), 'duplicate');
Symbol_Resolution_Test::rejects(static fn() => (new \resolve_symbols\Resolution_Join($empty, $store, [], $first->types->catalog))->join($results), 'Unexpected');
Symbol_Resolution_Test::rejects(static fn() => (new \resolve_symbols\Resolution_Join($empty, $store, [], $first->types->catalog))->join([]), 'Incomplete');
Symbol_Resolution_Test::rejects(static fn() => (new \resolve_symbols\Resolution_Join($empty, $store, [$tasks[0], $tasks[0]], $first->types->catalog))->join([]), 'Duplicate');
$original_resolution = $first->resolutions->for_symbol($answer_id);
$use = $original_resolution->bindings[0]->use_node_id;
$bad = new \resolve_symbols\Symbol_Resolution($answer_id, $original_resolution->syntax,
    [new \resolve_symbols\symbol_binding($use, $answer_id)], $original_resolution->scopes);
Symbol_Resolution_Test::rejects(static fn() => (new \resolve_symbols\Resolution_Join($first->resolutions, $store, [$store->symbol_by_id($answer_id)], $first->types->catalog))->join([$bad]), 'stale');
Symbol_Resolution_Test::rejects(static fn() => new \resolve_symbols\Symbol_Resolution($answer_id, $original_resolution->syntax,
        [$original_resolution->bindings[0], $original_resolution->bindings[0]]), 'duplicate');
Symbol_Resolution_Test::check($original_resolution->target_for($use) === $value_id, 'Resolved lookup uses the shared result owner');
Symbol_Resolution_Test::rejects(static fn() => $original_resolution->target_for(0), 'Missing resolved');

// A supported body edit refreshes that file while retaining the caller's bindings.
Symbol_Resolution_Test::edit($value_path, 'function value(): int { return value(); }');
$edited = $session->compile($manifest);
Symbol_Resolution_Test::check(($edited->resolutions->for_symbol($answer_id) === $first->resolutions->for_symbol($answer_id))
    && ($edited->resolutions->for_symbol($value_id) !== $first->resolutions->for_symbol($value_id)),
    'Name binding reuse is independent of separate body/type contract checking');
$new_tasks = \Step_Test::select(\resolve_symbols\Symbol_Resolver::class, $edited->symbols->current, $first->resolutions, false, $first->types->catalog);
Symbol_Resolution_Test::check(count($new_tasks) === 2, 'Every callable in a replaced file AST gets fresh bindings');
Symbol_Resolution_Test::rejects(static fn() => (new \resolve_symbols\Resolution_Worker($edited->symbols->current, $store->symbol_by_id($value_id), $first->types->catalog))->run(), 'Stale');
Symbol_Resolution_Test::rejects(static fn() => (new \resolve_symbols\Resolution_Join($first->resolutions, $edited->symbols->current, [$edited->symbols->current->symbol_by_id($value_id)], $first->types->catalog))->join([$first->resolutions->for_symbol($value_id)]), 'stale');
Symbol_Resolution_Test::rejects(static fn() => (new \resolve_symbols\Resolution_Join($first->resolutions, $edited->symbols->current, [], $first->types->catalog))->join([]), 'stale');

// Recursive/forward calls are lookup dependencies, not a reason to wait for a callee's body.
Symbol_Resolution_Test::edit($answer_path, 'function answer(): int { value(); return answer(); }');
Symbol_Resolution_Test::edit($value_path, 'function value(): int { return answer(); }');
Symbol_Resolution_Test::edit($main_path, 'value(); return answer();');
$recursive = $session->compile($manifest);
Symbol_Resolution_Test::check((Symbol_Resolution_Test::calls($recursive, $answer_id) === ['value', 'answer'])
    && (Symbol_Resolution_Test::calls($recursive, $value_id) === ['answer'])
    && (Symbol_Resolution_Test::calls($recursive, $main_id) === ['value', 'answer']),
    'Self recursion, mutual recursion and forward entry calls bind in source order');

Symbol_Resolution_Test::edit($main_path, 'return answer();');
$retained = [$session->observed?->inputs, $session->observed?->symbols, $session->observed?->resolutions];
$retained_dump = serialize($retained);
Symbol_Resolution_Test::edit($value_path, 'function renamed(): int { return 43; }');
$error = Symbol_Resolution_Test::rejects(static fn() => $session->compile($manifest), "Unknown function 'value'");
Symbol_Resolution_Test::check(($error instanceof \diagnostics\Source_Error) && ($error->path === $answer_path)
    && ($error->length === strlen('value')), 'A changed project index invalidates an unchanged caller and anchors its error');
Symbol_Resolution_Test::check(([$session->observed?->inputs, $session->observed?->symbols, $session->observed?->resolutions] === $retained)
    && (serialize($retained) === $retained_dump), 'Failed resolution preserves every accepted phase result');
unlink($value_path);
Symbol_Resolution_Test::rejects(static fn() => $session->compile($manifest), "Unknown function 'value'");
Symbol_Resolution_Test::edit($value_path, 'function renamed(): int { return 43; }');
Symbol_Resolution_Test::edit($answer_path, 'function answer(): int { return renamed(); }');
$repaired = $session->compile($manifest);
Symbol_Resolution_Test::check((Symbol_Resolution_Test::calls($repaired, $answer_id) === ['renamed'])
    && ($repaired->resolutions->for_symbol($value_id) === null), 'Repair replaces bindings and excludes removed owners');

Symbol_Resolution_Test::edit($main_path, 'return absent();');
$error = Symbol_Resolution_Test::rejects(static fn() => $session->compile($manifest), "Unknown function 'absent'");
Symbol_Resolution_Test::check(($error->start === 7) && ($error->length === 6) && ($error->path === $main_path),
    'Unknown top-level call has an exact name span');
Symbol_Resolution_Test::edit($main_path, 'return answer();');
$extra_path = $root . '/src/extra.phs';
Symbol_Resolution_Test::edit($extra_path, 'function unused(): int { return absent(); }');
Symbol_Resolution_Test::rejects(static fn() => $session->compile($manifest), "Unknown function 'absent'");
Symbol_Resolution_Test::edit($extra_path, 'function forward(): int { return later(); } function later(): int { return forward(); }');
$extra = $session->compile($manifest);
$forward = $extra->symbols->current->find_symbol('forward', '', symbol_kind::function_symbol);
$later = $extra->symbols->current->find_symbol('later', '', symbol_kind::function_symbol);
Symbol_Resolution_Test::check((Symbol_Resolution_Test::calls($extra, $forward) === ['later'])
    && (Symbol_Resolution_Test::calls($extra, $later) === ['forward']), 'Unused and later-defined functions are resolved too');
$extra_file = $extra->inputs->sources->find_file_id($extra_path);
$extra_entry = $extra->symbols->current->entry_symbol_id($extra_file);
$obsolete = WeakReference::create($extra->resolutions->for_symbol($forward));
unset($extra);
unlink($extra_path);
$removed = $session->compile($manifest);
Symbol_Resolution_Test::check(($removed->resolutions->for_symbol($forward) === null)
    && ($removed->resolutions->for_symbol($later) === null) && ($removed->resolutions->for_symbol($extra_entry) === null)
    && ($obsolete->get() === null), 'Deleted functions and file entries leave the project result set without retaining old bindings');
$fresh = (new \compile\Compiler_Session())->compile($manifest);
Symbol_Resolution_Test::check(Symbol_Resolution_Test::facts($removed) === Symbol_Resolution_Test::facts($fresh),
    'Repeated updates produce the same bindings as a fresh build, independent of historical IDs');

// Full rebuild executes the same workers and leaves old inputs/results untouched.
Symbol_Resolution_Test::edit($manifest, $removed->inputs->manifest->content . "\n");
$full = $session->compile($manifest);
foreach ($full->symbols->current->records() as $symbol) {
    Symbol_Resolution_Test::check($full->resolutions->for_symbol($symbol->symbol_id) !== $removed->resolutions->for_symbol($symbol->symbol_id),
        'Full rebuild replaces every callable result through the common path');
}
Symbol_Resolution_Test::check((Symbol_Resolution_Test::facts($full) === Symbol_Resolution_Test::facts($fresh))
    && (serialize($first) === $before), 'Full/selective facts agree and baseline snapshots stay intact');

Symbol_Resolution_Test::edit($main_path, str_repeat('answer();', 5000) . 'return 0;');
$large = $session->compile($manifest);
Symbol_Resolution_Test::check(count($large->resolutions->for_symbol($main_id)->bindings) === 5000,
    'Iterative body traversal handles large statement lists');
echo "symbol resolution ok: project calls, recursion, body boundaries, independent workers, reuse, stale binding rejection, unknown-name rollback, deletions, fresh equivalence and large bodies\n";
