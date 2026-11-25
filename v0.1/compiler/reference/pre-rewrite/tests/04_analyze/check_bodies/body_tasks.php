<?php
declare(strict_types=1);

require_once __DIR__ . '/../../support/body_support.php';

use Body_Test_Stages as Check;
use check_bodies\Body_Checker;
use check_bodies\Body_Set;
use check_bodies\body_check_task;
use analyze_lifetimes\Lifetime_Analyzer;
use analyze_lifetimes\Lifetime_Set;
use collect_symbols\symbol_kind;

$manifest = '../fixtures/three_files/project.json';
$main = realpath('../fixtures/three_files/src/main.phs');
$value_path = realpath('../fixtures/three_files/src/nested/value.phs');
Check::edit($main, 'return id(answer());
function id($v int): int { $x int = $v; { $x = $x; } return $x; }
function spare(): int { return 7; }');
$session = new \compile\Compiler_Session();
$output = getcwd() . '/program';
$baseline = $session->compile($manifest, $output);
$symbols = $baseline->symbols->current;
$names = $baseline->resolutions;
$types = $baseline->types;
$fixed = serialize($baseline);
$id = $symbols->find_symbol('id', '', symbol_kind::function_symbol);
$spare = $symbols->find_symbol('spare', '', symbol_kind::function_symbol);
$value = $symbols->find_symbol('value', '', symbol_kind::function_symbol);

// Select fixed inputs once. Compose the two existing workers before either join,
// in reverse task order, without passing project name sets into either worker.
$tasks = \Step_Test::select(Body_Checker::class, $symbols, $names, $types, new Body_Set(), true);
$bodies = [];
$analyses = [];
foreach (array_reverse($tasks) as $task)
{
    Check::check(($task->owner === $symbols->symbol_by_id($task->owner->symbol_id))
        && ($task->names === $names->for_symbol($task->owner->symbol_id)) && ($task->types === $types),
        'Selected tasks share the exact owner, callable bindings and type snapshot');
    $body = (new \check_bodies\Body_Worker($task))->check();
    $analysis = (new \analyze_lifetimes\Lifetime_Worker($body))->analyze();
    Check::check($analysis->body === $body, 'Lifetime work consumes the completed private checked body');
    $bodies[] = $body;
    $analyses[] = $analysis;
}
$joined = (new \check_bodies\Body_Join($symbols, $names, $types, new Body_Set(), $tasks))->join($bodies);
$lifetimes = (new \analyze_lifetimes\Lifetime_Join($joined, new Lifetime_Set(), $bodies))->join($analyses);
Check::check(($joined->to_json() === $baseline->bodies->to_json())
    && ($lifetimes->to_json() === $baseline->lifetimes->to_json()) && (serialize($baseline) === $fixed),
    'Composed workers and separate joins match the ordinary pipeline and preserve fixed inputs');
Check::check((\Step_Test::select(Body_Checker::class, $symbols, $names, $types, $joined, false) === [])
    && (\Step_Test::select(Lifetime_Analyzer::class, $joined, $lifetimes, false) === [])
    && (count(\Step_Test::select(Lifetime_Analyzer::class, $joined, new Lifetime_Set(), false)) === count($bodies)),
    'Checking reuse and missing lifetime work remain independently selectable');

// Standalone callers can assemble a fixed task directly. Results retain their
// actual semantic inputs, not the transient task or its complete type snapshot.
$single_types = clone $types;
$type_reference = WeakReference::create($single_types);
$single = new body_check_task($symbols->symbol_by_id($id), $names->for_symbol($id), $single_types);
$reference = WeakReference::create($single);
$checked = (new \check_bodies\Body_Worker($single))->check();
$analyzed = (new \analyze_lifetimes\Lifetime_Worker($checked))->analyze();
unset($single, $single_types);
Check::check(($reference->get() === null) && ($type_reference->get() === null) && ($analyzed->body === $checked)
    && ($checked->names === $names->for_symbol($id)) && ($checked->local_types === $types->locals_for($id)),
    'Checked/lifetime facts share their dependencies without retaining the task or complete type snapshot');

// Same-file AST identity alone is insufficient: callable identity and root scope
// must also match, including bodies without any locals or variable uses.
$wrong_names = new body_check_task($symbols->symbol_by_id($spare), $names->for_symbol($id), $types);
Check::rejects(static fn() => (new \check_bodies\Body_Worker($wrong_names))->check(), 'current callable name bindings');
$wrong_root = new \resolve_symbols\Symbol_Resolution($spare, $names->for_symbol($spare)->syntax,
    scopes: $names->for_symbol($id)->scopes);
Check::rejects(static fn() => (new \check_bodies\Body_Worker(new body_check_task($symbols->symbol_by_id($spare), $wrong_root, $types)))->check(),
    'current callable name bindings');
Check::rejects(static fn() => \Step_Test::select(Body_Checker::class, $symbols, new \resolve_symbols\Resolution_Set(), $types, new Body_Set(), true),
    'current callable name bindings');

// Even equivalent type snapshots are different task provenance. Joins must not
// accept a task captured for another snapshot merely because the result matches.
$other_types = clone $types;
$other_task = new body_check_task($symbols->symbol_by_id($spare), $names->for_symbol($spare), $other_types);
$other_body = (new \check_bodies\Body_Worker($other_task))->check();
Check::rejects(static fn() => (new \check_bodies\Body_Join($symbols, $names, $types, $joined, [$other_task]))->join([$other_body]),
    'stale body task');
Check::rejects(static fn() => (new \check_bodies\Body_Join($symbols, $names, $types, $joined, [$wrong_names]))->join([$other_body]),
    'stale body task');

// An old task remains readable for its original snapshot, but cannot be joined
// into a replacement project. Native body edits keep the ordinary stage order.
Check::edit($value_path, 'function value(): int { return 43; }');
$edited = $session->compile($manifest, $output);
$selected = \Step_Test::select(Body_Checker::class, $edited->symbols->current, $edited->resolutions, $edited->types, $baseline->bodies, false);
Check::check((!$edited->inputs->context->full_rebuild) && (count($selected) === 1)
    && ($selected[0]->owner->symbol_id === $value)
    && ($edited->bodies->for_symbol($id) === $baseline->bodies->for_symbol($id))
    && ($edited->lifetimes->for_symbol($id) === $baseline->lifetimes->for_symbol($id)),
    'A body edit selects one fixed task and reuses unaffected checked and lifetime results');
Check::rejects(static fn() => (new \check_bodies\Body_Join($edited->symbols->current, $edited->resolutions, $edited->types, $baseline->bodies, $tasks))->join($bodies), 'stale body task');
Check::rejects(static fn() => (new \check_bodies\Body_Join($edited->symbols->current, $edited->resolutions, $edited->types, $baseline->bodies, $selected))->join([$baseline->bodies->for_symbol($value)]), 'stale body result');
Check::check(serialize($baseline) === $fixed, 'Replacement preserves the original source and semantic snapshots');
$process = proc_open([$output], [0 => ['file', '/dev/null', 'r'], 1 => ['file', '/dev/null', 'w'],
    2 => ['file', '/dev/null', 'w']], $pipes);
Check::check(is_resource($process) && (proc_close($process) === 43), 'The ordinary native pipeline executes the updated composed calls');

echo "body tasks ok: fixed callable inputs, composed workers, separate joins/reuse, provenance, task release and native update\n";
