<?php
declare(strict_types=1);
require_once __DIR__ . '/../support/body_support.php';

use lower\Lowerer;
use lower\Lowered_Set;
use lower\instruction_kind;
use emit_llvm\LLVM_Emitter;
use collect_symbols\symbol_kind;

class Parameter_Lowering_Test extends Body_Test_Stages
{
    /** Execute a generated program and return its status for native assertions. */
    public static function run(string $path): int
    {
        $process = proc_open([$path], [0 => ['file', '/dev/null', 'r'], 1 => ['file', '/dev/null', 'w'],
                2 => ['file', '/dev/null', 'w']], $pipes);
        self::check(is_resource($process), 'Start native parameter program');
        try
        {
            $deadline = hrtime(true) + 2_000_000_000;
            do
            {
                $status = proc_get_status($process);
                if (!$status['running']) {
                    return $status['exitcode'];
                }
                self::check(hrtime(true) < $deadline, 'Parameter program timed out');
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

    public static function id(\compile\Compile_Result $result, string $name): int
    {
        return $result->symbols->current->find_symbol($name, '', symbol_kind::function_symbol);
    }
}
use Parameter_Lowering_Test as Check;

// Build cross-file fixtures that expose argument order, parameter copies, and shadowing.
$manifest = '../fixtures/three_files/project.json';
$root = realpath(dirname($manifest));
$main = $root . '/src/main.phs';
$answer = $root . '/src/answer.phs';
$value = $root . '/src/nested/value.phs';
$output = getcwd() . '/parameters';
$catalog_path = getcwd() . '/types.json';
$catalog = json_decode(file_get_contents(dirname(__DIR__, 2) . '/language/named_types.json'), true, 512, JSON_THROW_ON_ERROR);
file_put_contents($catalog_path, json_encode($catalog, JSON_THROW_ON_ERROR));
$main_source = '$x int = 17; return pair($x, id(id(42))); sink(99);';
$pair_source = 'function pair($a int, $b int): int {
    $copy int = $a;
    { $a int = 90; sink($a); }
    $a = id($b);
    $b = 99;
    return choose($copy, $a);
    $dead int = 0;
}';
$value_source = 'function choose($first int, $second int): int { return $second; }
function id($v int): int { return $v; }
function sink($v int): void {}
function bare($v int): void { return; }
function finish($v int): void { { $x int = $v; } $v = $v; }
function unsigned($v uint32): uint32 { return $v; }
function float_sink($v float): void {}
function floating($v float, $unused int): float { return $v; }
function forward_float($v float): float { return floating($v, 1); }';
Check::edit($main, $main_source);
Check::edit($answer, $pair_source);
Check::edit($value, $value_source);
$session = new \compile\Compiler_Session(type_catalog_path: $catalog_path);
$first = $session->compile($manifest, $output);
Check::check(($first->completed) && (Check::run($output) === 42), 'Native three-file argument passing, nested calls, shadowing, parameter assignment and copied locals');
$entry_id = $first->types->entry->symbol->symbol_id;
$pair_id = Check::id($first, 'pair');
$choose_id = Check::id($first, 'choose');
$id_id = Check::id($first, 'id');
$entry = $first->lowered->for_symbol($entry_id);
$pair = $first->lowered->for_symbol($pair_id);

// Check the plans as well as the executable: observable output alone cannot prove evaluation order.
$parameters = array_values(array_filter($pair->instructions, static fn($i) => $i->kind === instruction_kind::parameter));
Check::check((array_column($parameters, 'payload') === [1, 2]) && (array_column($pair->slots, 'source_local_id') === [1, 2, 3, 4])
    && ($pair->values[0]->source_value_id === 0) && ($pair->values[1]->source_value_id === 0),
    'Incoming values initialize ordinary slots; shadowed binding is distinct and unreachable local gets no slot');
$entry_kinds = array_column($entry->instructions, 'kind');
Check::check($entry_kinds === [instruction_kind::constant, instruction_kind::store, instruction_kind::load,
        instruction_kind::constant, instruction_kind::call, instruction_kind::call, instruction_kind::call],
    'First argument load precedes nested second-argument calls; unreachable sink is absent');
$calls = array_values(array_filter($entry->instructions, static fn($i) => $i->kind === instruction_kind::call));
Check::check((array_map(static fn($i) => $i->payload->target->callable_id, $calls) === [$id_id, $id_id, $pair_id])
    && (array_map(static fn($i) => [$i->payload->argument_start, $i->payload->argument_count], $calls) === [[0,1],[1,1],[2,2]])
    && ($entry->arguments === [3,4,2,5]), 'Calls own contiguous lowered operand ranges with earlier copies retained across nested calls');

// Verify agreement between prepared signatures, emitted imports, and incoming storage.
$ir = implode('', $first->llvm->ir_by_file());
Check::check(str_contains($ir, 'define external ccc i64 @"' . $pair->binding->link_name . '"(i64 %p1, i64 %p2)')
    && str_contains($ir, 'declare ccc i64 @"' . $pair->binding->link_name . '"(i64, i64)')
    && str_contains($ir, 'store i64 %p1, ptr addrspace(0) %s1')
    && str_contains($ir, '(double %p1, i64 %p2)') && str_contains($ir, 'call ccc double'),
    'Definitions, imports, incoming storage and mixed scalar call signatures agree; floating forwarding compiles without claiming float literal support');
$sink = $first->lowered->for_symbol(Check::id($first, 'sink'));
Check::check((count($sink->values) === 1) && (count($sink->slots) === 1) && (count($sink->instructions) === 2)
    && ($sink->blocks[0]->terminator->value_id === 0), 'Empty void parameter body initializes and ends its binding with no synthetic return value');

// Inspection exports must describe the same parameter and argument contracts as native lowering.
$export = json_decode($first->to_json(), true, 512, JSON_THROW_ON_ERROR);
$row = array_values(array_filter($export['lowered'], static fn($b) => $b['callable_id'] === $entry_id))[0];
Check::check(($row['arguments'] === $entry->arguments) && ($row['instructions'][6]['payload']['argument_count'] === 2),
    'Debug export keeps scalar operand references and compact call contracts');
$backend_export = json_decode($first->backend->to_json(), true, 512, JSON_THROW_ON_ERROR);
$row = array_values(array_filter($backend_export['callables'], static fn($b) => $b['callable_id'] === $pair_id))[0];
Check::check($row['parameter_type_ids'] === [$first->types->integer_literal_type(), $first->types->integer_literal_type()], 'Backend debug exposes ordered shared parameter types');

// Every worker reads fixed inputs; both full/reversed and warm selection use the same path.
$before = serialize($first);
$warm = $session->compile($manifest, $output);
Check::check(($warm->llvm === $first->llvm) && ($warm->native === $first->native)
    && (\Step_Test::select(Lowerer::class, $warm->lifetimes, $warm->backend, $first->lowered, false) === []), 'Warm parameter program reuses all native work');
$tasks = \Step_Test::select(Lowerer::class, $first->lifetimes, $first->backend, new Lowered_Set(), true);
$results = array_map(static fn($task) => (new \lower\Lowering_Worker($task))->lower(), array_reverse($tasks));
$joined = (new \lower\Lowering_Join($first->lifetimes, $first->backend, new Lowered_Set(), $tasks))->join($results);
Check::check($joined->to_json() === $first->lowered->to_json(), 'Full reversed lower workers produce the same plan');
$emit_tasks = \Step_Test::select(LLVM_Emitter::class, $joined, null, true);
$emitted = array_map(static fn($task) => (new \emit_llvm\Emission_Worker($task))->emit(), array_reverse($emit_tasks));
$functions = (new \emit_llvm\Emission_Join($joined, $first->backend, $first->llvm->entry, null, $emit_tasks))->join($emitted);
$module_tasks = \Step_Test::select(\emit_llvm\Module_Assembler::class, $functions, null, true);
$modules = array_map([\emit_llvm\Module_Worker::class, 'assemble'], array_reverse($module_tasks));
$program = (new \emit_llvm\Module_Join($functions, null, $module_tasks))->join($modules);
Check::check(($program->ir_by_file() === $first->llvm->ir_by_file()) && (serialize($first) === $before),
    'Reversed emission/file assembly preserve exact signatures and all retained inputs');
Check::rejects(static fn() => (new \lower\Lowering_Join($first->lifetimes, $first->backend, new Lowered_Set(), $tasks))->join([]), 'Incomplete');
Check::rejects(static fn() => (new \lower\Lowering_Join($first->lifetimes, $first->backend, new Lowered_Set(), $tasks))->join([...$results, $results[0]]), 'duplicate');

// The same two inputs are independently observable across a body-only edit.
Check::edit($value, str_replace('return $second;', 'return $first;', $value_source));
$edited = $session->compile($manifest, $output);
Check::check((!$edited->inputs->context->full_rebuild) && (Check::run($output) === 17)
    && ($edited->backend === $first->backend) && ($edited->lowered->for_symbol($pair_id) === $pair)
    && ($edited->lowered->for_symbol($entry_id) === $entry), 'Callee body edit selects the first copied argument and reuses unchanged caller plans');
foreach ([$pair->source_file_id(), $entry->source_file_id()] as $file_id) {
    Check::check($edited->native->object_for($file_id) === $first->native->object_for($file_id), 'Unchanged caller object is shared');
}
Check::check($edited->native->object_for($first->lowered->for_symbol($choose_id)->source_file_id())
    !== $first->native->object_for($first->lowered->for_symbol($choose_id)->source_file_id()), 'Edited source replaces its object');
Check::rejects(static fn() => (new \lower\Lowering_Join($edited->lifetimes, $edited->backend, $first->lowered, \Step_Test::select(Lowerer::class, $edited->lifetimes, $edited->backend, $first->lowered, false)))->join([$first->lowered->for_symbol($choose_id)]), 'stale');

// An invalid call must preserve both resident snapshots and the last published executable.
$observed = $session->observed;
$published = $session->published;
$generation = $session->generation;
$hash = hash_file('sha256', $output);
Check::edit($main, 'return pair(17);');
Check::rejects(static fn() => $session->compile($manifest, $output), 'Call argument count');
Check::check(($session->observed === $observed) && ($session->published === $published) && ($session->generation === $generation)
    && (hash_file('sha256', $output) === $hash) && (Check::run($output) === 17), 'Argument failure preserves resident snapshots and executable');
Check::edit($main, str_replace('= 17;', '= 18;', $main_source));
$repair = $session->compile($manifest, $output);
Check::check((!$repair->inputs->context->full_rebuild) && (Check::run($output) === 18) && (serialize($first) === $before), 'Repair uses the same resident pipeline and preserves previous snapshots');
$fresh = (new \compile\Compiler_Session(type_catalog_path: $catalog_path))->compile($manifest, getcwd() . '/fresh-parameters');
Check::check(($fresh->llvm->ir_by_file() === $repair->llvm->ir_by_file()) && (Check::run($fresh->native->path) === 18), 'Fresh and incremental parameter compilation agree');

// Lowering checks exact consuming-call facts, not just statement-level lifetime coverage.
$analysis = $first->lifetimes->for_symbol($entry_id);
$lifetimes = $analysis->lifetimes;
$f = $lifetimes[1];
$lifetimes[1] = new \analyze_lifetimes\value_lifetime($f->value_id, $f->statement_id, $f->end, $f->consumer_id + 1);
$bad = new \analyze_lifetimes\Analyzed_Body($analysis->body, $lifetimes, $analysis->reachable_statement_count,
    $analysis->falls_through, $analysis->local_lifetimes);
Check::rejects(static fn() => (new \lower\Lowering_Worker(new \lower\lowering_input($bad, $first->backend)))->lower(), 'does not match analyzed lifetime');
$bad = new \lower\Lowered_Body($entry->input, $entry->binding, $entry->values, $entry->instructions,
    $entry->blocks, $entry->entry_block_id, $entry->slots, [999, ...array_slice($entry->arguments, 1)]);
Check::rejects(static fn() => (new \emit_llvm\Emission_Worker($bad))->emit(), 'LLVM argument type');

// Backend parameter definitions are dependencies even when the return is unchanged.
$toolchain = new \prepare_backend\LLVM_Toolchain();
$backend = \Step_Test::run(new \prepare_backend\LLVM_Backend($toolchain, $first->types, null, true));
$invocations = $toolchain->invocation_count();
Check::check((\Step_Test::run(new \prepare_backend\LLVM_Backend($toolchain, $first->types, $backend, false)) === $backend) && ($toolchain->invocation_count() === $invocations),
    'Identical signatures reuse prepared bindings and successful scalar signature probes');

// Producer fixture: replace only a parameter definition while retaining its shape,
// IDs and all other definitions. A cache-row refresh with the same shared definition
// is intentionally reusable; a replacement definition is a different contract.
$invalid = clone $first->types->types;
$float_id = $invalid->find_type('float');
$definition = clone $invalid->definition_for_type($float_id);
$invalid->invalidate_definition($float_id);
\resolve_types\Type_Cache::materialize($invalid, $definition);
$locals = [];
foreach ($first->types->signatures() as $signature) {
    $local = $first->types->locals_for($signature->symbol_id);
    if ($local !== null) {
        $locals[] = $local;
    }
}
$types = new \resolve_types\Type_Resolution($invalid, $first->types->catalog, $first->types->entry,
    $first->types->signatures(), $locals, $first->resolutions);
$refreshed = \Step_Test::run(new \prepare_backend\LLVM_Backend($toolchain, $types, $backend, false));
$float_sink = Check::id($first, 'float_sink');
Check::check(($refreshed !== $backend) && ($refreshed->binding_for($float_sink) !== $backend->binding_for($float_sink))
    && ($refreshed->binding_for($float_sink)->return_definition === $backend->binding_for($float_sink)->return_definition)
    && ($refreshed->binding_for($float_sink)->signature === $backend->binding_for($float_sink)->signature)
    && ($refreshed->binding_for($float_sink)->parameters[0]->definition === $definition)
    && ($toolchain->invocation_count() === $invocations),
    'Parameter-only definition replacement refreshes contracts while identical scalar signature probes remain cached');
Check::rejects(static fn() => $backend->validate($types), 'Stale');
Check::rejects(static fn() => $refreshed->callable_for($first->bodies->for_symbol($float_sink), $float_sink), 'Stale backend parameter');

// More arguments than integer registers exercises real target stack passing.
$params = implode(', ', array_map(static fn($i) => '$p' . $i . ' int', range(1, 12)));
Check::edit($value, $value_source . 'function wide(' . $params . '): int { $p12 = id($p12); return $p12; }');
Check::edit($main, 'bare(1); finish(2); return wide(' . implode(',', range(31, 42)) . ');');
$wide = $session->compile($manifest, $output);
Check::check(($wide->inputs->context->full_rebuild) && (Check::run($output) === 42), 'Added declaration uses full fallback; twelve scalar arguments work across files and through parameter assignment');
Check::edit($main, 'return ' . str_repeat('id(', 512) . '43' . str_repeat(')', 512) . ';');
$deep = $session->compile($manifest, $output);
Check::check((!$deep->inputs->context->full_rebuild) && (Check::run($output) === 43)
    && (count($deep->lowered->for_symbol($entry_id)->arguments) === 512), 'Deep nested arguments lower iteratively and execute once per call');

// No integer name/width assumption in callable storage or argument emission.
foreach ($catalog['types'] as &$type) {
    if ($type['name'] === 'int') {
        $type['name'] = 'Counter';
        $type['bit_width'] = 23;
    }
}
unset($type);
$catalog['literal_types']['integer']['name'] = 'Counter';
$catalog['entry_return_type']['name'] = 'Counter';
file_put_contents($catalog_path, json_encode($catalog, JSON_THROW_ON_ERROR));
Check::edit($main, str_replace(' int', ' Counter', $main_source));
Check::edit($answer, str_replace([' int', ': int'], [' Counter', ': Counter'], $pair_source));
Check::edit($value, str_replace([' int', ': int'], [' Counter', ': Counter'], $value_source));
$custom = $session->compile($manifest, $output);
Check::check(($custom->inputs->context->full_rebuild) && (Check::run($output) === 42)
    && str_contains(implode('', $custom->llvm->ir_by_file()), '(i23 %p1, i23 %p2)'), 'Provider-defined type name and nonstandard width compile through the same parameter path');
echo "parameter lowering ok: native cross-file copies, argument order, nested/void calls, stack passing, shared contracts, exports, fixed workers, reuse, body increment, failure/repair and provider-defined types\n";
