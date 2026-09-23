<?php
declare(strict_types=1);
namespace analyzed_body_test;
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
    private static function ownership_for(\check_bodies\Checked_Body $body, \resolve_types\Type_Resolution $types, bool $prepare): ?\analyze_lifetimes\Ownership_Result {
        if (!$prepare) { return null; }
        $rows /** vector<\check_bodies\Checked_Body> */ = [$body]; $bodies = new \check_bodies\Body_Set($rows);
        $empty /** hash<\analyze_lifetimes\Ownership_Result> */ = [];
        $accepted = (new \analyze_lifetimes\Ownership_Queue((new \analyze_lifetimes\Ownership_Selection($bodies,$types->types))->select(),$empty,false))->run();
        $key = 'body:' . $body->callable_id; return $accepted[$key];
    }
    private static function boundaries(\analyze_lifetimes\Analyzed_Body $result, \check_bodies\Checked_Body $replacement): void {
        $body = $result->body; $plan = $result->plan; $allocations = $result->allocations; $ownership = $result->ownership;
        if ($ownership !== null) {
            $rejected = false;
            try { $invalid_worker = new \analyze_lifetimes\Lifetime_Worker($replacement,$ownership); }
            catch (\LogicException $error) { $rejected = $error->getMessage() === 'Stale ownership input for lifetime worker'; }
            if (!$rejected) { throw new \LogicException('Stale ownership accepted by worker'); }
            $replacement_plan = (new \analyze_lifetimes\Lifetime_Plan_Worker($replacement))->analyze();
            $rejected = false;
            try { $invalid = new \analyze_lifetimes\Analyzed_Body($replacement,$replacement_plan,null,$ownership); }
            catch (\LogicException $error) { $rejected = $error->getMessage() === 'Stale ownership input for lifetime result'; }
            if (!$rejected) { throw new \LogicException('Stale ownership accepted by result'); }
        }
        $owners = \analyze_lifetimes\Resource_Locations::locals($body);
        if (q_count($owners) !== 0) {
            $rejected = false;
            try { $invalid = new \analyze_lifetimes\Analyzed_Body($body,$plan,null,null); }
            catch (\LogicException $error) { $rejected = $error->getMessage() === 'Missing or unexpected allocation analysis'; }
            if (!$rejected) { throw new \LogicException('Missing allocation facts accepted'); }
        }
        if ($allocations !== null) {
            $entries = $allocations->entries();
            $rejected = false;
            $foreign = new \analyze_lifetimes\Allocation_Analysis($replacement,$entries);
            try { $invalid = new \analyze_lifetimes\Analyzed_Body($body,$plan,$foreign,null); }
            catch (\LogicException $error) { $rejected = $error->getMessage() === 'Allocation facts belong to another checked body'; }
            if (!$rejected) { throw new \LogicException('Foreign allocation facts accepted'); }
            if (q_count($owners) !== 0) {
                for ($mode = 0; $mode < 2; $mode++) {
                    $modified = $allocations->entries(); $states = $modified[1]->states();
                    $key = '999999'; $state = 5;
                    if ($mode === 1) { foreach ($owners as $owner_key => $location) { $key = '' . $owner_key; break; } $state = 0; }
                    $states[$key] = $state; $modified[1] = new \analyze_lifetimes\Allocation_Entry($states);
                    $bad = new \analyze_lifetimes\Allocation_Analysis($body,$modified); $rejected = false;
                    try { $invalid = new \analyze_lifetimes\Analyzed_Body($body,$plan,$bad,null); }
                    catch (\LogicException $error) { $rejected = $error->getMessage() === 'Invalid allocation entry state'; }
                    if (!$rejected) { throw new \LogicException('Invalid resource state accepted'); }
                }
                if (q_count($entries) > 1) {
                    foreach ($entries as $id => $entry) { if ($id !== 1) { unset($entries[$id]); break; } }
                    $bad = new \analyze_lifetimes\Allocation_Analysis($body,$entries); $rejected = false;
                    try { $invalid = new \analyze_lifetimes\Analyzed_Body($body,$plan,$bad,null); }
                    catch (\LogicException $error) { $rejected = $error->getMessage() === 'Allocation analysis must cover reachable blocks'; }
                    if (!$rejected) { throw new \LogicException('Incomplete resource coverage accepted'); }
                }
                $same = new \analyze_lifetimes\Analyzed_Body($body,$plan,$allocations,$ownership);
                if ($same->allocations !== $allocations) { throw new \LogicException('Validation changed accepted facts'); }
            }
        } else {
            $states /** hash<int> */ = []; $entries /** hash<\analyze_lifetimes\Allocation_Entry,int> */ = [];
            foreach ($plan->blocks() as $id) { $entries[$id] = new \analyze_lifetimes\Allocation_Entry($states); }
            $extra = new \analyze_lifetimes\Allocation_Analysis($body,$entries); $rejected = false;
            try { $invalid = new \analyze_lifetimes\Analyzed_Body($body,$plan,$extra,null); }
            catch (\LogicException $error) { $rejected = $error->getMessage() === 'Missing or unexpected allocation analysis'; }
            if (!$rejected) { throw new \LogicException('Unexpected allocation facts accepted'); }
        }
    }
    public static function run(string $text): void {
        $cases = json_read($text);
        for ($i = 0; $i < $cases->size(); $i++) {
            $item = $cases->at($i); $f = \resource_locations_fixture\Fixture::prepare($item->member('source')->text()); $types = Probe::snapshot($f);
            $body = \check_bodies\Body_Worker::prepare($f->input,$f->reader->annotations->bindings($f->input->owner),$types)->check();
            $ownership = Probe::ownership_for($body,$types,$item->member('ownership')->boolean());
            $worker = new \analyze_lifetimes\Lifetime_Worker($body,$ownership); $message = 'accepted';
            try {
                $result = $worker->analyze();
                if (($result->body !== $body) || ($result->plan->body !== $body) || ($result->ownership !== $ownership)) { throw new \LogicException('Lost lifetime result provenance'); }
                if (($result->allocations !== null) !== $item->member('allocations')->boolean()) { throw new \LogicException('Incorrect resource result presence'); }
                if ($ownership !== null) { if ($result->allocations !== $ownership->allocations) { throw new \LogicException('Accepted resource analysis recomputed'); } }
                $again = false; try { $worker->analyze(); } catch (\LogicException $error) { $again = true; }
                if (!$again) { throw new \LogicException('Worker reused'); }
                $replacement = \check_bodies\Body_Worker::prepare($f->input,$f->reader->annotations->bindings($f->input->owner),$types)->check();
                $rejected = false;
                try { $invalid = new \analyze_lifetimes\Analyzed_Body($replacement,$result->plan,$result->allocations,$result->ownership); }
                catch (\LogicException $error) { $rejected = true; }
                if (!$rejected) { throw new \LogicException('Foreign plan accepted'); }
                Probe::boundaries($result,$replacement);
            } catch (\RuntimeException $error) {
                $diagnostic = $worker->diagnostic(); if ($diagnostic === null) { throw new \LogicException('Missing lifetime source diagnostic'); }
                $message = $error->getMessage();
            }
            echo json_quote($message) . "\n";
        }
    }
}
