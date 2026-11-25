<?php
declare(strict_types=1);
namespace allocation_traversal_test;
final class Probe {
    public static function snapshot(\resource_locations_fixture\Fixture $f): \resolve_types\Type_Resolution {
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
    private static function run_case(\scpp\Json_View $input): string {
        $f = \resource_locations_fixture\Fixture::prepare($input->member('source')->text()); $snapshot = Probe::snapshot($f);
        $body = \check_bodies\Body_Worker::prepare($f->input,$f->reader->annotations->bindings($f->input->owner),$snapshot)->check();
        $locations = \analyze_lifetimes\Resource_Locations::locals($body); $flow = new \analyze_lifetimes\Resource_Flow_State();
        foreach ($locations as $key => $location) { $flow->states[$key] = \analyze_lifetimes\RESOURCE_EMPTY_VALUE; }
        $parameters /** hash<bool> */ = []; $incoming /** hash<int> */ = [];
        $observations = new \analyze_lifetimes\Ownership_Observations($incoming); $dependencies /** hash<\analyze_lifetimes\Ownership_Summary> */ = [];
        $mode = $input->member('mode')->text();
        $fields /** hash<\analyze_lifetimes\Resource_Transition> */ = [];
        $summary_parameters /** vector<\analyze_lifetimes\Parameter_Effects> */ = [];
        $summary_pairs /** vector<\analyze_lifetimes\Distinct_Endpoints> */ = []; $summary_result /** hash<int> */ = [];
        $summary = new \analyze_lifetimes\Ownership_Summary($summary_parameters,$summary_pairs,$summary_result);
        if ($input->has('summary_call')) {
            $fields['0'] = new \analyze_lifetimes\Resource_Transition(3,9,false,true);
            $summary_parameters[] = new \analyze_lifetimes\Parameter_Effects(0,$fields);
            $summary = new \analyze_lifetimes\Ownership_Summary($summary_parameters,$summary_pairs,$summary_result);
            $key = 'body:' . $body->call_for(1)->target_callable_id; $dependencies[$key] = $summary;
        }
        if ($input->has('construct_dependency')) {
            $fields['0'] = new \analyze_lifetimes\Resource_Transition(1,10,true,true);
            $summary_parameters[] = new \analyze_lifetimes\Parameter_Effects(0,$fields);
            $summary = new \analyze_lifetimes\Ownership_Summary($summary_parameters,$summary_pairs,$summary_result);
            $key = 'construct:' . $body->local_type_for(1); $dependencies[$key] = $summary;
        }
        if ($mode !== 'expression') {
            $fields['0'] = new \analyze_lifetimes\Resource_Transition($input->member('required')->integer(),$input->member('result')->integer(),true,true);
            $summary_parameters[] = new \analyze_lifetimes\Parameter_Effects(0,$fields);
            if (($mode === 'copy') || ($mode === 'move')) {
                $source_fields /** hash<\analyze_lifetimes\Resource_Transition> */ = [];
                $source_result = 9; $mutates = false; if ($mode === 'move') { $source_result = 5; $mutates = true; }
                $source_fields['0'] = new \analyze_lifetimes\Resource_Transition(2,$source_result,$mutates,true);
                $summary_parameters[] = new \analyze_lifetimes\Parameter_Effects(1,$source_fields);
                $flow->states['1:0'] = $input->member('initial')->integer();
            }
            $summary = new \analyze_lifetimes\Ownership_Summary($summary_parameters,$summary_pairs,$summary_result);
            $key = 'destroy:' . $body->local_type_for(1); $dependencies[$key] = $summary;
            if (($mode === 'copy') || ($mode === 'move')) { $key = $mode . ':' . $body->local_type_for(1); $dependencies[$key] = $summary; }
        }
        $contracts = new \analyze_lifetimes\Resource_Calls($locations,$parameters,$observations);
        $runtime = new \analyze_lifetimes\Allocation_Calls($contracts); $bindings = new \analyze_lifetimes\Resource_Bindings($body);
        $traversal = new \analyze_lifetimes\Allocation_Traversal($body,$dependencies,$contracts,$runtime,$bindings,$observations);
        if ($mode !== 'expression') {
            $reason = ''; $node = $body->value_for(1)->source_node_id;
            try {
                if ($mode === 'construct') {
                    $result = $traversal->construction_fields($summary,$node);
                    if ($result->states['0'] !== $input->member('state')->integer()) { throw new \LogicException('Wrong constructed resource state'); }
                } else if (($mode === 'copy') || ($mode === 'move')) {
                    $value_id = $body->argument_for(1,1)->value_id;
                    $result = $traversal->source_construction_fields($value_id,$flow,$mode . ':');
                    $source_state = 10; if ($mode === 'move') { $source_state = 5; }
                    if (($result->states['0'] !== 10) || ($flow->states['1:0'] !== $source_state)) { throw new \LogicException('Wrong source construction transfer'); }
                } else {
                    $states /** hash<int> */ = []; $states['0'] = $input->member('state')->integer();
                    $traversal->finish_fields($body->local_type_for(1),new \analyze_lifetimes\Resource_Field_State($states),$node);
                }
            } catch (\RuntimeException $error) {
                $diagnostic = $traversal->diagnostic(); if ($diagnostic === null) { throw new \LogicException('Missing lifecycle resource diagnostic'); }
                $reason = $diagnostic->reason;
            }
            return json_quote($reason);
        }
        $borrows /** hash<\analyze_lifetimes\Resource_Location,int> */ = []; $reason = ''; $constructed_count = 0;
        if ($input->has('pin')) { $root_path /** vector<int> */ = []; $borrows[-1] = new \analyze_lifetimes\Resource_Location(1,$root_path); }
        try {
            for ($i = 0; $i < $body->statement_count(); $i++) {
                $statement = $body->statement_at($i);
                $constructed = $traversal->expression($statement->value_id,$statement->call_start,$statement->call_start+$statement->call_count,$flow,$borrows);
                foreach ($constructed as $value_id => $fields_result) {
                    $constructed_count++;
                    if ($fields_result->states['0'] !== 10) { throw new \LogicException('Wrong constructed expression result'); }
                }
            }
        } catch (\RuntimeException $error) {
            $diagnostic = $traversal->diagnostic(); if ($diagnostic === null) { throw new \LogicException('Missing resource traversal diagnostic'); }
            if ($diagnostic->path !== '/signatures.phs') { throw new \LogicException('Wrong resource traversal source'); }
            $reason = $diagnostic->reason;
        }
        $expected_constructed = 0; if ($input->has('construct_dependency')) { $expected_constructed = 1; }
        if ($constructed_count !== $expected_constructed) { throw new \LogicException('Unexpected constructed expression count'); }
        $out = '['; $separator = '';
        for ($id = 1; $id < $body->names->locals_count()+1; $id++) {
            foreach (\analyze_lifetimes\Resource_Locations::paths($body->definition_for($body->local_type_for($id))) as $path) {
                $location = new \analyze_lifetimes\Resource_Location($id,$path); $key = $location->key();
                $out .= $separator . $flow->states[$key]; $separator = ',';
            }
        }
        return '[' . $out . '],' . json_quote($reason) . ']';
    }
    public static function run(string $text): void {
        $cases = json_read($text); for ($i = 0; $i < $cases->size(); $i++) { echo Probe::run_case($cases->at($i)) . "\n"; }
    }
}
