<?php
declare(strict_types=1);
namespace body_project_test;
final class Probe {
    public static function snapshot(\body_project_fixture\Fixture $f): \resolve_types\Type_Resolution {
        for ($i = 0; $i < $f->catalog->size(); $i++) { \resolve_types\Type_Cache::materialize($f->types,$f->catalog->definition_at($i)); }
        $none /** vector<\resolve_types\Callable_Signature> */ = [];
        $old = new \resolve_types\Signature_Set(\type_model\Type_Store::fresh($f->types->context),$none);
        $tasks = \resolve_types\Signature_Selection::select($f->symbols,$f->instances->view(),$f->entry,$f->types,$old,$f->prepared,false);
        $requests /** vector<\resolve_types\Signature_Request> */ = [];
        foreach ($tasks as $task) { $requests[] = \resolve_types\Signature_Resolver::resolve($f->symbols,$f->reader,$task,$f->entry,$f->prepared); }
        $signatures = (new \resolve_types\Signature_Join($f->symbols,$f->reader,$f->types,$old,$tasks,$f->entry,$f->prepared))->join($requests);
        $history = new \resolve_types\Local_Type_Validity();
        $local_tasks = \resolve_types\Local_Type_Resolver::select($f->symbols,$f->reader,$f->types,$history,false,$f->entry);
        $local_requests /** vector<\resolve_types\Local_Type_Request> */ = [];
        foreach ($local_tasks as $task) { $local_requests[] = \resolve_types\Local_Type_Resolver::resolve($f->symbols,$f->reader,$task); }
        $locals = (new \resolve_types\Local_Type_Join($f->symbols,$f->reader,$f->types,$history,$local_tasks,$f->entry,$signatures))->join($local_requests);
        $bindings /** vector<\resolve_symbols\Symbol_Resolution> */ = [];
        for ($i = 0; $i < $f->reader->annotations->names->size(); $i++) { $bindings[] = $f->reader->annotations->names->at($i); }
        $families /** hash<\load_runtime\Family_Preparation_Result,int> */ = [];
        return new \resolve_types\Type_Resolution($f->types,$f->catalog,$f->entry,$signatures,$locals,$f->reader->annotations->names,$f->instances->snapshot($bindings),$families);
    }
    public static function execute(\check_bodies\Body_Plan $plan, bool $reverse): array /** vector<\check_bodies\Checked_Body> */ {
        $results /** vector<\check_bodies\Checked_Body> */ = [];
        for ($i = 0; $i < $plan->task_count(); $i++) {
            $position = $reverse ? $plan->task_count()-1-$i : $i;
            $task = $plan->task_at($position);
            $results[] = \check_bodies\Body_Worker::prepare($task->input,$task->names,$task->types)->check();
        }
        return $results;
    }
    /** Narrow fixture projection for these literal/call/plain-record plans, not the compiler debug serializer. */
    private static function plan_text(\check_bodies\Body_Set $set): string {
        $out = '';
        foreach ($set->bodies() as $body) {
            $out .= $body->callable_id . ':' . $body->value_count() . ':' . $body->statement_count() . ':' . $body->call_count() . ';';
            for ($i = 1; $i < $body->value_count()+1; $i++) {
                $value = $body->value_for($i); $out .= $value->source_node_id . ':' . $value->type_id . ':' . $value->kind . ':';
                if ($value->kind === \check_bodies\VALUE_INTEGER_LITERAL) { $out .= $value->integer_text(); }
                elseif ($value->kind === \check_bodies\VALUE_CALL_RESULT) { $out .= $value->call_id(); }
                elseif (($value->kind === \check_bodies\VALUE_LOCAL_READ) || ($value->kind === \check_bodies\VALUE_LOCAL_BORROW)) {
                    $place = $value->place(); $out .= $place->local_id;
                    for ($p = 0; $p < $place->size(); $p++) { $projection = $place->at($p); $out .= ':' . $projection->kind . ':' . $projection->operand . ':' . $projection->type_id . ':' . $projection->call_end; }
                } elseif ($value->kind !== \check_bodies\VALUE_RECORD_DEFAULT) { throw new \LogicException('Unexpected fixture value'); }
            }
            for ($i = 1; $i < $body->call_count()+1; $i++) {
                $call = $body->call_for($i); $out .= $call->target_callable_id . ':' . $call->result_value_id . ':' . $call->argument_start . ':' . $call->argument_count . ';';
            }
            for ($i = 0; $i < $body->statement_count(); $i++) {
                $row = $body->statement_at($i); $out .= $row->source_node_id . ':' . $row->kind . ':' . $row->value_id . ':' . $row->call_start . ':' . $row->call_count . ':' . $row->scope_id . ';';
            }
            foreach ($body->flow_blocks() as $block) { $out .= $block->statement_start . ':' . $block->statement_count . ':' . $block->scope_id . ':' . $block->end . ':' . $block->first . ':' . $block->second . ';'; }
        }
        return $out;
    }
    private static function dependency_variant(\check_bodies\Checked_Body $body, string $mode): \check_bodies\Checked_Body {
        $values /** vector<\check_bodies\Typed_Value> */ = []; for ($i = 1; $i < $body->value_count()+1; $i++) { $values[] = $body->value_for($i); }
        $calls /** vector<\check_bodies\Typed_Call> */ = []; for ($i = 1; $i < $body->call_count()+1; $i++) { $calls[] = $body->call_for($i); }
        $statements /** vector<\check_bodies\Typed_Statement> */ = []; for ($i = 0; $i < $body->statement_count(); $i++) { $statements[] = $body->statement_at($i); }
        $scopes /** vector<\check_bodies\Typed_Scope> */ = []; for ($i = 1; $i < $body->scope_count()+1; $i++) { $scopes[] = $body->scope_for($i); }
        $arguments /** vector<\check_bodies\Typed_Argument> */ = []; for ($i = 0; $i < $body->argument_count(); $i++) { $arguments[] = $body->argument_at($i); }
        $types = $body->type_dependencies(); $signatures = $body->signature_dependencies(); $own = $body->callable_id;
        if ($mode === 'missing_own_signature') { unset($signatures[$own]); }
        if ($mode === 'unknown_type_dependency') { $types[99999] = new \type_model\Type_Record('unknown','',0,false,null); }
        if ($mode === 'wrong_parameter_dependency') {
            // Called g has one parameter in this fixture.
            $callee = $body->call_for(1)->target_callable_id; $old = $signatures[$callee];
            $parameters /** vector<int> */ = [99999];
            $signatures[$callee] = new \check_bodies\Signature_Dependency($callee,$old->representation_id,$old->representation,$parameters,$old->external,$old->storage);
        }
        return new \check_bodies\Checked_Body($body->input,$body->names,$body->falls_through,$body->local_types,$values,$calls,$statements,$scopes,$arguments,$body->flow_blocks(),$types,$signatures);
    }
    public static function run(string $text): void {
        $cases = json_read($text);
        $program = 'function f(): int { return g(7); } function g($x int): int { return 2; } function unused(): int { return 3; } return 0;';
        for ($ci = 0; $ci < $cases->size(); $ci++) {
            $mode = $cases->at($ci)->member('mode')->text(); $source = $program;
            if ($mode === 'concrete') { $source = 'function f(): int32 { $p Point; return identity<int32>($p->read()); } template<typename T> function identity($x T): T { return $x; } return 0;'; }
            $f = \body_project_fixture\Fixture::prepare($source);
            $snapshot = Probe::snapshot($f); $names = $snapshot->names;
            $empty_rows /** vector<\check_bodies\Checked_Body> */ = []; $empty = new \check_bodies\Body_Set($empty_rows);
            $baseline = \check_bodies\Body_Checker::check($f->symbols,$names,$snapshot,$empty,false)->result();
            if ($baseline->size() !== 4) { throw new \LogicException('Unexpected baseline membership'); }
            $baseline_text = Probe::plan_text($baseline); $valid = true; $error_text = ''; $expected = '';
            $types = $f->types->fork(); $rows /** vector<\resolve_types\Callable_Signature> */ = [];
            $g = 0; $unused = 0;
            if ($mode !== 'concrete') {
                $g = $f->symbols->find_symbol('g',\collect_symbols\SYMBOL_FUNCTION,0,'');
                $unused = $f->symbols->find_symbol('unused',\collect_symbols\SYMBOL_FUNCTION,0,'');
            }
            $integer = $f->types->find_type('int','');
            if ($mode === 'type_dependency') { $types->invalidate_definition($integer); $types->bind_definition($integer,$f->catalog->integer_literal_type); }
            if ($mode === 'unrelated_type') { $types->declare_type('Unrelated','test'); }
            for ($i = 0; $i < $snapshot->callables->size(); $i++) {
                $row = $snapshot->callables->at($i);
                if ($mode === 'missing_called_signature') { if ($row->callable_id === $g) { continue; } }
                if ($mode === 'nonparticipant') { if ($row->callable_id === $unused) { continue; } }
                $change = false;
                if ($mode === 'called_signature') { $change = $row->callable_id === $g; }
                if ($mode === 'unrelated_signature') { $change = $row->callable_id === $unused; }
                if ($change) {
                    $parameters /** vector<int> */ = []; $passing /** vector<int> */ = [];
                    if ($row->callable_id === $g) { $parameters[] = $integer; $passing[] = \type_model\PASS_VALUE; }
                    $shape = $types->intern_signature($types->find_type('int32',''),$parameters,$passing);
                    $row = new \resolve_types\Callable_Signature($row->input,$row->return_annotation_id,$shape,$row->external,$row->storage,$row->receiver_index);
                }
                $rows[] = $row;
            }
            $locals /** vector<\resolve_types\Local_Types> */ = [];
            foreach ($snapshot->body_signatures() as $signature) { $local = $snapshot->locals_for($signature->callable_id); if ($local !== null) { if ($mode === 'missing_called_signature') { if ($signature->callable_id === $g) { continue; } } $locals[] = $local; } }
            $families /** hash<\load_runtime\Family_Preparation_Result,int> */ = [];
            $current = new \resolve_types\Type_Resolution($types,$f->catalog,$f->entry,new \resolve_types\Signature_Set($types,$rows),$locals,$names,$snapshot->instances,$families);
            $before_types = $types->type_count(); $before_shapes = $types->representation_count(); $before_members = $types->member_count();
            if ($mode === 'duplicate_set') { $expected = 'Duplicate checked body'; }
            if ($mode === 'duplicate_result') { $expected = 'Duplicate body result'; }
            if ($mode === 'missing_result') { $expected = 'Incomplete body phase'; }
            if ($mode === 'unselected_result') { $expected = 'Unexpected body result'; }
            if ($mode === 'foreign_result') { $expected = 'Stale body result'; }
            if ($mode === 'wrong_names_owner') { $expected = 'Body plan requires the type snapshot name owner'; }
            if ($mode === 'foreign_symbols') { $expected = 'Stale body declaration'; }
            try {
                if ($mode === 'duplicate_set') { $duplicated /** vector<\check_bodies\Checked_Body> */ = [$baseline->at(0),$baseline->at(0)]; $bad = new \check_bodies\Body_Set($duplicated); }
                elseif (($mode === 'wrong_names_owner') || ($mode === 'foreign_symbols')) {
                    $other = \body_project_fixture\Fixture::prepare($program);
                    if ($mode === 'wrong_names_owner') { $bad = new \check_bodies\Body_Plan($f->symbols,$other->reader->annotations->names,$current,$baseline,false); }
                    else { $bad = new \check_bodies\Body_Plan($other->symbols,$names,$current,$baseline,false); }
                } elseif (($mode === 'missing_own_signature') || ($mode === 'unknown_type_dependency') || ($mode === 'wrong_parameter_dependency')) {
                    $original = $baseline->for_callable($f->input->callable_id); if ($original === null) { throw new \LogicException('Missing f body'); }
                    $variant = Probe::dependency_variant($original,$mode);
                    $valid = !\check_bodies\Body_Validity::is_current($variant,$f->input,$names,$current);
                } elseif ($mode === 'semantic_failure') {
                    $failed_fixture = \body_project_fixture\Fixture::prepare('function f(): int { return; } return 0;');
                    $failed_types = Probe::snapshot($failed_fixture);
                    $update = \check_bodies\Body_Checker::check($failed_fixture->symbols,$failed_types->names,$failed_types,$baseline,false);
                    $valid = !$update->valid(); $diagnostic = $update->diagnostic;
                    if ($diagnostic === null) { $valid = false; }
                    else { if (($diagnostic->path !== '/signatures.phs') || ($diagnostic->reason !== 'A value is required by the declared return type')) { $valid = false; } }
                    $hidden = false; try { $update->result(); } catch (\LogicException $error) { $hidden = $error->getMessage() === 'Body checking failed'; }
                    if (!$hidden) { $valid = false; }
                } else {
                    $full = ($mode === 'full') || ($mode === 'reversed') || ($mode === 'duplicate_result') || ($mode === 'missing_result') || ($mode === 'foreign_result');
                    $previous = $baseline; if ($mode === 'fresh') { $previous = $empty; }
                    $plan = new \check_bodies\Body_Plan($f->symbols,$names,$current,$previous,$full);
                    if (($mode === 'type_dependency') || ($mode === 'called_signature') || ($mode === 'unrelated_signature') || ($mode === 'missing_called_signature')) {
                        $count = 4; if ($mode === 'called_signature') { $count = 2; } if (($mode === 'unrelated_signature') || ($mode === 'missing_called_signature')) { $count = 1; }
                        $valid = $plan->task_count() === $count;
                        if ($mode !== 'type_dependency') { $valid = $valid && ($plan->selected_task($f->entry->symbol->symbol_id) === null); }
                        if ($mode === 'unrelated_signature') { $valid = $valid && ($plan->selected_task($f->input->callable_id) === null); }
                    } else {
                        $results = Probe::execute($plan,$mode === 'reversed');
                        if ($mode === 'duplicate_result') { $results[] = $results[0]; }
                        if ($mode === 'missing_result') { $missing /** vector<\check_bodies\Checked_Body> */ = []; $results = $missing; }
                        if ($mode === 'unselected_result') { $results[] = $baseline->at(0); }
                        if ($mode === 'foreign_result') {
                            $other = \body_project_fixture\Fixture::prepare($program); $other_types = Probe::snapshot($other);
                            $other_set = \check_bodies\Body_Checker::check($other->symbols,$other_types->names,$other_types,$empty,false)->result();
                            $results[0] = $other_set->at(0);
                        }
                        $joined = (new \check_bodies\Body_Join($plan))->join($results);
                        if ($mode === 'nonparticipant') { $valid = ($joined->size() === 3) && ($joined->for_callable($unused) === null); }
                        else { $valid = Probe::plan_text($joined) === $baseline_text; }
                        for ($i = 0; $i < $joined->size(); $i++) {
                            $body = $joined->at($i); $old = $baseline->for_callable($body->callable_id);
                            if ($mode === 'concrete') {
                                if ($body->input->instance !== null) { if ($joined->for_symbol($body->callable_id) !== null) { $valid = false; } }
                                if ($joined->for_callable($body->callable_id) !== $body) { $valid = false; }
                            }
                            if ($full || ($mode === 'fresh')) { if ($body === $old) { $valid = false; } }
                            else { if ($body !== $old) { $valid = false; } }
                        }
                        if ($mode === 'snapshot_membership') { $copy = $joined->bodies(); $copy[] = $joined->at(0); if ($joined->size() !== 4) { $valid = false; } }
                        if (!$full) { if ($mode !== 'fresh') { if ($plan->task_count() !== 0) { $valid = false; } } }
                    }
                }
            } catch (\LogicException $error) { $error_text = $error->getMessage(); }
            if ($error_text !== $expected) { $valid = false; }
            if (Probe::plan_text($baseline) !== $baseline_text) { $valid = false; }
            if (($before_types !== $types->type_count()) || ($before_shapes !== $types->representation_count()) || ($before_members !== $types->member_count())) { $valid = false; }
            echo $valid ? "true\n" : "false\n";
        }
    }
}
