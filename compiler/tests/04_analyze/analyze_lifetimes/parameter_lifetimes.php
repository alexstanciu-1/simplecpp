<?php
declare(strict_types=1);
require_once __DIR__ . '/../../support/body_support.php';

use Body_Test_Stages as Check;
use analyze_lifetimes\Lifetime_Analyzer as Analyzer;
use analyze_lifetimes\Lifetime_Set;
use analyze_lifetimes\lifetime_end;
use analyze_lifetimes\local_end;
use analyze_lifetimes\Analyzed_Body;
use check_bodies\Checked_Body;
use check_bodies\typed_argument;
use check_bodies\typed_value;
use check_bodies\value_kind;
use collect_symbols\symbol_kind;

class Parameter_Lifetimes_Test
{
    public static function analyze(\check_bodies\Body_Set $bodies, ?Lifetime_Set $previous = null, bool $full = false): Lifetime_Set
    {
        $update = new \compile\Update_Context();
        $update->full_rebuild = $full;
        return \compile\Phases::run_lifetimes($bodies, $previous ?? new Lifetime_Set(), $update);
    }

    public static function changed(Checked_Body $body, ?array $arguments = null, ?array $values = null, ?array $dependencies = null): Checked_Body
    {
        return new Checked_Body($body->owner, $body->names, $values ?? $body->values, $body->calls, $body->statements,
            $body->falls_through, $dependencies ?? $body->type_dependencies, $body->signature_dependencies,
            $body->local_types, $body->scopes, $arguments ?? $body->arguments, $body->blocks);
    }
}

$manifest = '../fixtures/three_files/project.json';
$session = new \compile\Compiler_Session();
$output = getcwd() . '/program';
$baseline = $session->compile($manifest, $output);
$before = $baseline->to_json();
$observed = $session->observed;
$published = $session->published;
$generation = $session->generation;
$key = hash_file('sha256', $output);
$root = $baseline->inputs->manifest->directory;
$main = $root . '/src/main.phs';
$value = $root . '/src/nested/value.phs';
$original_main = file_get_contents($main);
$original_value = file_get_contents($value);
$definitions = $original_value . '
function pair($a int, $b int): int { $copy int = $a; { $a int = $b; sink($a); } $a = id($a); return $copy; $dead int = 9; }
function id($v int): int { return $v; }
function sink($v int): void {}
function bare($v int): void { return; }
function finish($v int): void { { $c int = $v; } $v = $v; }
function unused($v uint32): void {}';
$source = '$x int = 10; return pair($x, id(id(42))); sink(9);';
Check::edit($value, $definitions);
Check::edit($main, $source);
$input = Check::prepare($baseline);
$bodies = Check::bodies($input, $baseline->bodies);
$main_id = $input->types->entry->symbol->symbol_id;
$pair_id = $input->symbols->find_symbol('pair', '', symbol_kind::function_symbol);
$sink_id = $input->symbols->find_symbol('sink', '', symbol_kind::function_symbol);
$bare_id = $input->symbols->find_symbol('bare', '', symbol_kind::function_symbol);
$finish_id = $input->symbols->find_symbol('finish', '', symbol_kind::function_symbol);
$answer_id = $input->symbols->find_symbol('answer', '', symbol_kind::function_symbol);
$fixed = serialize([$input, $bodies, $baseline]);
$tasks = \Step_Test::select(\analyze_lifetimes\Lifetime_Analyzer::class, $bodies, $baseline->lifetimes, false);
$results = array_map(static fn($task) => (new \analyze_lifetimes\Lifetime_Worker($task))->analyze(), array_reverse($tasks));
$all = (new \analyze_lifetimes\Lifetime_Join($bodies, $baseline->lifetimes, $tasks))->join($results);
$a = $all->for_symbol($main_id);
$pair = $all->for_symbol($pair_id);
Check::check((serialize([$input, $bodies, $baseline]) === $fixed)
    && ($all->for_symbol($answer_id) === $baseline->lifetimes->for_symbol($answer_id)), 'Independent workers preserve fixed inputs and share unaffected analyses');
Check::check(array_map(static fn($l) => [$l->value_id, $l->statement_id, $l->end->value, $l->consumer_id], $a->lifetimes)
    === [[1,1,'local_copy',0], [3,2,'argument_copy',1], [4,2,'argument_copy',2], [2,2,'argument_copy',3], [5,2,'argument_copy',3], [6,2,'return_copy',0]],
    'Earlier argument read remains live through nested calls and ends only at its consumer; results copy once into the next call');
Check::check(($a->reachable_statement_count === 2) && (count($a->lifetimes) === 6) && (count($a->body->values) === 7),
    'Unreachable call arguments receive no temporary lifetimes');
Check::check(array_map(static fn($l) => [$l->local_id,$l->initialized_statement_id,$l->end_after_statement,$l->end->value], $pair->local_lifetimes)
    === [[4,2,3,'scope_exit'],[3,1,5,'return_exit'],[2,0,5,'return_exit'],[1,0,5,'return_exit']],
    'Shadowed locals close before root bindings; parameter assignment preserves entry initialization and return unwinds in reverse order');
Check::check(($pair->local_for(5) === null) && ($pair->local_for(1)->initialized_statement_id === 0),
    'Unreachable declarations remain absent while incoming parameters are live at entry');
$sink = $all->for_symbol($sink_id);
$bare = $all->for_symbol($bare_id);
$finish = $all->for_symbol($finish_id);
Check::check(($sink->falls_through) && ($sink->lifetimes === []) && ($sink->local_for(1)->initialized_statement_id === 0)
    && ($sink->local_for(1)->end_after_statement === 0) && ($sink->local_for(1)->end === local_end::scope_exit),
    'Empty void body closes incoming bindings at boundary zero');
Check::check((!$bare->falls_through) && ($bare->local_for(1)->end === local_end::return_exit) && ($bare->local_for(1)->end_after_statement === 1),
    'Bare return closes incoming bindings');
Check::check(($finish->falls_through) && (array_map(static fn($l) => [$l->local_id,$l->initialized_statement_id,$l->end_after_statement], $finish->local_lifetimes)
        === [[2,1,1],[1,0,2]]), 'Void fallthrough and parameter self-assignment reuse ordinary local scope handling');
$export = json_decode($all->to_json(), true, 512, JSON_THROW_ON_ERROR);
$row = array_values(array_filter($export, static fn($r) => $r['symbol_id'] === $main_id))[0];
Check::check($row['lifetimes'][3]['consumer_id'] === 3, 'Debug export identifies the actual consuming call');
Check::check((\Step_Test::select(\analyze_lifetimes\Lifetime_Analyzer::class, $bodies, $all, false) === []) && (Parameter_Lifetimes_Test::analyze($bodies,$all)->for_symbol($main_id) === $a),
    'Unchanged checked bodies select no lifetime work');
Check::check((Parameter_Lifetimes_Test::analyze($bodies,$all,true)->to_json() === $all->to_json())
    && (Parameter_Lifetimes_Test::analyze(Check::bodies(Check::prepare($baseline)))->to_json() === $all->to_json()),
    'Full and fresh stages use the same analysis path and produce equal facts');
Check::rejects(static fn() => (new \analyze_lifetimes\Lifetime_Join($bodies, $all, $tasks))->join([]), 'Incomplete');
Check::rejects(static fn() => (new \analyze_lifetimes\Lifetime_Join($bodies, $all, $tasks))->join([...$results,$results[0]]), 'duplicate');

// Lowering accepts these facts once the current parameter contracts are prepared.
$backend = \Step_Test::run(new \prepare_backend\LLVM_Backend(new \prepare_backend\LLVM_Toolchain(), $input->types, null, true));
foreach ([$a, $sink] as $analysis) {
    $plan = (new \lower\Lowering_Worker(new \lower\lowering_input($analysis, $backend)))->lower();
    Check::check($plan->input->analysis === $analysis, 'Lowering consumes the exact parameter/argument lifetime result');
}

// Corrupt consumer relationships cannot cause a second evaluation or consume a dead value.
$body = $a->body;
$arguments = $body->arguments;
$arguments[1] = $arguments[0];
Check::rejects(static fn() => (new \analyze_lifetimes\Lifetime_Worker(Parameter_Lifetimes_Test::changed($body,arguments:$arguments)))->analyze(), 'more than once');
$arguments = $body->arguments;
$arg = $arguments[0];
$arguments[0] = new typed_argument(6,$arg->parameter_type_id);
Check::rejects(static fn() => (new \analyze_lifetimes\Lifetime_Worker(Parameter_Lifetimes_Test::changed($body,arguments:$arguments)))->analyze(), 'cyclic');
$arguments = $body->arguments;
$arguments[0] = new typed_argument($arg->value_id,$input->types->types->find_type('uint32'));
Check::rejects(static fn() => (new \analyze_lifetimes\Lifetime_Worker(Parameter_Lifetimes_Test::changed($body,arguments:$arguments)))->analyze(), 'argument conversion');
$values = $body->values;
$v = $values[1];
$values[1] = new typed_value($v->source_node_id,$v->type_id,value_kind::local_read,new \check_bodies\place(999));
Check::rejects(static fn() => (new \analyze_lifetimes\Lifetime_Worker(Parameter_Lifetimes_Test::changed($body,values:$values)))->analyze(), 'Read requires a live initialized local');
$dependencies = $pair->body->type_dependencies;
$dependencies[$input->types->integer_literal_type()] = $input->types->types->type_by_id($input->types->types->find_type('void'));
Check::rejects(static fn() => (new \analyze_lifetimes\Lifetime_Worker(Parameter_Lifetimes_Test::changed($pair->body,dependencies:$dependencies)))->analyze(), 'Value type has no lifetime contract');
$bad_local = new \analyze_lifetimes\local_lifetime(3,0,5,local_end::return_exit);
Check::rejects(static fn() => new Analyzed_Body($pair->body, $pair->lifetimes, 5, false, [$bad_local]), 'entry initialization');
Check::check(serialize([$input,$bodies,$baseline]) === $fixed, 'Malformed worker inputs never mutate accepted snapshots');

// Argument-only edit replaces one body; removed parameter contributions disappear with their owner.
Check::edit($main,str_replace('42','43',$source));
$edited = Check::prepare($baseline,$input);
$edited_bodies = Check::bodies($edited,$bodies);
Check::rejects(static fn() => (new \analyze_lifetimes\Lifetime_Join($edited_bodies, $all, [$edited_bodies->for_symbol($main_id)]))->join([$a]), 'stale');
$edited_all = Parameter_Lifetimes_Test::analyze($edited_bodies,$all);
Check::check(($edited_all->for_symbol($main_id) !== $a) && ($edited_all->for_symbol($pair_id) === $pair),
    'Incremental selection replaces only the edited checked owner');
Check::check(($session->observed === $observed) && ($session->published === $published) && ($session->generation === $generation)
    && (hash_file('sha256',$output) === $key) && ($baseline->to_json() === $before), 'Independent stage work preserves the real executable and resident baseline');
Check::edit($main,'return ' . str_repeat('id(',1024) . '42' . str_repeat(')',1024) . ';');
$deep = Parameter_Lifetimes_Test::analyze(Check::bodies(Check::prepare($baseline)))->for_symbol($main_id);
Check::check((count($deep->lifetimes) === 1025) && ($deep->lifetimes[1023]->consumer_id === 1024)
    && ($deep->lifetimes[1024]->end === lifetime_end::return_copy), 'Deep nesting uses an explicit cursor stack and one end per temporary');
$parameters = implode(',',array_map(static fn($i) => '$p'.$i.' int',range(1,1500)));
Check::edit($value,$definitions . ' function wide('.$parameters.'): int { return $p1500; }');
Check::edit($main,'return wide('.implode(',',array_fill(0,1500,'42')).');');
$wide_inputs = Check::prepare($baseline);
$wide = Parameter_Lifetimes_Test::analyze(Check::bodies($wide_inputs));
$wide_id = $wide_inputs->symbols->find_symbol('wide','',symbol_kind::function_symbol);
Check::check((count($wide->for_symbol($main_id)->lifetimes) === 1501) && (count($wide->for_symbol($wide_id)->local_lifetimes) === 1500),
    'Wide calls and incoming parameters retain linear result datasets');
Check::edit($main,$original_main);
Check::edit($value,$original_value);
$removed_inputs = Check::prepare($baseline,$input);
$removed = Parameter_Lifetimes_Test::analyze(Check::bodies($removed_inputs,$bodies),$all);
Check::check($removed->for_symbol($pair_id) === null, 'Deleted callable results are withdrawn through the common join');
$repair = $session->compile($manifest,$output);
Check::check(($repair->completed) && ($baseline->to_json() === $before), 'Failure followed by repair resumes the existing executable subset');
$process = proc_open([$output],[0=>['file','/dev/null','r'],1=>['file','/dev/null','w'],2=>['file','/dev/null','w']],$pipes);
Check::check(is_resource($process) && (proc_close($process) === 42),'Repaired executable returns 42');
echo "parameter lifetimes ok: entry bindings, call consumption, nested argument retention, scope/return exits, purity/reuse, malformed inputs, exports and lowering handoff\n";
