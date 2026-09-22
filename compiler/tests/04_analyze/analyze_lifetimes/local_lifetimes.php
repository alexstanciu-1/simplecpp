<?php
declare(strict_types=1);
require_once __DIR__ . '/../../support/body_support.php';

use analyze_lifetimes\Lifetime_Analyzer as Analyzer;
use analyze_lifetimes\Lifetime_Set;
use analyze_lifetimes\local_end;
use analyze_lifetimes\lifetime_end;
use compile\Phases;
use compile\Update_Context;
use check_bodies\Checked_Body;
use check_bodies\typed_value;
use check_bodies\value_kind;
use check_bodies\typed_statement;
use check_bodies\statement_kind;

class Local_Lifetimes_Test extends Body_Test_Stages
{
    public static function analyze(\check_bodies\Body_Set $bodies, ?Lifetime_Set $previous = null, bool $full = false): Lifetime_Set
    {
        $update = new Update_Context();
        $update->full_rebuild = $full;
        return Phases::run_lifetimes($bodies, $previous ?? new Lifetime_Set(), $update);
    }

    public static function changed(Checked_Body $body, ?array $values = null, ?array $statements = null,
        ?array $dependencies = null, ?array $scopes = null): Checked_Body
    {
        return new Checked_Body($body->owner, $body->names, $values ?? $body->values, $body->calls,
            $statements ?? $body->statements, $body->falls_through, $dependencies ?? $body->type_dependencies,
            $body->signature_dependencies, $body->local_types, $scopes ?? $body->scopes, $body->arguments, $body->blocks);
    }
}

$path = '../fixtures/three_files/project.json';
$main = realpath('../fixtures/three_files/src/main.phs');
$value = realpath('../fixtures/three_files/src/nested/value.phs');
$original_main = file_get_contents($main);
$original_value = file_get_contents($value);
Local_Lifetimes_Test::edit($value, $original_value . "\nfunction noop(): void { return; }");
$session = new \compile\Compiler_Session();
$baseline = $session->compile($path);
$before = serialize($baseline);
$source = '$outer int = answer(); { $inner int = $outer; $inner = $inner; } { $outer int = 43; $other int = $outer; return $outer; $dead int = 44; } $unreached int = 9; return 42;';
Local_Lifetimes_Test::edit($main, $source);
$input = Local_Lifetimes_Test::prepare($baseline);
$bodies = Local_Lifetimes_Test::bodies($input, $baseline->bodies);
$main_id = $input->types->entry->symbol->symbol_id;
$body = $bodies->for_symbol($main_id);
$fixed = serialize([$input, $bodies, $baseline]);
$tasks = \Step_Test::select(\analyze_lifetimes\Lifetime_Analyzer::class, $bodies, $baseline->lifetimes, false);
Local_Lifetimes_Test::check(count($tasks) === 1, 'Only changed checked bodies need lifetime work');
$results = [];
foreach (array_reverse($tasks) as $task) {
    $results[] = (new \analyze_lifetimes\Lifetime_Worker($task))->analyze();
}
$analysis = (new \analyze_lifetimes\Lifetime_Join($bodies, $baseline->lifetimes, $tasks))->join($results);
$a = $analysis->for_symbol($main_id);
Local_Lifetimes_Test::check(($a->body === $body) && ($a->reachable_statement_count === 6) && (!$a->falls_through)
    && (count($a->lifetimes) === 6) && (count($body->values) === 9), 'Analyze only the reachable prefix, retaining checked unreachable source separately');
Local_Lifetimes_Test::check(array_map(static fn($l) => [$l->local_id, $l->initialized_statement_id, $l->end_after_statement, $l->end->value], $a->local_lifetimes)
    === [[2, 2, 3, 'scope_exit'], [4, 5, 6, 'return_exit'], [3, 4, 6, 'return_exit'], [1, 1, 6, 'return_exit']],
    'Scope exits and return unwind locals in reverse initialization order; assignment does not create a new binding lifetime');
Local_Lifetimes_Test::check(array_map(static fn($l) => $l->end->value, $a->lifetimes)
    === ['local_copy', 'local_copy', 'assignment_source', 'local_copy', 'local_copy', 'return_copy'],
    'Temporary consumers distinguish initialization, live assignment and return copying');
Local_Lifetimes_Test::check(($a->local_for(1) === $a->local_lifetimes[3]) && ($a->local_for(5) === null) && ($a->local_for(6) === null),
    'Owned lookup shares reached binding records and reports no lifetime for unreached declarations');
Local_Lifetimes_Test::rejects(static fn() => $a->local_for(0), 'Missing resolved local');
Local_Lifetimes_Test::check(serialize([$input, $bodies, $baseline]) === $fixed, 'Workers and join preserve every fixed input');
$export = $analysis->to_json();
$rows = json_decode($export, true, 512, JSON_THROW_ON_ERROR);
$entry = array_values(array_filter($rows, static fn($r) => $r['symbol_id'] === $main_id))[0];
Local_Lifetimes_Test::check(($entry['local_lifetimes'][0]['end'] === 'scope_exit') && ($entry['lifetimes'][5]['end'] === 'return_copy')
    && ($analysis->to_json() === $export), 'Debug exports distinguish binding ends from temporary copies');
$warm_inputs = Local_Lifetimes_Test::prepare($baseline, $input);
$warm_bodies = Local_Lifetimes_Test::bodies($warm_inputs, $bodies);
Local_Lifetimes_Test::check((\Step_Test::select(\analyze_lifetimes\Lifetime_Analyzer::class, $warm_bodies, $analysis, false) === [])
    && (Local_Lifetimes_Test::analyze($warm_bodies, $analysis)->for_symbol($main_id) === $a), 'Warm input/body reuse shares the complete lifetime result');
$all_tasks = \Step_Test::select(\analyze_lifetimes\Lifetime_Analyzer::class, $bodies, new Lifetime_Set(), true);
$all_results = [];
foreach (array_reverse($all_tasks) as $task) {
    $all_results[] = (new \analyze_lifetimes\Lifetime_Worker($task))->analyze();
}
Local_Lifetimes_Test::check(((new \analyze_lifetimes\Lifetime_Join($bodies, new Lifetime_Set(), $all_tasks))->join($all_results)->to_json() === $export)
    && (Local_Lifetimes_Test::analyze($bodies, $analysis, true)->to_json() === $export), 'Reversed workers and full selection produce identical facts through the same joins');
$fresh = Local_Lifetimes_Test::prepare($baseline);
Local_Lifetimes_Test::check(Local_Lifetimes_Test::analyze(Local_Lifetimes_Test::bodies($fresh))->to_json() === $export,
    'Fresh frontend/type/body work produces equivalent lifetime facts');
Local_Lifetimes_Test::rejects(static fn() => (new \analyze_lifetimes\Lifetime_Join($bodies, $analysis, $tasks))->join([]), 'Incomplete');
Local_Lifetimes_Test::rejects(static fn() => (new \analyze_lifetimes\Lifetime_Join($bodies, $analysis, $tasks))->join([...$results, $results[0]]), 'duplicate');

// Malformed checked inputs must not bypass live-local and copying requirements.
$values = $body->values;
$first = $values[0];

// Replace the first statement's expression with a read of its not-yet-live destination.
// Use a non-call statement so its original producing call cannot mask the read.
$values[] = new typed_value($first->source_node_id, $first->type_id, value_kind::local_read, new \check_bodies\place(1));
$statements = $body->statements;
$s = $statements[0];
$statements[0] = new typed_statement($s->source_node_id, $s->kind, count($values), 0, 0, $s->scope_id, $s->target);
Local_Lifetimes_Test::rejects(static fn() => (new \analyze_lifetimes\Lifetime_Worker(Local_Lifetimes_Test::changed($body, $values, $statements)))->analyze(), 'Read requires a live initialized local');
$values = $body->values;
$v = $values[5];
$values[5] = new typed_value($v->source_node_id, $v->type_id, value_kind::local_read, new \check_bodies\place(2));
Local_Lifetimes_Test::rejects(static fn() => (new \analyze_lifetimes\Lifetime_Worker(Local_Lifetimes_Test::changed($body, $values)))->analyze(), 'Read requires a live initialized local');
$statements = $body->statements;
$s = $statements[4];
$statements[4] = new typed_statement($s->source_node_id, statement_kind::assignment, $s->value_id, $s->call_start, $s->call_count,
    $s->scope_id, new \check_bodies\place(2));
Local_Lifetimes_Test::rejects(static fn() => (new \analyze_lifetimes\Lifetime_Worker(Local_Lifetimes_Test::changed($body, statements: $statements)))->analyze(), 'Assignment requires a live initialized local');
$dependencies = $body->type_dependencies;
$dependencies[$first->type_id] = $input->types->types->type_by_id($input->types->types->find_type('void'));
Local_Lifetimes_Test::rejects(static fn() => (new \analyze_lifetimes\Lifetime_Worker(Local_Lifetimes_Test::changed($body, dependencies: $dependencies)))->analyze(), 'Value type has no lifetime contract');
$scopes = $body->scopes;
$scopes[1] = new \check_bodies\typed_scope(99, 0);
Local_Lifetimes_Test::rejects(static fn() => (new \analyze_lifetimes\Lifetime_Worker(Local_Lifetimes_Test::changed($body, scopes: $scopes)))->analyze(), 'outside its scope range');
Local_Lifetimes_Test::check(serialize([$input, $bodies, $baseline]) === $fixed, 'Invalid inputs cannot mutate retained checked or analyzed state');

Local_Lifetimes_Test::edit($main, str_replace('43', '45', $source));
$edited = Local_Lifetimes_Test::prepare($baseline, $input);
$edited_bodies = Local_Lifetimes_Test::bodies($edited, $bodies);
Local_Lifetimes_Test::rejects(static fn() => (new \analyze_lifetimes\Lifetime_Join($edited_bodies, $analysis, [$edited_bodies->for_symbol($main_id)]))->join([$a]), 'stale');
$edited_analysis = Local_Lifetimes_Test::analyze($edited_bodies, $analysis);
Local_Lifetimes_Test::check(($edited_analysis->for_symbol($main_id) !== $a) && ($analysis->to_json() === $export), 'Replacement retires the old work without editing old facts');
$compiled = $session->compile($path);
Local_Lifetimes_Test::check((count($compiled->lowered->for_symbol($main_id)->slots) === 4)
    && (serialize($baseline) === $before), 'Lowering consumes only reached local lifetimes and preserves accepted snapshots');
Local_Lifetimes_Test::edit($main, '$x int = noop(); return 42;');
Local_Lifetimes_Test::rejects(static fn() => $session->compile($path), 'Initialization expression produces no value');
Local_Lifetimes_Test::edit($main, '$x int = 42; noop(); $x = $x; return $x;');
$repair = Local_Lifetimes_Test::prepare($baseline, $edited);
$repair_bodies = Local_Lifetimes_Test::bodies($repair, $edited_bodies);
$ra = Local_Lifetimes_Test::analyze($repair_bodies, $edited_analysis)->for_symbol($main_id);
Local_Lifetimes_Test::check((count($ra->lifetimes) === 3) && (count($ra->local_lifetimes) === 1)
    && ($ra->local_for(1)->end_after_statement === 4), 'Void calls create no value lifetime; self-assignment reads before overwriting; repair uses common stages');

// Fallthrough closes inner scopes and then root locals, with no fabricated return.
Local_Lifetimes_Test::edit($main, 'return 42; function finish(): void { $a int = 1; { $b int = 2; } $a = $a; }');
$fall = Local_Lifetimes_Test::prepare($baseline, $repair);
$fall_bodies = Local_Lifetimes_Test::bodies($fall, $repair_bodies);
$fall_analysis = Local_Lifetimes_Test::analyze($fall_bodies, $edited_analysis);
$finish_id = $fall->symbols->find_symbol('finish', '', \collect_symbols\symbol_kind::function_symbol);
$finish = $fall_analysis->for_symbol($finish_id);
Local_Lifetimes_Test::check(($finish->falls_through) && (array_map(static fn($l) => [$l->local_id, $l->end_after_statement, $l->end], $finish->local_lifetimes)
        === [[2, 2, local_end::scope_exit], [1, 3, local_end::scope_exit]]), 'Void fallthrough closes lexical lifetimes at actual block boundaries');
Local_Lifetimes_Test::check($fall_analysis->for_symbol($main_id)->local_lifetimes === [], 'Removed local declarations have no current runtime facts');
Local_Lifetimes_Test::edit($main, 'return 42;');
$removed = Local_Lifetimes_Test::prepare($baseline, $fall);
Local_Lifetimes_Test::check(Local_Lifetimes_Test::analyze(Local_Lifetimes_Test::bodies($removed, $fall_bodies), $fall_analysis)->for_symbol($finish_id) === null,
    'Removed callable lifetime results are excluded independently of selection');

// Deep nesting and many writes do not multiply the binding lifetime dataset.
Local_Lifetimes_Test::edit($main, '$x int = 42;' . str_repeat('{', 128) . str_repeat('$x = $x;', 1000) . str_repeat('}', 128) . 'return $x;');
$large = Local_Lifetimes_Test::prepare($baseline, $removed);
$la = Local_Lifetimes_Test::analyze(Local_Lifetimes_Test::bodies($large))->for_symbol($main_id);
Local_Lifetimes_Test::check((count($la->local_lifetimes) === 1) && (count($la->lifetimes) === 1002) && ($la->local_for(1)->end_after_statement === 1002),
    'Many assignments retain one binding lifetime and one temporary per evaluation');

// Scalar-only blocks already fit the common lowering path. Prove native behavior.
Local_Lifetimes_Test::edit($main, '{ { return answer(); } }');
$binary = getcwd() . '/nested-scalar-program';
$native = $session->compile($path, $binary);
$process = proc_open([$binary], [0 => ['file', '/dev/null', 'r'], 1 => ['file', '/dev/null', 'w'], 2 => ['file', '/dev/null', 'w']], $pipes);
Local_Lifetimes_Test::check(is_resource($process) && (proc_close($process) === 42) && ($native->completed), 'Nested scalar return compiles and executes through the existing native path');
Local_Lifetimes_Test::edit($main, $original_main);
Local_Lifetimes_Test::check($session->compile($path)->llvm->ir_by_file() === $baseline->llvm->ir_by_file(), 'Original program remains compilable after repair');
echo "local lifetimes ok: initialization, copy reads/writes, lexical exits, return unwind, unreachable exclusion, contracts, fixed workers, reuse, repair and native scalar blocks\n";
