<?php
declare(strict_types=1);
namespace lifetime_analyzer_test;
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
    private static function bodies(\resource_locations_fixture\Fixture $f, \resolve_types\Type_Resolution $types): \check_bodies\Body_Set {
        $rows /** vector<\check_bodies\Checked_Body> */ = [];
        $g = $f->symbols->find_symbol('g',\collect_symbols\SYMBOL_FUNCTION,0,'');
        if ($g !== 0) {
            $input = new \resolve_types\Callable_Input($f->symbols->symbol_by_id($g));
            $rows[] = \check_bodies\Body_Worker::prepare($input,$f->reader->annotations->bindings($input->owner),$types)->check();
        }
        $rows[] = \check_bodies\Body_Worker::prepare($f->input,$f->reader->annotations->bindings($f->input->owner),$types)->check();
        return new \check_bodies\Body_Set($rows);
    }
    public static function run(string $text): void {
        $cases = json_read($text);
        for ($i = 0; $i < $cases->size(); $i++) {
            $item = $cases->at($i); $f = \resource_locations_fixture\Fixture::prepare($item->member('source')->text()); $types = Probe::snapshot($f);
            $bodies = Probe::bodies($f,$types); $none /** vector<\analyze_lifetimes\Analyzed_Body> */ = []; $no_ownership /** hash<\analyze_lifetimes\Ownership_Result> */ = [];
            $previous = new \analyze_lifetimes\Lifetime_Set($none,$no_ownership); $analyzer = new \analyze_lifetimes\Lifetime_Analyzer($bodies,$types->types,$previous,false); $message = 'accepted';
            try {
                $update = $analyzer->analyze();
                if (!$update->valid()) {
                    $diagnostic = $update->diagnostic; if ($diagnostic === null) { throw new \LogicException('Missing analysis diagnostic'); }
                    $message = $diagnostic->reason;
                    $rejected = false; try { $invalid = $update->result(); } catch (\LogicException $error) { $rejected = true; }
                    if (!$rejected) { throw new \LogicException('Failed analysis exposed results'); }
                } else {
                    $current = $update->result();
                    if (q_count($current->bodies()) !== $bodies->size()) { throw new \LogicException('Incomplete analysis'); }
                    $reuse = (new \analyze_lifetimes\Lifetime_Analyzer($bodies,$types->types,$current,false))->analyze()->result();
                    $full = (new \analyze_lifetimes\Lifetime_Analyzer($bodies,$types->types,$current,true))->analyze()->result();
                    foreach ($current->bodies() as $result) {
                        if ($reuse->for_callable($result->body->callable_id) !== $result) { throw new \LogicException('Incremental analysis lost reuse'); }
                        $rebuilt = $full->for_callable($result->body->callable_id); if ($rebuilt === null) { throw new \LogicException('Missing rebuilt analysis'); }
                        if (($rebuilt === $result) || ($rebuilt->body !== $result->body)) { throw new \LogicException('Incorrect full analysis'); }
                    }
                }
                $rejected = false; try { $analyzer->analyze(); } catch (\LogicException $error) { $rejected = true; }
                if (!$rejected) { throw new \LogicException('Coordinator reused'); }
            } catch (\RuntimeException $error) {
                $message = $error->getMessage();
                if ($message === 'Recursive ownership summary dependencies are unsupported: body:' . $f->input->callable_id) { $message = 'cycle rejected'; }
            }
            if (q_count($previous->bodies()) !== 0) { throw new \LogicException('Prior results mutated'); }
            echo json_quote($message) . "\n";
        }
    }
}
