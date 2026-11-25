<?php
declare(strict_types=1);
namespace resource_bindings_test;
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
        $bindings = new \analyze_lifetimes\Resource_Bindings($body);
        $mode = $input->member('mode')->text(); $out = ''; $call = $body->call_count();
        try {
            if ($mode === 'operand') { $out = $bindings->operand($call,$input->member('position')->integer())->key(); }
            else if ($mode === 'allocation') {
                $effect = new \type_model\Allocation_Effect(\type_model\ALLOCATION_TRANSFER,1,0);
                $mapped = $bindings->allocation_operands($call,$effect); $out = $mapped[1]->key() . '>' . $mapped[0]->key();
            }
            else if ($mode === 'summary') {
                $fields /** hash<\analyze_lifetimes\Resource_Transition> */ = [];
                $fields[''] = new \analyze_lifetimes\Resource_Transition(3,9,false,true);
                $parameters /** vector<\analyze_lifetimes\Parameter_Effects> */ = [new \analyze_lifetimes\Parameter_Effects(1,$fields),new \analyze_lifetimes\Parameter_Effects(0,$fields)];
                $pairs /** vector<\analyze_lifetimes\Distinct_Endpoints> */ = []; $results /** hash<int> */ = [];
                $summary = new \analyze_lifetimes\Ownership_Summary($parameters,$pairs,$results);
                $mapped = $bindings->summary_operands($call,$summary); $out = $mapped[1]->key() . '>' . $mapped[0]->key();
            }
            else { throw new \LogicException('Unknown binding test'); }
        } catch (\RuntimeException $error) {
            $diagnostic = $bindings->diagnostic(); $failure = $bindings->failure();
            if ($diagnostic === null) { throw new \LogicException('Missing binding diagnostic'); }
            if ($failure === null) { throw new \LogicException('Missing binding failure'); }
            $out = $diagnostic->reason;
            $syntax = $body->input->owner->source_frontend()->tree->row($failure->node);
            if (($diagnostic->path !== '/signatures.phs') || ($diagnostic->start !== (int)$syntax->start) || ($diagnostic->length !== (int)$syntax->length)) { throw new \LogicException('Wrong binding attribution'); }
        }
        return json_quote($out);
    }
    public static function run(string $text): void {
        $cases = json_read($text); for ($i = 0; $i < $cases->size(); $i++) { echo Probe::run_case($cases->at($i)) . "\n"; }
    }
}
