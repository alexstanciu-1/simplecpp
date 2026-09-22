<?php
declare(strict_types=1);
require_once __DIR__ . '/../support/body_support.php';

use lower\Lowerer;
use lower\Lowered_Set;
use lower\instruction_kind;
use emit_llvm\LLVM_Emitter;

class Local_Lowering_Test extends Body_Test_Stages
{
    /** Run the native local-variable proof with a deadline and release its process. */
    public static function run(string $path): int
    {
        $process = proc_open([$path], [0 => ['file', '/dev/null', 'r'], 1 => ['file', '/dev/null', 'w'],
                2 => ['file', '/dev/null', 'w']], $pipes);
        self::check(is_resource($process), 'Start native local-variable program');
        try
        {
            $deadline = hrtime(true) + 2_000_000_000;
            do
            {
                $status = proc_get_status($process);
                if (!$status['running']) {
                    return $status['exitcode'];
                }
                self::check(hrtime(true) < $deadline, 'Local-variable program timed out');
                usleep(10000);
            }
            while (true);
        }
        finally {
            if (proc_get_status($process)['running']) {
                proc_terminate($process, 9);
            }
            proc_close($process);
        }
    }
}

$manifest = '../fixtures/three_files/project.json';
$root = realpath(dirname($manifest));
$main = $root . '/src/main.phs';
$value_path = $root . '/src/nested/value.phs';
$output = getcwd() . '/locals';
$catalog_path = getcwd() . '/types.json';
$catalog = json_decode(file_get_contents(dirname(__DIR__, 2) . '/language/named_types.json'), true, 512, JSON_THROW_ON_ERROR);
file_put_contents($catalog_path, json_encode($catalog, JSON_THROW_ON_ERROR));
Local_Lowering_Test::edit($main, '$result int = answer(); return $result;');
$source = 'function value(): int {
    $outer int = 41;
    { $outer = 42; $outer int = 9; $outer = $outer; }
    $copy int = $outer;
    $outer = 7;
    { $nested int = $copy; return $nested; $dead int = value(); }
    return 99;
}';
Local_Lowering_Test::edit($value_path, $source);
$session = new \compile\Compiler_Session(type_catalog_path: $catalog_path);
$first = $session->compile($manifest, $output);
Local_Lowering_Test::check(($first->completed) && (Local_Lowering_Test::run($output) === 42),
    'Three-file native calls, outer assignment, shadowing, self-assignment, independent value copy and early return');
$value_id = $first->symbols->current->find_symbol('value', '', \collect_symbols\symbol_kind::function_symbol);
$entry_id = $first->types->entry->symbol->symbol_id;
$body = $first->lowered->for_symbol($value_id);
Local_Lowering_Test::check((array_column($body->slots, 'source_local_id') === [1, 2, 3, 4])
    && (count(array_filter($body->instructions, static fn($i) => $i->kind === instruction_kind::store)) === 7)
    && (count(array_filter($body->instructions, static fn($i) => $i->kind === instruction_kind::call)) === 0),
    'One slot per reached binding; initialization/assignment share stores; unreachable local and recursion produce nothing');
$ir = $first->llvm->function_for($value_id)->ir;
Local_Lowering_Test::check((substr_count($ir, 'alloca ') === 4) && (substr_count($ir, 'load i64') === 4)
    && (substr_count($ir, 'store i64') === 7), 'LLVM emits typed storage, fresh read values and ordered writes');
$export = json_decode($first->to_json(), true, 512, JSON_THROW_ON_ERROR);
$exported = array_values(array_filter($export['lowered'], static fn($b) => $b['callable_id'] === $value_id))[0];
Local_Lowering_Test::check(($exported['slots'][0]['source_local_id'] === 1)
    && ($exported['instructions'][1]['payload'] === ['address' => ['slot_id' => 1, 'projections' => [], 'address_id' => 0], 'value_id' => 1])
    && ($exported['instructions'][1]['result_value_id'] === 0), 'Debug export distinguishes slot/value identities and no-result stores');
$before = serialize($first);
$warm = $session->compile($manifest, $output);
Local_Lowering_Test::check(($warm->llvm === $first->llvm) && ($warm->native === $first->native)
    && (\Step_Test::select(Lowerer::class, $warm->lifetimes, $warm->backend, $first->lowered, false) === []), 'Unchanged locals reuse lowering, emission and artifact');
$tasks = \Step_Test::select(Lowerer::class, $first->lifetimes, $first->backend, new Lowered_Set(), true);
$results = array_map(static fn($task) => (new \lower\Lowering_Worker($task))->lower(), array_reverse($tasks));
$joined = (new \lower\Lowering_Join($first->lifetimes, $first->backend, new Lowered_Set(), $tasks))->join($results);
Local_Lowering_Test::check($joined->to_json() === $first->lowered->to_json(), 'Full selection and reversed lowering workers preserve local plans');
$emit_tasks = \Step_Test::select(LLVM_Emitter::class, $joined, null, true);
$emitted = array_map(static fn($task) => (new \emit_llvm\Emission_Worker($task))->emit(), array_reverse($emit_tasks));
$functions = (new \emit_llvm\Emission_Join($joined, $first->backend, $first->llvm->entry, null, $emit_tasks))->join($emitted);
$module_tasks = \Step_Test::select(\emit_llvm\Module_Assembler::class, $functions, null, true);
$module_results = array_map([\emit_llvm\Module_Worker::class, 'assemble'], array_reverse($module_tasks));
$module = (new \emit_llvm\Module_Join($functions, null, $module_tasks))->join($module_results);
Local_Lowering_Test::check(($module->ir_by_file() === $first->llvm->ir_by_file()) && (serialize($first) === $before),
    'Emission workers join deterministically without mutating shared analyses, slots or instructions');
Local_Lowering_Test::rejects(static fn() => (new \lower\Lowering_Join($first->lifetimes, $first->backend, new Lowered_Set(), $tasks))->join([]), 'Incomplete');

// Lowering must consume lifetime authorization, not merely accept checked writes.
$analysis = $body->input->analysis;
$missing = new \analyze_lifetimes\Analyzed_Body($analysis->body, $analysis->lifetimes,
    $analysis->reachable_statement_count, $analysis->falls_through);
Local_Lowering_Test::rejects(static fn() => (new \lower\Lowering_Worker(new \lower\lowering_input($missing, $first->backend)))->lower(), 'analyzed initialization');
$facts = $analysis->lifetimes;
$f = $facts[0];
$facts[0] = new \analyze_lifetimes\value_lifetime($f->value_id, $f->statement_id, \analyze_lifetimes\lifetime_end::discard);
$missing = new \analyze_lifetimes\Analyzed_Body($analysis->body, $facts,
    $analysis->reachable_statement_count, $analysis->falls_through, $analysis->local_lifetimes);
Local_Lowering_Test::rejects(static fn() => (new \lower\Lowering_Worker(new \lower\lowering_input($missing, $first->backend)))->lower(), 'analyzed lifetime');

// Backend format proof only: a non-default stack space must propagate to both
// allocations and memory operands. This does not advertise another native target.
$config = $first->backend->configuration;
$stack_config = new \prepare_backend\backend_configuration($config->backend_key, $config->target_triple,
    preg_replace('/-A[0-9]+(?=-|$)/', '', $config->data_layout) . '-A5', $config->cpu, $config->features,
    $config->abi_key, $config->runtime_key);
$binding = $body->binding;
$stack_binding = new \prepare_backend\callable_binding($stack_config, $value_id, $binding->signature, $binding->return_definition,
    $binding->link_name, $binding->linkage, $binding->calling_convention);
$stack_context = new \prepare_backend\Backend_Context($stack_config, [$stack_binding]);
$stack_body = (new \lower\Lowering_Worker(new \lower\lowering_input($analysis, $stack_context)))->lower();
$stack_ir = (new \emit_llvm\Emission_Worker($stack_body))->emit()->ir;
Local_Lowering_Test::check(str_contains($stack_ir, 'alloca i64, addrspace(5)')
    && (substr_count($stack_ir, 'ptr addrspace(5)') === 11), 'Stack address space comes from the fixed target layout');
Local_Lowering_Test::rejects(static fn() => new \lower\lowered_instruction(instruction_kind::store, 1, 1,
        new \lower\store_operands(new \lower\storage_address(1), 1)), 'Invalid lowered instruction');
Local_Lowering_Test::rejects(static fn() => $body->slot_for(0), 'Missing lowered local slot');

Local_Lowering_Test::edit($value_path, str_replace('$outer = 42;', '$outer = 43;', $source));
$edited = $session->compile($manifest, $output);
Local_Lowering_Test::check((!$edited->inputs->context->full_rebuild) && (Local_Lowering_Test::run($output) === 43)
    && (count(\Step_Test::select(Lowerer::class, $edited->lifetimes, $edited->backend, $first->lowered, false)) === 1)
    && ($edited->lowered->for_symbol($entry_id) === $first->lowered->for_symbol($entry_id))
    && ($edited->llvm->function_for($entry_id) === $first->llvm->function_for($entry_id))
    && (serialize($first) === $before), 'One body edit replaces its slots/instructions and native behavior while callers and previous snapshots stay shared');
Local_Lowering_Test::rejects(static fn() => (new \lower\Lowering_Join($edited->lifetimes, $edited->backend, $first->lowered, \Step_Test::select(Lowerer::class, $edited->lifetimes, $edited->backend, $first->lowered, false)))->join([$body]), 'stale');
$published = $session->published;
$observed = $session->observed;
$hash = hash_file('sha256', $output);
Local_Lowering_Test::edit($value_path, 'function value(): int { $x int = 1; $x = absent(); return $x; }');
Local_Lowering_Test::rejects(static fn() => $session->compile($manifest, $output), 'Unknown');
Local_Lowering_Test::check(($session->published === $published) && ($session->observed === $observed)
    && (hash_file('sha256', $output) === $hash) && (Local_Lowering_Test::run($output) === 43), 'Failure preserves accepted state and executable');
Local_Lowering_Test::edit($value_path, str_replace('$outer = 42;', '$outer = 44;', $source));
$repair = $session->compile($manifest, $output);
Local_Lowering_Test::check(Local_Lowering_Test::run($output) === 44, 'Repair of a local body goes through the same resident pipeline');
$fresh = (new \compile\Compiler_Session(type_catalog_path: $catalog_path))->compile($manifest, getcwd() . '/fresh-locals');
Local_Lowering_Test::check(($fresh->llvm->ir_by_file() === $repair->llvm->ir_by_file()) && (Local_Lowering_Test::run($fresh->native->path) === 44),
    'Fresh and incremental local-variable compilation agree');

// Void fallthrough, bare return, sibling scopes and call-result initialization.
Local_Lowering_Test::edit($main, 'finish(); stop(); $x int = answer(); $x; return $x;
function finish(): void { { $x int = 1; } { $x int = 2; $x = $x; } }
function stop(): void { $x int = 1; return; $dead int = value(); }');
$voids = $session->compile($manifest, $output);
Local_Lowering_Test::check(Local_Lowering_Test::run($output) === 44, 'Void exits end locals without invented values or unreachable execution');
Local_Lowering_Test::edit($main, 'return answer(); $unreachable int = 8;');
$removed = $session->compile($manifest, $output);
Local_Lowering_Test::check(($removed->lowered->for_symbol($entry_id)->slots === []) && (Local_Lowering_Test::run($output) === 44),
    'Removing reached locals retires their storage; unreachable declarations need no slots');

// Provider-defined integer spelling/width uses the same storage and value path.
foreach ($catalog['types'] as &$type) {
    if ($type['name'] === 'int') {
        $type['name'] = 'Counter';
        $type['bit_width'] = 17;
    }
}
unset($type);
$catalog['literal_types']['integer']['name'] = 'Counter';
$catalog['entry_return_type']['name'] = 'Counter';
file_put_contents($catalog_path, json_encode($catalog, JSON_THROW_ON_ERROR));
Local_Lowering_Test::edit($main, '$x Counter = answer(); return $x;');
Local_Lowering_Test::edit($root . '/src/answer.phs', 'function answer(): Counter { $x Counter = value(); return $x; }');
Local_Lowering_Test::edit($value_path, str_replace(': int', ': Counter', str_replace(' int =', ' Counter =', $source)));
$custom = $session->compile($manifest, $output);
Local_Lowering_Test::check(($custom->inputs->context->full_rebuild) && (Local_Lowering_Test::run($output) === 42)
    && str_contains(implode('', $custom->llvm->ir_by_file()), 'alloca i17') && str_contains(implode('', $custom->llvm->ir_by_file()), 'load i17')
    && str_contains(implode('', $custom->llvm->ir_by_file()), 'store i17'), 'Unknown type name and width produce real native typed storage using shared metadata');

// An uncalled recursive float function supplies a real typed call result. This
// proves float storage is valid LLVM/native object code, not float literal syntax.
Local_Lowering_Test::edit($value_path, file_get_contents($value_path)
    . 'function floating(): float { $x float = floating(); $x = $x; return $x; }');
$floating = $session->compile($manifest, $output);
Local_Lowering_Test::check((Local_Lowering_Test::run($output) === 42) && str_contains(implode('', $floating->llvm->ir_by_file()), 'alloca double')
    && str_contains(implode('', $floating->llvm->ir_by_file()), 'load double') && str_contains(implode('', $floating->llvm->ir_by_file()), 'store double'), 'Floating storage follows representation contracts without integer-name dispatch');
echo "local lowering ok: native scope/copy/write/return behavior, flat storage, unreachable exclusion, exports, fixed workers, reuse, body increment, repair and metadata-driven storage\n";
