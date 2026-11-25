<?php
declare(strict_types=1);
require_once __DIR__ . '/../../support/local_resolution_support.php';
require_once __DIR__ . '/../../support/body_support.php';

use resolve_symbols\Symbol_Resolver as Resolver;
use resolve_symbols\Resolution_Set;
use collect_symbols\symbol_kind;
use compile\Phases;
use compile\Update_Context;
use Local_Resolution_Test as Check;

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
$answer = $root . '/src/answer.phs';
$original_main = file_get_contents($main);
$original_value = file_get_contents($value);
$original_answer = file_get_contents($answer);
$main_id = $baseline->symbols->current->entry_symbol_id($baseline->inputs->sources->entry_file()->id);
$answer_id = $baseline->symbols->current->find_symbol('answer', '', symbol_kind::function_symbol);
$value_id = $baseline->symbols->current->find_symbol('value', '', symbol_kind::function_symbol);
$source = '$x int = 1; $y int = value(answer($x), value($x, 2));
    { $x int = 3; $y = answer(value($x), $y); }
    return value($x, $y);';
Check::edit($main, $source);
Check::edit($value, 'function value($p int32): int { return answer($p); }');
$symbols = Check::project($baseline);
$tasks = \Step_Test::select(\resolve_symbols\Symbol_Resolver::class, $symbols, $baseline->resolutions, false, $baseline->types->catalog);
$fixed = serialize([$symbols, $baseline->resolutions]);
$results = array_map(static fn($task) => (new \resolve_symbols\Resolution_Worker($symbols, $task, $baseline->types->catalog))->run(), array_reverse($tasks));
$names = (new \resolve_symbols\Resolution_Join($baseline->resolutions, $symbols, $tasks, $baseline->types->catalog))->join($results);
$r = $names->for_symbol($main_id);
Check::check(array_map(static fn($b) => $symbols->symbol_by_id($b->target_symbol_id)->name, $r->bindings)
    === ['value', 'answer', 'value', 'answer', 'value', 'value'], 'Nested callees use the existing binding table in source traversal order');
Check::check(array_column($r->local_bindings, 'local_id') === [1, 1, 2, 3, 2, 1, 2],
    'Argument reads obey initializer, assignment and nested-shadow scope rules');
$parameter = $names->for_symbol($value_id);
Check::check(($parameter->parameter_count === 1) && (count($parameter->locals) === 1)
    && ($parameter->local_bindings[0]->local_id === 1), 'Parameter arguments reference existing local records');
Check::check(($names->for_symbol($answer_id) === $baseline->resolutions->for_symbol($answer_id))
    && (serialize([$symbols, $baseline->resolutions]) === $fixed), 'Workers preserve inputs and retain unaffected callers');
foreach ($r->bindings as $binding) {
    Check::check($r->target_for($binding->use_node_id) === $binding->target_symbol_id, 'Nested target lookup');
}
foreach ($r->local_bindings as $binding) {
    Check::check($r->binding_for($binding->use_node_id) === $binding, 'Shared local-use lookup');
}
$export = json_decode($names->to_json(), true, 512, JSON_THROW_ON_ERROR);
$row = array_values(array_filter($export, static fn($row) => $row['symbol_id'] === $main_id))[0];
Check::check((count($row['bindings']) === 6) && (count($row['local_bindings']) === 7), 'Exports include nested argument bindings');
Check::check(\Step_Test::select(\resolve_symbols\Symbol_Resolver::class, $symbols, $names, false, $baseline->types->catalog) === [], 'Unchanged name results select no work');
$full = new Update_Context();
$full->full_rebuild = true;
Check::check((Phases::run_symbols($symbols, $names, $full, $baseline->types->catalog)->to_json() === $names->to_json())
    && (Phases::run_symbols($symbols, new Resolution_Set(), new Update_Context(), $baseline->types->catalog)->to_json() === $names->to_json()),
    'Fresh and full resolution use the same path and produce equal facts');
Check::rejects(static fn() => (new \resolve_symbols\Resolution_Join($names, $symbols, $tasks, $baseline->types->catalog))->join([]), 'Incomplete');
Check::rejects(static fn() => (new \resolve_symbols\Resolution_Join($names, $symbols, $tasks, $baseline->types->catalog))->join([...$results, $results[0]]), 'duplicate');


// Parameter-free callees still reject arguments at checking, never silently ignoring values.
Check::edit($value, $original_value);
Check::edit($main, 'return value(answer(1));');
$input = Body_Test_Stages::prepare($baseline);
$error = Check::rejects(static fn() => (new \check_bodies\Body_Worker(new \check_bodies\body_check_task($input->symbols->symbol_by_id($main_id),
        $input->names->for_symbol($main_id), $input->types)))->check(), 'Call argument count does not match the resolved signature');
Check::check(($error instanceof \diagnostics\Source_Error) && ($error->path === $main) && ($error->start === 13),
    'Direct body worker rejects the first unsupported argument');
Check::rejects(static fn() => $session->compile($manifest, $output), 'Call argument count does not match the resolved signature');

// A nested target change selects an unchanged caller through existing dependencies.
Check::edit($answer, 'function renamed(): int { return value(); }');
$update = new Update_Context();
$sources = \Step_Test::run(new \read_sources\Source_Discovery($input->inputs->manifest, $input->inputs->sources));
$lexical = Phases::run_tokenization($sources, $input->inputs->tokens, $update);
$frontends = Phases::run_parsing($lexical->sources, $lexical->tokens, $input->inputs->frontends, $update);
$changed = \Step_Test::run(new \collect_symbols\Declaration_Collector($input->symbols, $lexical->sources, $frontends, false))->current;
$selected = \Step_Test::select(\resolve_symbols\Symbol_Resolver::class, $changed, $input->names, false, $baseline->types->catalog);
Check::check(($changed->symbol_by_id($main_id) === $input->symbols->symbol_by_id($main_id))
    && in_array($changed->symbol_by_id($main_id), $selected, true), 'Unchanged AST caller is selected when its nested target disappears');
$error = Check::rejects(static fn() => Phases::run_symbols($changed, $input->names, $update, $baseline->types->catalog), "Unknown function 'answer'");
Check::check(($error instanceof \diagnostics\Source_Error) && ($error->path === $main) && ($error->start === 13), 'Nested callee diagnostic uses its own source span');
Check::edit($answer, $original_answer);
Check::check(Body_Test_Stages::prepare($baseline, $input)->names->for_symbol($main_id) === $input->names->for_symbol($main_id),
    'Repair preserves an unchanged caller whose nested target identity is restored');

// Editing argument content replaces bindings, not the old AST-backed result.
Check::edit($main, '$x int = 42; return value(answer($x));');
$edited = Body_Test_Stages::prepare($baseline, $input);
Check::rejects(static fn() => (new \resolve_symbols\Resolution_Join($input->names, $edited->symbols, [$edited->symbols->symbol_by_id($main_id)], $baseline->types->catalog))->join([$input->names->for_symbol($main_id)]), 'stale');
Check::check($edited->names->for_symbol($main_id) !== $input->names->for_symbol($main_id), 'Argument edit replaces its callable result');

foreach ([
        ['return value(answer($missing));', '$missing', 'Unknown local'],
        ['return value(missing());', 'missing', "Unknown function 'missing'"],
        ['$x int = 1; { $x int = value(answer($x)); } return 42;', '$x));', 'cannot read itself'],
        ['return value($later); $later int = 1;', '$later', 'Unknown local'],
        ['{ $hidden int = 1; } return value(answer($hidden));', '$hidden));', 'Unknown local'],
        ['$X int = 1; return value($x);', '$x', 'Unknown local'],
        ['$x int = 1; function leak(): int { return value($x); } return 42;', '$x);', 'Unknown local'],
    ] as [$text, $anchor, $reason])
{
    Check::edit($main, $text);
    $error = Check::rejects(static fn() => $session->compile($manifest, $output), $reason);
    Check::check(($error instanceof \diagnostics\Source_Error) && ($error->path === $main)
        && ($error->start === strpos($text, $anchor)), 'Nested argument errors retain the exact failing span');
    Check::check(($session->published === $published) && ($session->generation === $generation)
        && (hash_file('sha256', $output) === $key) && ($baseline->to_json() === $before), 'Failure preserves accepted snapshots and executable');
}
Check::edit($main, 'return 42; value(1);');
Check::rejects(static fn() => $session->compile($manifest, $output), 'Call argument count does not match the resolved signature');

// Deep and wide argument syntax uses O(depth) scratch and linear output rows.
Check::edit($main, '$x int = 42; return ' . str_repeat('value(', 1024) . '$x' . str_repeat(')', 1024) . ';');
$deep_symbols = Check::project($baseline);
$deep = Phases::run_symbols($deep_symbols, new Resolution_Set(), new Update_Context(), $baseline->types->catalog)->for_symbol($main_id);
Check::check((count($deep->bindings) === 1024) && (count($deep->local_bindings) === 1), 'Deep calls do not use recursive name-resolution calls');
Check::edit($main, '$x int = 42; return value(' . implode(',', array_fill(0, 2000, '$x')) . ');');
$wide_symbols = Check::project($baseline);
$wide = Phases::run_symbols($wide_symbols, new Resolution_Set(), new Update_Context(), $baseline->types->catalog)->for_symbol($main_id);
Check::check((count($wide->bindings) === 1) && (count($wide->local_bindings) === 2000)
    && (count($wide->locals) === 1), 'Wide arguments share the same local without copying declaration data');

Check::edit($main, $original_main);
$repair = $session->compile($manifest, $output);
Check::check(($repair->completed) && (!$repair->inputs->context->full_rebuild) && ($baseline->to_json() === $before),
    'Failure followed by repair resumes the common pipeline');
$process = proc_open([$output], [0 => ['file', '/dev/null', 'r'], 1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
Check::check(is_resource($process) && (proc_close($process) === 42), 'Repaired executable still returns 42');
echo "argument resolution ok: nested calls/locals, scopes, dependencies, workers/joins, reuse, exports, deep/wide traversal and downstream gates\n";
