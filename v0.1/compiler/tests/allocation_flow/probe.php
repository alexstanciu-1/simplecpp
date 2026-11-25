<?php
declare(strict_types=1);
namespace allocation_flow_test;
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
        $dependencies /** hash<\analyze_lifetimes\Ownership_Summary> */ = [];
        $worker = new \analyze_lifetimes\Allocation_Flow($body,$dependencies); $reason = ''; $has_analysis = 'false'; $rows = '['; $separator = '';
        try {
            $analysis = $worker->analyze();
            if ($analysis !== null) {
                $has_analysis = 'true';
                if ($analysis->body !== $body) { throw new \LogicException('Allocation result lost body identity'); }
                $entries = $analysis->entries(); $count = q_count($entries); unset($entries[1]);
                if (q_count($analysis->entries()) !== $count) { throw new \LogicException('Allocation entry membership escaped'); }
                $reachable = \check_bodies\Flow_Graph::reachable($body->flow_blocks());
                if (q_count($reachable) !== $count) { throw new \LogicException('Incomplete reachable allocation entries'); }
                $current_entries = $analysis->entries();
                if ($input->has('entry_states')) {
                    $observed /** hash<bool,int> */ = [];
                    foreach ($current_entries as $entry) { foreach ($entry->states() as $state) { $observed[$state] = true; } }
                    $expected_states = $input->member('entry_states');
                    if (q_count($observed) !== $expected_states->size()) { throw new \LogicException('Incorrect converged relation membership'); }
                    for ($i = 0; $i < $expected_states->size(); $i++) {
                        $state = $expected_states->at($i)->integer(); if (!isset($observed[$state])) { throw new \LogicException('Incorrect converged relation'); }
                    }
                }
                foreach ($reachable as $id) { if (!isset($current_entries[$id])) { throw new \LogicException('Missing reachable allocation entry'); } }
            }
            $summary = $worker->summary(); $parameters = $summary->parameters();
            for ($position = 0; $position < $body->entry_parameter_count(); $position++) {
                if (isset($parameters[$position])) {
                    foreach ($parameters[$position]->fields() as $path => $transition) {
                        $mutates = 'false'; if ($transition->mutates) { $mutates = 'true'; }
                        $accessed = 'false'; if ($transition->accessed) { $accessed = 'true'; }
                        $rows .= $separator . '[' . $position . ',' . json_quote('' . $path) . ',' . $transition->required . ',' . $transition->result . ',' . $mutates . ',' . $accessed . ']'; $separator = ',';
                    }
                }
            }
            $rejected = false; try { $worker->analyze(); } catch (\LogicException $error) { $rejected = true; }
            if (!$rejected) { throw new \LogicException('Allocation worker reused'); }
        } catch (\RuntimeException $error) {
            $diagnostic = $worker->diagnostic(); if ($diagnostic === null) { throw new \LogicException('Missing allocation diagnostic'); }
            if ($diagnostic->path !== '/signatures.phs') { throw new \LogicException('Wrong allocation source'); } $reason = $diagnostic->reason;
        }
        return '[' . $has_analysis . ',' . $rows . '],' . json_quote($reason) . ']';
    }
    public static function run(string $text): void {
        $cases = json_read($text); for ($i = 0; $i < $cases->size(); $i++) { echo Probe::run_case($cases->at($i)) . "\n"; }
    }
}
