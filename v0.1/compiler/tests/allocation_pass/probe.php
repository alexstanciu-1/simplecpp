<?php
declare(strict_types=1);
namespace allocation_pass_test;
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
        if ($body->block_count() !== 1) { throw new \LogicException('Pass fixture expects a single checked block'); }
        $locations = \analyze_lifetimes\Resource_Locations::locals($body); $flow = new \analyze_lifetimes\Resource_Flow_State();
        $parameters /** hash<bool> */ = []; $incoming /** hash<int> */ = [];
        $observations = new \analyze_lifetimes\Ownership_Observations($incoming); $dependencies /** hash<\analyze_lifetimes\Ownership_Summary> */ = [];
        if ($input->has('lifecycle')) {
            $fields /** hash<\analyze_lifetimes\Resource_Transition> */ = []; $fields['0'] = new \analyze_lifetimes\Resource_Transition(1,10,true,true);
            $rows /** vector<\analyze_lifetimes\Parameter_Effects> */ = [new \analyze_lifetimes\Parameter_Effects(0,$fields)];
            $pairs /** vector<\analyze_lifetimes\Distinct_Endpoints> */ = []; $result /** hash<int> */ = [];
            $key = 'construct:' . $body->local_type_for(1); $dependencies[$key] = new \analyze_lifetimes\Ownership_Summary($rows,$pairs,$result);
            $fields['0'] = new \analyze_lifetimes\Resource_Transition(2,5,true,true); $rows[0] = new \analyze_lifetimes\Parameter_Effects(0,$fields);
            $key = 'destroy:' . $body->local_type_for(1); $dependencies[$key] = new \analyze_lifetimes\Ownership_Summary($rows,$pairs,$result);
        }
        $contracts = new \analyze_lifetimes\Resource_Calls($locations,$parameters,$observations);
        $runtime = new \analyze_lifetimes\Allocation_Calls($contracts); $bindings = new \analyze_lifetimes\Resource_Bindings($body);
        $traversal = new \analyze_lifetimes\Allocation_Traversal($body,$dependencies,$contracts,$runtime,$bindings,$observations);
        $pass = new \analyze_lifetimes\Allocation_Pass($body,$dependencies,$locations,$contracts,$bindings,$traversal,$observations);
        $reason = '';
        try {
            $exit = $pass->block(1,$flow);
            if (q_count($flow->states) !== 0) { throw new \LogicException('Block modified its fixed entry'); }
            if ($input->has('lifecycle')) {
                if ($exit->states['1:0'] !== 10) { throw new \LogicException('Exit destruction escaped private state'); }
                $pass->leave($exit,0); $pass->leave($exit,0);
                if ($exit->states['1:0'] !== 10) { throw new \LogicException('Sibling exit state was contaminated'); }
            }
        } catch (\RuntimeException $error) {
            $diagnostic = $pass->diagnostic(); if ($diagnostic === null) { throw new \LogicException('Missing block diagnostic'); }
            if ($diagnostic->path !== '/signatures.phs') { throw new \LogicException('Wrong block source'); }
            $reason = $diagnostic->reason;
        }
        return json_quote($reason);
    }
    public static function run(string $text): void {
        $cases = json_read($text); for ($i = 0; $i < $cases->size(); $i++) { echo Probe::run_case($cases->at($i)) . "\n"; }
    }
}
