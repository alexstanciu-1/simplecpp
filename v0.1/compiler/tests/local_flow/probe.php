<?php
declare(strict_types=1);
namespace local_flow_test;
final class Probe {
    public static function snapshot(\local_flow_fixture\Fixture $f): \resolve_types\Type_Resolution {
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
        $families /** hash<\load_runtime\Family_Preparation_Result,int> */ = [];
        return new \resolve_types\Type_Resolution($f->types,$f->catalog,$f->entry,$signatures,$locals,$f->reader->annotations->names,$f->instances->snapshot($bindings),$families);
    }
    private static function copy_graph(\check_bodies\Checked_Body $body, array $blocks /** vector<\check_bodies\Typed_Block> */): \check_bodies\Checked_Body {
        $values /** vector<\check_bodies\Typed_Value> */ = []; $calls /** vector<\check_bodies\Typed_Call> */ = [];
        $statements /** vector<\check_bodies\Typed_Statement> */ = []; $scopes /** vector<\check_bodies\Typed_Scope> */ = []; $arguments /** vector<\check_bodies\Typed_Argument> */ = [];
        for ($i = 1; $i < $body->value_count()+1; $i++) { $values[] = $body->value_for($i); }
        for ($i = 1; $i < $body->call_count()+1; $i++) { $calls[] = $body->call_for($i); }
        for ($i = 0; $i < $body->statement_count(); $i++) { $statements[] = $body->statement_at($i); }
        for ($i = 1; $i < $body->scope_count()+1; $i++) { $scopes[] = $body->scope_for($i); }
        for ($i = 0; $i < $body->argument_count(); $i++) { $arguments[] = $body->argument_at($i); }
        return new \check_bodies\Checked_Body($body->input,$body->names,$body->falls_through,$body->local_types,$values,$calls,$statements,$scopes,$arguments,$blocks,$body->type_dependencies(),$body->signature_dependencies());
    }
    private static function facts(\analyze_lifetimes\Initialization_State $state): string {
        $out = '['; $separator = '';
        foreach ($state->facts() as $row) { $out .= $separator . '[' . $row->local_id . ',' . $row->statement_id . ']'; $separator = ','; }
        return $out . ']';
    }
    public static function run(string $text): void {
        $cases = json_read($text);
        for ($ci = 0; $ci < $cases->size(); $ci++) {
            $case_data = $cases->at($ci); $f = \local_flow_fixture\Fixture::prepare($case_data->member('source')->text());
            $snapshot = Probe::snapshot($f); $names = $f->reader->annotations->bindings($f->input->owner);
            $body = \check_bodies\Body_Worker::prepare($f->input,$names,$snapshot)->check();
            $overrides = $case_data->member('blocks');
            if ($overrides->size() !== 0) {
                $blocks /** vector<\check_bodies\Typed_Block> */ = [];
                for ($i = 0; $i < $overrides->size(); $i++) {
                    $r = $overrides->at($i);
                    $blocks[] = new \check_bodies\Typed_Block($r->at(0)->integer(),$r->at(1)->integer(),$r->at(2)->integer(),$r->at(3)->integer(),$r->at(4)->integer(),$r->at(5)->integer());
                }
                $body = Probe::copy_graph($body,$blocks);
            }
            $entries = \analyze_lifetimes\Local_Flow::entries($body); $valid = $entries->body === $body;
            $repeat = \analyze_lifetimes\Local_Flow::entries($body);
            $reachable = \check_bodies\Flow_Graph::reachable($body->flow_blocks());
            if (q_count($reachable) !== $entries->size()) { $valid = false; }
            $output = '{"parameters":' . $body->entry_parameter_count() . ',"parents":['; $separator = '';
            for ($i = 1; $i < $names->scopes_count()+1; $i++) { $output .= $separator . $names->scope_for($i)->parent_scope_id; $separator = ','; }
            $output .= '],"locals":['; $separator = '';
            for ($i = 1; $i < $names->locals_count()+1; $i++) { $output .= $separator . $names->local_for($i)->scope_id; $separator = ','; }
            $output .= '],"blocks":['; $separator = '';
            for ($i = 0; $i < $body->block_count(); $i++) {
                $b = $body->block_at($i); $output .= $separator . '['.$b->statement_start.','.$b->statement_count.','.$b->scope_id.','.$b->end.','.$b->first.','.$b->second.']'; $separator = ',';
            }
            $output .= '],"statements":['; $separator = '';
            for ($i = 0; $i < $body->statement_count(); $i++) {
                $s = $body->statement_at($i); $local = 0;
                if ($s->kind === \check_bodies\STATEMENT_LOCAL_DECLARATION) { $target = $s->target; if ($target === null) { throw new \LogicException('Missing declaration target'); } $local = $target->local_id; }
                $output .= $separator . '[' . $s->scope_id . ',' . $local . ']'; $separator = ',';
            }
            $output .= '],"entries":['; $separator = '';
            for ($i = 1; $i < $body->block_count()+1; $i++) {
                $state = $entries->for_block($i); $again = $repeat->for_block($i); $projection = 'null';
                if ($state === null) { if ($again !== null) { $valid = false; } }
                else {
                    $projection = Probe::facts($state);
                    if ($again === null) { $valid = false; } else { if (Probe::facts($again) !== $projection) { $valid = false; } }
                    foreach ($state->facts() as $row) {
                        if (!$state->has((int)$row->local_id)) { $valid = false; }
                        $initialization = $state->initialization((int)$row->local_id);
                        if ($initialization === null) { $valid = false; } else { if ($initialization !== (int)$row->statement_id) { $valid = false; } }
                        $row->statement_id = 9999;
                    }
                    if (Probe::facts($state) !== $projection) { $valid = false; }
                }
                $output .= $separator . $projection; $separator = ',';
            }
            echo $output . '],"valid":' . ($valid ? 'true' : 'false') . "}\n";
        }
    }
}
