<?php
declare(strict_types=1);
require_once __DIR__ . '/../../support/bootstrap.php';

use lower\Lowerer;
use lower\Lowered_Set;
use lower\lowering_input;
use lower\instruction_kind;
use collect_symbols\symbol_kind;

class Lowering_Test
{
    public static function check(bool $ok, string $message): void
    {
        if (!$ok) {
            throw new Exception($message);
        }
    }

    /** Require a rejected operation to carry the expected diagnostic. */
    public static function rejects(callable $action, string $message): void
    {
        try {
            $action();
        }
        catch (Throwable $error) {
            self::check(str_contains($error->getMessage(), $message), $error->getMessage());
            return;
        }
        throw new Exception('Expected rejection: ' . $message);
    }

    public static function edit(string $path, string $text): void
    {
        clearstatcache(true, $path);
        $mtime = filemtime($path);
        file_put_contents($path, $text);
        touch($path, $mtime + 2);
        clearstatcache(true, $path);
    }

    public static function baseline(\compile\Compiler_Session $session): array
    {
        return [$session->observed];
    }

    // Test-only interpreter of the lowered plan. It never reads source/typed ASTs
    // or bypasses the compiler; this verifies instruction composition, not LLVM.
    /** Interpret the lowered test plan and record nested calls without reading source syntax. */
    public static function evaluate(Lowered_Set $program, int $symbol, array &$trace): ?string
    {
        if (count($trace) > 100) {
            throw new Exception('Unexpected unbounded call trace');
        }
        $trace[] = $symbol;
        $body = $program->for_callable($symbol);
        $block = $body->blocks[$body->entry_block_id - 1];
        $values = [];
        for ($index = $block->instruction_start; $index < ($block->instruction_start + $block->instruction_count); ++$index)
        {
            $instruction = $body->instructions[$index];
            $result = match ($instruction->kind) {
                instruction_kind::constant => $instruction->payload,
                instruction_kind::call => self::evaluate($program, $instruction->payload->target->callable_id, $trace),
            };
            if ($instruction->result_value_id !== 0) {
                $values[$instruction->result_value_id] = $result;
            }
        }
        return $block->terminator->value_id === 0 ? null : $values[$block->terminator->value_id];
    }

    /** Collect stable semantic facts for comparison across compiler snapshots. */
    public static function facts(\compile\Compile_Result $result): array
    {
        $facts = [];
        foreach ($result->lowered->bodies() as $body)
        {
            $values = [];
            foreach ($body->values as $value) {
                $values[] = [$value->source_value_id, $body->definition_for($value->type_id)];
            }
            $instructions = [];
            foreach ($body->instructions as $instruction) {
                $payload = is_string($instruction->payload) ? $instruction->payload
                    : $result->symbols->current->symbol_by_id($instruction->payload->target->callable_id)->name;
                $instructions[] = [$instruction->kind, $instruction->source_node_id, $instruction->result_value_id, $payload];
            }
            $facts[$result->symbols->current->symbol_by_id($body->binding->callable_id)->name] = [$values, $instructions, $body->blocks];
        }
        ksort($facts);
        return $facts;
    }
}

$manifest = '../fixtures/three_files/project.json';
$root = realpath(dirname($manifest));
$main = $root . '/src/main.phs';
$value_path = $root . '/src/nested/value.phs';
$main_source = 'warmup(); empty_call(); return answer(); value();
function warmup(): void { value(); return; answer(); }
function empty_call(): void {}
function spare(): int { return 8; }';
Lowering_Test::edit($main, $main_source);
$target_path = getcwd() . '/backend.json';
file_put_contents($target_path, json_encode(['clang' => 'clang', 'target' => null], JSON_THROW_ON_ERROR));
$catalog_path = getcwd() . '/types.json';
$catalog = file_get_contents(dirname(__DIR__, 3) . '/language/named_types.json');
file_put_contents($catalog_path, $catalog);
$session = new \compile\Compiler_Session(type_catalog_path: $catalog_path, backend_toolchain_path: $target_path);
$first = $session->compile($manifest);
$entry = $first->types->entry->symbol->symbol_id;
$value = $first->symbols->current->find_symbol('value', '', symbol_kind::function_symbol);
$answer = $first->symbols->current->find_symbol('answer', '', symbol_kind::function_symbol);
$warmup = $first->symbols->current->find_symbol('warmup', '', symbol_kind::function_symbol);
$empty = $first->symbols->current->find_symbol('empty_call', '', symbol_kind::function_symbol);
$spare = $first->symbols->current->find_symbol('spare', '', symbol_kind::function_symbol);
$body = $first->lowered->for_symbol($value);
Lowering_Test::check(($body->entry_block_id === 1) && (count($body->blocks) === 1)
    && ($body->blocks[0]->instruction_start === 0) && ($body->blocks[0]->instruction_count === 1)
    && ($body->instructions[0]->kind === instruction_kind::constant) && ($body->instructions[0]->payload === '42')
    && ($body->blocks[0]->terminator->value_id === 1) && ($body->values[0]->source_value_id === 1)
    && ($body->definition_for($body->values[0]->type_id) === $body->binding->return_definition),
    'A real literal return lowers to an exact constant and a separate block terminator with shared type facts');
$entry_body = $first->lowered->for_symbol($entry);
Lowering_Test::check((count($entry_body->instructions) === 3) && (count($entry_body->values) === 1)
    && (array_column($entry_body->instructions, 'result_value_id') === [0, 0, 1])
    && ($entry_body->instructions[2]->payload->target === $first->backend->binding_for($answer))
    && ($entry_body->blocks[0]->terminator->value_id === 1),
    'Void calls remain instructions without values; returning a call result does not execute it twice');
Lowering_Test::check((count($first->lowered->for_symbol($warmup)->instructions) === 1)
    && (count($first->lowered->for_symbol($warmup)->values) === 1)
    && ($first->lowered->for_symbol($warmup)->blocks[0]->terminator->value_id === 0)
    && ($first->lowered->for_symbol($empty)->instructions === [])
    && ($first->lowered->for_symbol($empty)->blocks[0]->terminator->source_node_id === $first->bodies->for_symbol($empty)->owner->body_node_id),
    'Discarded results preserve calls, unreachable tails disappear, and empty void fallthrough becomes a real return');
$trace = [];
Lowering_Test::check((Lowering_Test::evaluate($first->lowered, $entry, $trace) === '42')
    && ($trace === [$entry, $warmup, $value, $empty, $answer, $value]), 'The composed lowered plan preserves call order and returns the source result');
Lowering_Test::check((!$first->completed) && ($first->stopped_before === 'build_native') && ($session->generation === 0),
    'Lowering is real progress but not LLVM emission or a published executable');
$before = serialize($first);
$warm = $session->compile($manifest);
Lowering_Test::check((\Step_Test::select(Lowerer::class, $warm->lifetimes, $warm->backend, $first->lowered, false) === [])
    && ($warm->lowered->for_symbol($entry) === $entry_body), 'Warm runs select no lowering work and share results');
$tasks = \Step_Test::select(Lowerer::class, $first->lifetimes, $first->backend, new Lowered_Set(), true);
$results = [];
foreach (array_reverse($tasks) as $task) {
    $results[] = (new \lower\Lowering_Worker($task))->lower();
}
$joined = (new \lower\Lowering_Join($first->lifetimes, $first->backend, new Lowered_Set(), $tasks))->join($results);
Lowering_Test::check(($joined->to_json() === $first->lowered->to_json()) && (serialize($first) === $before),
    'Reversed workers produce deterministic plans without modifying shared inputs');
Lowering_Test::rejects(static fn() => (new \lower\Lowering_Join($first->lifetimes, $first->backend, new Lowered_Set(), $tasks))->join([]), 'Incomplete');
Lowering_Test::rejects(static fn() => (new \lower\Lowering_Join($first->lifetimes, $first->backend, new Lowered_Set(), $tasks))->join([...$results, $results[0]]), 'duplicate');
Lowering_Test::rejects(static fn() => (new \lower\Lowering_Join($first->lifetimes, $first->backend, new Lowered_Set(), [$tasks[0], $tasks[0]]))->join([]), 'Duplicate');
Lowering_Test::check((new \lower\Lowering_Join(new \analyze_lifetimes\Lifetime_Set(), $first->backend, $first->lowered, []))->join([])->bodies() === [],
    'Removed analyses cannot leave stale lowered functions');
Lowering_Test::edit($value_path, 'function value(): int { return 43; }');
$edited = $session->compile($manifest);
$selected = \Step_Test::select(Lowerer::class, $edited->lifetimes, $edited->backend, $first->lowered, false);
Lowering_Test::check((count($selected) === 1) && ($selected[0]->analysis === $edited->lifetimes->for_symbol($value))
    && ($edited->lowered->for_symbol($entry) === $entry_body) && ($edited->lowered->for_symbol($answer) === $first->lowered->for_symbol($answer))
    && (serialize($first) === $before), 'A body-only edit replaces only its lowered result under unchanged contracts');
$trace = [];
Lowering_Test::check(Lowering_Test::evaluate($edited->lowered, $entry, $trace) === '43', 'An unchanged lowered caller observes the replaced callee plan');
Lowering_Test::rejects(static fn() => (new \lower\Lowering_Join($edited->lifetimes, $edited->backend, $first->lowered, $selected))->join([$body]), 'stale');
Lowering_Test::rejects(static fn() => (new \lower\Lowering_Join($edited->lifetimes, $edited->backend, $first->lowered, [$first->lowering_input_for($value)]))->join([]), 'stale');

$baseline = Lowering_Test::baseline($session);
Lowering_Test::edit($value_path, 'function value(): uint32 { return 44; }');
Lowering_Test::rejects(static fn() => $session->compile($manifest), 'Unsupported implicit return conversion');
Lowering_Test::check(Lowering_Test::baseline($session) === $baseline, 'Failure keeps every accepted result, including lowered plans');
Lowering_Test::edit($value_path, 'function value(): int { return 44; }');
$repair = $session->compile($manifest);
$analysis = $repair->lifetimes->for_symbol($value);
$invalid = new \analyze_lifetimes\Analyzed_Body($analysis->body, [], $analysis->reachable_statement_count, $analysis->falls_through);
Lowering_Test::rejects(static fn() => (new \lower\Lowering_Worker(new lowering_input($invalid, $repair->backend)))->lower(), 'analyzed lifetime');
Lowering_Test::rejects(static fn() => (new \lower\Lowering_Worker(new lowering_input($analysis, new \prepare_backend\Backend_Context())))->lower(), 'not configured');
Lowering_Test::check(serialize($first) === $before, 'Invalid lowering inputs cannot mutate retained outputs');
file_put_contents($target_path, json_encode(['clang' => 'clang', 'target' => $first->backend->configuration->target_triple], JSON_THROW_ON_ERROR));
$target_edit = $session->compile($manifest);
Lowering_Test::check(($target_edit->lifetimes->for_symbol($entry) === $repair->lifetimes->for_symbol($entry))
    && (count(\Step_Test::select(Lowerer::class, $target_edit->lifetimes, $target_edit->backend, $repair->lowered, false)) === count($target_edit->lifetimes->bodies()))
    && ($target_edit->lowered->for_symbol($entry) !== $entry_body), 'Backend-only changes invalidate all lowered plans without redoing semantic analysis');
Lowering_Test::rejects(static fn() => (new \lower\Lowering_Join($target_edit->lifetimes, $target_edit->backend, $repair->lowered, [$repair->lowering_input_for($entry)]))->join([]), 'stale');

// Provider-defined names and widths traverse exactly the same constant/call path.
$data = json_decode($catalog, true, 512, JSON_THROW_ON_ERROR);
foreach ($data['types'] as &$definition) {
    if ($definition['name'] === 'int') {
        $definition['name'] = 'Counter';
        $definition['bit_width'] = 17;
    }
}
unset($definition);
$data['literal_types']['integer']['name'] = 'Counter';
$data['entry_return_type']['name'] = 'Counter';
file_put_contents($catalog_path, json_encode($data, JSON_THROW_ON_ERROR));
Lowering_Test::edit($main, str_replace(': int', ': Counter', $main_source));
Lowering_Test::edit($root . '/src/answer.phs', 'function answer(): Counter { return value(); }');
Lowering_Test::edit($value_path, 'function value(): Counter { return 00044; }');
$counter = $session->compile($manifest);
Lowering_Test::check(($counter->inputs->context->full_rebuild)
    && ($counter->lowered->for_symbol($value)->instructions[0]->payload === '44')
    && ($counter->lowered->for_symbol($value)->binding->return_definition->representation->payload->bit_width === 17),
    'Full selection lowers provider-defined scalar contracts without name/width special cases');
Lowering_Test::edit($main, str_replace('function spare(): Counter { return 8; }', '', file_get_contents($main)));

// Remaining main declarations are void; removal should retire spare everywhere.
$removed = $session->compile($manifest);
Lowering_Test::check($removed->lowered->for_symbol($spare) === null, 'Function removal retires its lowered body');
Lowering_Test::edit($value_path, 'function value(): Counter { ' . str_repeat('value();', 1000) . 'return 44; ' . str_repeat('value();', 300) . '}');
$large = $session->compile($manifest);
Lowering_Test::check((count($large->lowered->for_symbol($value)->instructions) === 1001)
    && (count($large->lowered->for_symbol($value)->values) === 1001)
    && ($large->lowered->for_symbol($value)->blocks[0]->terminator->value_id === 1001),
    'Large flat bodies produce linear instruction/value datasets and exclude unreachable calls');
$fresh = (new \compile\Compiler_Session(type_catalog_path: $catalog_path, backend_toolchain_path: $target_path))->compile($manifest);
Lowering_Test::check(Lowering_Test::facts($large) == Lowering_Test::facts($fresh), 'Fresh and incremental lowered plans are semantically equivalent');
$export = json_decode($large->to_json(), true, 512, JSON_THROW_ON_ERROR);
Lowering_Test::check((count($export['lowered']) === count($export['lifetimes'])) && ($export['stopped_before'] === 'build_native')
    && (isset($export['lowered'][0]['blocks'][0]['terminator'])), 'Debug output exports actual blocks, instructions, values and terminators');
echo "lowering ok: real plans and composed evaluation, call order, constants, void/return flow, lifetimes, fixed workers, reuse, failure/repair, backend invalidation, removals and exports\n";
