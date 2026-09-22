<?php
declare(strict_types=1);
require_once __DIR__ . '/../../support/bootstrap.php';

use compile\Phases;
use compile\Update_Context;
use check_bodies\Body_Checker;
use check_bodies\Body_Set;
use check_bodies\statement_kind;
use check_bodies\value_kind;
use collect_symbols\symbol_kind;

require_once __DIR__ . '/../../support/body_support.php';

$path = '../fixtures/three_files/project.json';
$main = realpath('../fixtures/three_files/src/main.phs');
$value_path = realpath('../fixtures/three_files/src/nested/value.phs');
$original_main = file_get_contents($main);
$original_value = file_get_contents($value_path);
Body_Test_Stages::edit($value_path, $original_value . "\nfunction noop(): void { return; }\nfunction unsigned(): uint32 { return unsigned(); }\nfunction real(): float { return real(); }");
$session = new \compile\Compiler_Session();
$baseline = $session->compile($path);
$before = serialize($baseline);
$source = '$x int = answer(); { $x int = value(); $x = $x; {} } $x = $x; noop(); return $x; $x = value();';
Body_Test_Stages::edit($main, $source);
$input = Body_Test_Stages::prepare($baseline);
$main_id = $input->types->entry->symbol->symbol_id;
$answer_id = $input->symbols->find_symbol('answer', '', symbol_kind::function_symbol);
$int = $input->types->types->find_type('int');
$fixed = serialize([$input, $baseline]);
$tasks = \Step_Test::select(Body_Checker::class, $input->symbols, $input->names, $input->types, $baseline->bodies, false);
Body_Test_Stages::check(count($tasks) === 1, 'Only the edited entry needs body checking');
$results = [];
foreach (array_reverse($tasks) as $task) {
    $results[] = (new \check_bodies\Body_Worker($task))->check();
}
$bodies = (new \check_bodies\Body_Join($input->symbols, $input->names, $input->types, $baseline->bodies, $tasks))->join($results);
$b = $bodies->for_symbol($main_id);
Body_Test_Stages::check(serialize([$input, $baseline]) === $fixed, 'Body workers and join preserve all fixed stage inputs');
Body_Test_Stages::check(($b->local_types === $input->types->locals_for($main_id)) && ($b->local_type_for(1) === $int)
    && ($b->definition_for($int) === $input->types->definition_for($int)), 'Bodies retain shared local/type contracts with owned accessors');
Body_Test_Stages::check((array_map(static fn($s) => $s->target?->local_id ?? 0, $b->statements) === [1, 2, 2, 1, 0, 0, 1])
    && (array_column($b->statements, 'scope_id') === [1, 2, 2, 1, 1, 1, 1]), 'Writes name destinations independently of value IDs and respect shadowing');
Body_Test_Stages::check(array_map(static fn($s) => [$s->statement_start, $s->statement_count], $b->scopes) === [[0, 7], [1, 2], [3, 0]],
    'Ranges preserve parent/child containment and empty blocks without copied AST rows');
Body_Test_Stages::check(($b->scope_for(2) === $b->scopes[1]) && (!$b->falls_through), 'Scope access is shared and any-depth return exits the callable');
Body_Test_Stages::rejects(static fn() => $b->scope_for(0), 'Missing checked scope');
$reads = array_values(array_filter($b->values, static fn($v) => $v->kind === value_kind::local_read));
Body_Test_Stages::check((array_map(static fn($v) => $v->payload->local_id, $reads) === [2, 1, 1]) && (array_column($reads, 'type_id') === [$int, $int, $int]), 'Each local read creates a typed value referring to its binding identity');
Body_Test_Stages::check((array_column($b->statements, 'call_start') === [0, 1, 2, 2, 2, 3, 3])
    && (array_column($b->statements, 'call_count') === [1, 1, 0, 0, 1, 0, 1])
    && ($b->calls[2]->result_value_id === 0) && ($b->statements[4]->value_id === 0), 'Evaluation segments preserve calls, including void calls and unreachable statements');
foreach ($b->statements as $statement) {
    if ((($statement->target?->local_id ?? 0) !== 0) || ($statement->kind === statement_kind::return_statement)) {
        Body_Test_Stages::check($b->values[$statement->value_id - 1]->type_id === (($statement->target?->local_id ?? 0) === 0 ? $b->signature_for($main_id)->return_type : $b->local_type_for(($statement->target?->local_id ?? 0))), 'Every typed write or return consumes a destination-typed value');
    }
}
Body_Test_Stages::check($bodies->for_symbol($answer_id) === $baseline->bodies->for_symbol($answer_id), 'Unchanged callable output is shared');
$export = $bodies->to_json();
$rows = json_decode($export, true, 512, JSON_THROW_ON_ERROR);
$entry_row = array_values(array_filter($rows, static fn($r) => $r['symbol_id'] === $main_id))[0];
Body_Test_Stages::check(($entry_row['values'][2]['kind'] === 'local_read') && ($entry_row['statements'][2]['target']['local_id'] === 2)
    && ($entry_row['scopes'][2]['statement_count'] === 0) && ($bodies->to_json() === $export), 'Exports describe typed reads, destinations and scope ranges without mutating results');
$warm = Body_Test_Stages::prepare($baseline, $input);
Body_Test_Stages::check((\Step_Test::select(Body_Checker::class, $warm->symbols, $warm->names, $warm->types, $bodies, false) === [])
    && (Body_Test_Stages::bodies($warm, $bodies)->for_symbol($main_id) === $b), 'Warm pipeline input reuse selects no body work');
$full = Body_Test_Stages::bodies($input, $bodies, true);
Body_Test_Stages::check(($full->to_json() === $export) && ($full->for_symbol($main_id) !== $b), 'Full selection uses the common body algorithm');
$fresh_inputs = Body_Test_Stages::prepare($baseline);
Body_Test_Stages::check(Body_Test_Stages::bodies($fresh_inputs)->to_json() === $export,
    'Fresh reads, parsing, binding and types produce equivalent checked bodies');

// Reverse a complete worker batch as well as the single-file incremental batch.
$all_tasks = \Step_Test::select(Body_Checker::class, $input->symbols, $input->names, $input->types, new Body_Set(), true);
$all_results = [];
foreach (array_reverse($all_tasks) as $task) {
    $all_results[] = (new \check_bodies\Body_Worker($task))->check();
}
Body_Test_Stages::check((new \check_bodies\Body_Join($input->symbols, $input->names, $input->types, new Body_Set(), $all_tasks))->join($all_results)->to_json() === $export,
    'Complete reversed worker results join deterministically');
Body_Test_Stages::rejects(static fn() => (new \check_bodies\Body_Join($input->symbols, $input->names, $input->types, $bodies, $tasks))->join([]), 'Incomplete');
Body_Test_Stages::rejects(static fn() => (new \check_bodies\Body_Join($input->symbols, $input->names, $input->types, $bodies, $tasks))->join([...$results, $results[0]]), 'duplicate');

// Removing fixed local-type input must be diagnosed before checking the body.
$missing = new \resolve_types\Type_Resolution($input->types->types, $input->types->catalog, $input->types->entry, $input->types->signatures(), [], $input->names);
Body_Test_Stages::rejects(static fn() => (new \check_bodies\Body_Worker(new \check_bodies\body_check_task($input->symbols->symbol_by_id($main_id),
        $input->names->for_symbol($main_id), $missing)))->check(), 'current resolved local types');
Body_Test_Stages::check(count(\Step_Test::select(Body_Checker::class, $input->symbols, $input->names, $missing, $bodies, false)) === 1, 'Missing associations cannot reuse a checked body');

foreach ([
        ['$x uint32 = 42; return 42;', '42', 'Unsupported implicit initialization conversion from int to uint32'],
        ['$x uint32 = unsigned(); $x = 42; return 42;', '42;', 'Unsupported implicit assignment conversion from int to uint32'],
        ['$x uint32 = unsigned(); return $x;', '$x;', 'Unsupported implicit return conversion from uint32 to int'],
        ['$x int = noop(); return 42;', 'noop()', 'Initialization expression produces no value'],
        ['$x int = 42; $x = noop(); return $x;', 'noop()', 'Assignment expression produces no value'],
        ['{ return; }', 'return;', 'A value is required'],
        ['{ $x int = 42; }', '{', 'Callable can finish without returning a value'],
        ['return 42; { $x uint32 = 42; }', '42; }', 'Unsupported implicit initialization'],
        ['$x float = real(); $x = unsigned(); return 42;', 'unsigned()', 'Unsupported implicit assignment conversion from uint32 to float'],
    ] as [$text, $anchor, $message])
{
    Body_Test_Stages::edit($main, $text);
    $error = Body_Test_Stages::rejects(static fn() => $session->compile($path), $message);
    Body_Test_Stages::check(($error instanceof \diagnostics\Source_Error) && ($error->path === $main)
        && ($error->start === strpos($text, $anchor)), 'Type error anchors the failing expression or body');
    Body_Test_Stages::check((serialize($baseline) === $before) && ($session->observed?->bodies === $baseline->bodies),
        'Failed checking preserves the accepted compiler snapshot');
}

Body_Test_Stages::edit($main, '$x uint32 = unsigned(); { $x float = real(); $x = real(); } $x = unsigned(); return 42;');
$mixed = Body_Test_Stages::prepare($baseline, $input);
$mixed_bodies = Body_Test_Stages::bodies($mixed, $bodies);
$mb = $mixed_bodies->for_symbol($main_id);
Body_Test_Stages::check(($mb->local_type_for(1) !== $mb->local_type_for(2)) && (count($mb->type_dependencies) === 3),
    'Different shadowed types use their resolved identities and all consumed definitions are dependencies');
Body_Test_Stages::rejects(static fn() => (new \check_bodies\Body_Join($mixed->symbols, $mixed->names, $mixed->types, $bodies, [new \check_bodies\body_check_task($mixed->symbols->symbol_by_id($main_id),
            $mixed->names->for_symbol($main_id), $mixed->types)]))->join([$b]), 'stale');
Body_Test_Stages::check($bodies->to_json() === $export, 'Replacing an edited body preserves old typed values and scopes');

// An unchanged AST still depends on the called function's return contract.
Body_Test_Stages::edit($main, '$x int = value(); return $x;');
$caller = Body_Test_Stages::prepare($baseline, $mixed);
$caller_bodies = Body_Test_Stages::bodies($caller, $mixed_bodies);
$with_helpers = file_get_contents($value_path);
Body_Test_Stages::edit($value_path, str_replace('function value(): int', 'function value(): uint32', $with_helpers));
$changed_callee = Body_Test_Stages::prepare($baseline, $caller);
Body_Test_Stages::check(($changed_callee->names->for_symbol($main_id) === $caller->names->for_symbol($main_id))
    && in_array($changed_callee->symbols->symbol_by_id($main_id), array_column(\Step_Test::select(Body_Checker::class, $changed_callee->symbols, $changed_callee->names, $changed_callee->types, $caller_bodies, false), 'owner'), true),
    'Callee signature edits invalidate an unchanged local initializer caller');
Body_Test_Stages::rejects(static fn() => (new \check_bodies\Body_Worker(new \check_bodies\body_check_task($changed_callee->symbols->symbol_by_id($main_id),
        $changed_callee->names->for_symbol($main_id), $changed_callee->types)))->check(), 'Unsupported implicit initialization conversion from uint32 to int');
Body_Test_Stages::edit($value_path, $with_helpers);
$repaired = Body_Test_Stages::prepare($baseline, $caller);
Body_Test_Stages::check(Body_Test_Stages::bodies($repaired, $caller_bodies)->for_symbol($main_id)->values[0]->type_id === $int,
    'Failure followed by repair uses the accepted per-stage baseline');

// Any-depth returns, empty scopes, void fallthrough, and many local reads.
Body_Test_Stages::edit($main, 'return 42; function empty(): void { {} } function withlocal(): void { $x int = 42; { $x = $x; return; } }');
$void_input = Body_Test_Stages::prepare($baseline, $repaired);
$void_bodies = Body_Test_Stages::bodies($void_input);
$empty = $void_bodies->for_symbol($void_input->symbols->find_symbol('empty', '', symbol_kind::function_symbol));
$withlocal = $void_bodies->for_symbol($void_input->symbols->find_symbol('withlocal', '', symbol_kind::function_symbol));
Body_Test_Stages::check(($empty->falls_through) && ($empty->statements === []) && ($empty->scope_for(2)->statement_count === 0)
    && (!$withlocal->falls_through) && ($withlocal->statements[2]->value_id === 0),
    'Void fallthrough and nested bare returns retain real scopes without inventing values');
Body_Test_Stages::edit($main, '{ { return 42; } {} } 43;');
$nested = Body_Test_Stages::prepare($baseline, $repaired);
$nb = Body_Test_Stages::bodies($nested)->for_symbol($main_id);
Body_Test_Stages::check((!$nb->falls_through) && (count($nb->statements) === 2) && ($nb->statements[0]->scope_id === 3)
    && ($nb->scope_for(4)->statement_count === 0), 'Nested return exits the callable while unreachable statements are still checked');
$nested_compiled = $session->compile($path);
Body_Test_Stages::check($nested_compiled->lifetimes->for_symbol($main_id)->reachable_statement_count === 1, 'Nested scalar return uses the existing downstream path');
Body_Test_Stages::edit($main, '$x int = 42;' . str_repeat('{', 128) . str_repeat('$x = $x;', 1000) . str_repeat('}', 128) . 'return $x;');
$large = Body_Test_Stages::prepare($baseline, $nested);
$lb = Body_Test_Stages::bodies($large)->for_symbol($main_id);
Body_Test_Stages::check((count($lb->scopes) === 129) && (count($lb->statements) === 1002) && (count($lb->values) === 1002)
    && ($lb->scope_for(129)->statement_count === 1000), 'Deep scopes and long statement lists use flat output and explicit traversal');
Body_Test_Stages::check(count($session->compile($path)->lowered->for_symbol($main_id)->slots) === 1, 'Many writes lower to one binding slot');
Body_Test_Stages::edit($main, $original_main);
Body_Test_Stages::check($session->compile($path)->llvm->ir_by_file() === $baseline->llvm->ir_by_file(), 'Supported programs compile again after restoring the source');
echo "local bodies ok: typed initialization/reads/writes, shared conversions, scope ranges, void/return rules, dependencies, fixed workers, reuse, failure/repair and local lowering\n";
