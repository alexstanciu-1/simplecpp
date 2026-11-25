<?php
declare(strict_types=1);
require_once __DIR__ . '/../../support/body_support.php';

use Body_Test_Stages as Check;
use check_bodies\Body_Checker as Checker;
use check_bodies\Body_Set;
use check_bodies\value_kind;
use collect_symbols\symbol_kind;
use analyze_lifetimes\Lifetime_Analyzer;
use analyze_lifetimes\Lifetime_Set;

$manifest = '../fixtures/three_files/project.json';
$session = new \compile\Compiler_Session();
$output = getcwd() . '/program';
$baseline = $session->compile($manifest, $output);
$before = $baseline->to_json();
$published = $session->published;
$generation = $session->generation;
$key = hash_file('sha256', $output);
$root = $baseline->inputs->manifest->directory;
$main = $root . '/src/main.phs';
$value = $root . '/src/nested/value.phs';
$original_main = file_get_contents($main);
$original_value = file_get_contents($value);
$definitions = $original_value . '
function pair($a int, $b int): int { $copy int = $a; $copy = $b; return $copy; }
function id($v int): int { return $v; }
function sink($v int): void { return; }
function unsigned($v uint32): uint32 { return $v; }
function unused($v uint32): int { return 42; }';
$source = '$x int = 10; $y int = pair(id($x), id(20)); sink($y); return pair($y, id(42));';
Check::edit($value, $definitions);
Check::edit($main, $source);
$input = Check::prepare($baseline);
$main_id = $input->types->entry->symbol->symbol_id;
$pair_id = $input->symbols->find_symbol('pair', '', symbol_kind::function_symbol);
$unused_id = $input->symbols->find_symbol('unused', '', symbol_kind::function_symbol);
$answer_id = $input->symbols->find_symbol('answer', '', symbol_kind::function_symbol);
$int = $input->types->integer_literal_type();
$tasks = \Step_Test::select(\check_bodies\Body_Checker::class, $input->symbols, $input->names, $input->types, $baseline->bodies, false);
$fixed = serialize([$input, $baseline]);
$results = array_map(static fn($task) => (new \check_bodies\Body_Worker($task))->check(), array_reverse($tasks));
$bodies = (new \check_bodies\Body_Join($input->symbols, $input->names, $input->types, $baseline->bodies, $tasks))->join($results);
$b = $bodies->for_symbol($main_id);
$pair = $bodies->for_symbol($pair_id);
Check::check((serialize([$input, $baseline]) === $fixed) && ($bodies->for_symbol($answer_id) === $baseline->bodies->for_symbol($answer_id)),
    'Independent reversed workers preserve fixed inputs and unaffected bodies');
Check::check(array_map(static fn($c) => $input->symbols->symbol_by_id($c->target_callable_id)->name, $b->calls)
    === ['id', 'id', 'pair', 'sink', 'id', 'pair'], 'Nested call effects follow left-to-right argument evaluation before consumers');
Check::check((array_column($b->calls, 'result_value_id') === [3, 5, 6, 0, 10, 11])
    && (count($b->values) === 11) && (count($b->arguments) === 8), 'Values have evaluation order; void call produces no value row');
Check::check(($b->argument_for(3, 1)->value_id === 3) && ($b->argument_for(3, 2)->value_id === 5)
    && ($b->argument_for(6, 1)->value_id === 8) && ($b->argument_for(6, 2)->value_id === 10),
    'Nested argument ranges remain contiguous and preserve positional value references');
foreach ($b->arguments as $argument) {
    Check::check(($argument->parameter_type_id === $int) && ($b->values[$argument->value_id - 1]->type_id === $int), 'Every argument uses the shared checked conversion contract');
}
Check::check(($pair->entry_parameter_count() === 2) && ($pair->local_types === $input->types->locals_for($pair_id))
    && (count($pair->statements) === 3) && (array_map(static fn($s) => $s->target?->local_id ?? 0, $pair->statements) === [3, 3, 0]),
    'Incoming parameters use existing locals without fabricated initialization statements');
Check::check(($bodies->for_symbol($unused_id)->entry_parameter_count() === 1)
    && ($bodies->for_symbol($unused_id)->definition_for($input->types->types->find_type('uint32'))->signed === false),
    'Unused incoming parameter contracts still belong to the body dependencies');
$export = json_decode($bodies->to_json(), true, 512, JSON_THROW_ON_ERROR);
$row = array_values(array_filter($export, static fn($r) => $r['symbol_id'] === $main_id))[0];
Check::check((count($row['arguments']) === 8) && ($row['calls'][2]['argument_count'] === 2), 'Exports preserve actual argument plans');
Check::rejects(static fn() => $b->argument_for(3, 0), 'Missing checked argument');
Check::rejects(static fn() => $b->argument_for(3, 3), 'Missing checked argument');
Check::check((\Step_Test::select(\check_bodies\Body_Checker::class, $input->symbols, $input->names, $input->types, $bodies, false) === [])
    && (Check::bodies($input, $bodies)->for_symbol($main_id) === $b), 'Warm bodies select no work and share results');
Check::check((Check::bodies($input, $bodies, true)->to_json() === $bodies->to_json())
    && (Check::bodies($input)->to_json() === $bodies->to_json()), 'Full/fresh work uses the same checking path');
Check::rejects(static fn() => (new \check_bodies\Body_Join($input->symbols, $input->names, $input->types, $bodies, $tasks))->join([]), 'Incomplete');
Check::rejects(static fn() => (new \check_bodies\Body_Join($input->symbols, $input->names, $input->types, $bodies, $tasks))->join([...$results, $results[0]]), 'duplicate');
Check::check(count(\Step_Test::select(Lifetime_Analyzer::class, $bodies, new Lifetime_Set(), false)) === count($bodies->bodies()),
    'Checked parameter/argument bodies now participate in lifetime work');
Check::check(((new \analyze_lifetimes\Lifetime_Worker($b))->analyze()->body === $b) && ((new \analyze_lifetimes\Lifetime_Worker($pair))->analyze()->body === $pair),
    'Lifetime workers consume checked parameter and argument plans');
Check::check((new \compile\Compiler_Session())->compile($manifest, getcwd() . '/parameter-program')->completed, 'Checked parameter bodies complete the native pipeline');

// An unchanged caller depends on its callee's parameter contract, not its body.
Check::edit($value, str_replace('function id($v int): int { return $v; }', 'function id($v uint32): int { return 42; }', $definitions));
$changed = Check::prepare($baseline, $input);
Check::check(($changed->symbols->symbol_by_id($main_id) === $input->symbols->symbol_by_id($main_id))
    && in_array($changed->symbols->symbol_by_id($main_id), array_column(\Step_Test::select(\check_bodies\Body_Checker::class, $changed->symbols, $changed->names, $changed->types, $bodies, false), 'owner'), true),
    'Parameter annotation edit selects an unchanged caller');
Check::rejects(static fn() => Check::bodies($changed, $bodies), 'Unsupported implicit argument conversion from int to uint32');
Check::edit($value, $definitions);
$repair = Check::prepare($baseline, $input);
$restored_bodies = Check::bodies($repair, $bodies);
Check::check($restored_bodies->for_symbol($main_id) === $b, 'Repair reuses an unchanged caller with its restored contract');
$invalid = clone $input->types->types;
$invalid->invalidate_definition($input->types->types->find_type('uint32'));
$refreshed = \Step_Test::run(new \resolve_types\Type_Resolver($input->symbols, $input->types->catalog, $invalid, $input->types,
    false, $input->types->entry, $input->names));
Check::check(in_array($input->symbols->symbol_by_id($unused_id), array_column(\Step_Test::select(\check_bodies\Body_Checker::class, $input->symbols, $input->names, $refreshed, $bodies, false), 'owner'), true),
    'Definition invalidation selects bodies with unused parameter types');

// An argument-only body edit replaces its plan and rejects the old AST-backed result.
Check::edit($main, str_replace('id(20)', 'id(21)', $source));
$argument_edit = Check::prepare($baseline, $repair);
$edited_bodies = Check::bodies($argument_edit, $restored_bodies);
Check::check(($edited_bodies->for_symbol($main_id) !== $b)
    && ($edited_bodies->for_symbol($pair_id) === $restored_bodies->for_symbol($pair_id))
    && ($edited_bodies->for_symbol($main_id)->values[3]->payload === '21')
    && ($b->values[3]->payload === '20'), 'Argument body edit replaces only its checked owner and preserves the old plan');
Check::rejects(static fn() => (new \check_bodies\Body_Join($argument_edit->symbols, $argument_edit->names, $argument_edit->types, $bodies, [new \check_bodies\body_check_task($argument_edit->symbols->symbol_by_id($main_id),
            $argument_edit->names->for_symbol($main_id), $argument_edit->types)]))->join([$b]), 'stale');
Check::edit($main, 'return 42; id(1);');
Check::check((new \compile\Compiler_Session())->compile($manifest, getcwd() . '/parameter-program')->completed, 'Checked parameter bodies complete the native pipeline');

// Diagnostics retain exact source spans, even in unreachable statements.
foreach ([
        ['return pair(1);', 'pair', 'Call argument count'],
        ['return id(1, 2);', '2', 'Call argument count'],
        ['return id();', 'id', 'Call argument count'],
        ['return value(1);', '1', 'Call argument count'],
        ['return id(sink(1));', 'sink', 'Argument expression produces no value'],
        ['return unsigned(42);', '42', 'Unsupported implicit argument conversion'],
        ['return 42; unsigned(1);', '1', 'Unsupported implicit argument conversion'],
    ] as [$text, $anchor, $reason]) {
    Check::edit($main, $text);
    $error = Check::rejects(static fn() => $session->compile($manifest, $output), $reason);
    Check::check(($error instanceof \diagnostics\Source_Error) && ($error->path === $main) && ($error->start === strpos($text, $anchor)), 'Argument diagnostics point at the actual call or argument');
}
Check::check(($session->published === $published) && ($session->generation === $generation)
    && (hash_file('sha256', $output) === $key) && ($baseline->to_json() === $before), 'Checking and lifetime failures preserve the accepted executable and snapshots');

// Assignment/shadowing, recursion and void calls use the same parameter model.
Check::edit($value, $definitions . '
function recurse($v int): int { return recurse(id($v)); }
function shadow($v int): int { { $v int = 1; sink($v); } $v = id($v); return $v; }');
Check::edit($main, 'return pair(1, 2);');
$composed = Check::prepare($baseline, $input);
Check::bodies($composed);
Check::edit($main, 'return ' . str_repeat('id(', 1024) . '42' . str_repeat(')', 1024) . ';');
$deep = Check::prepare($baseline);
$deep_body = Check::bodies($deep)->for_symbol($main_id);
Check::check((count($deep_body->arguments) === 1024) && (count($deep_body->calls) === 1024), 'Deep nested checking uses an explicit stack');
$parameters = implode(',', array_map(static fn($i) => '$p' . $i . ' int', range(1, 1500)));
Check::edit($value, $definitions . ' function wide(' . $parameters . '): int { return $p1500; }');
Check::edit($main, 'return wide(' . implode(',', array_fill(0, 1500, '42')) . ');');
$wide = Check::bodies(Check::prepare($baseline))->for_symbol($main_id);
Check::check((count($wide->calls) === 1) && (count($wide->arguments) === 1500) && (count($wide->values) === 1501),
    'Wide calls retain linear argument/value rows');
Check::edit($main, $original_main);
Check::edit($value, $original_value);
$final = $session->compile($manifest, $output);
Check::check(($final->completed) && ($baseline->to_json() === $before), 'Repair returns to the existing native subset');
$process = proc_open([$output], [0 => ['file', '/dev/null', 'r'], 1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
Check::check(is_resource($process) && (proc_close($process) === 42), 'Repaired executable returns 42');
echo "parameter bodies ok: initialized inputs, flat argument plans, left-to-right nesting, conversions, dependencies, workers/joins, reuse, exports, failures and native completion\n";
