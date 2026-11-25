<?php
declare(strict_types=1);
namespace lifetime_plan_test;
final class Probe {
    public static function snapshot(\lifetime_plan_fixture\Fixture $f): \resolve_types\Type_Resolution {
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
    private static function values(\analyze_lifetimes\Lifetime_Plan $plan): string {
        $out = '['; $separator = '';
        foreach ($plan->values() as $row) { $out .= $separator.'['.$row->value_id.','.$row->statement_id.','.json_quote(\analyze_lifetimes\Lifetime_Ends::name($row->end)).','.$row->consumer_id.']'; $separator = ','; }
        return $out.']';
    }
    private static function locals(\analyze_lifetimes\Lifetime_Plan $plan): string {
        $out = '['; $separator = '';
        foreach ($plan->locals() as $row) { $out .= $separator.'['.$row->local_id.','.$row->initialized_statement_id.','.$row->end_after_statement.','.json_quote(\analyze_lifetimes\Lifetime_Ends::local_name($row->end)).','.$row->block_id.']'; $separator = ','; }
        return $out.']';
    }
    private static function cleanups(\analyze_lifetimes\Lifetime_Plan $plan): string {
        $out = '['; $separator = '';
        foreach ($plan->cleanups() as $row) { $out .= $separator.'['.json_quote(\analyze_lifetimes\Lifetime_Ends::subject_name($row->subject)).','.$row->subject_id.','.$row->after_statement.','.$row->block_id.']'; $separator = ','; }
        return $out.']';
    }
    private static function malformed(\analyze_lifetimes\Lifetime_Plan $plan): bool {
        $cleanups = $plan->cleanups();
        if (q_count($cleanups) === 0) { return true; }
        $missing /** vector<\analyze_lifetimes\Cleanup_Obligation> */ = [];
        for ($i = 1; $i < q_count($cleanups); $i++) { $missing[] = $cleanups[$i]; }
        $rejected = false;
        try { $unused = new \analyze_lifetimes\Lifetime_Plan($plan->body,$plan->values(),$plan->reachable_statement_count,$plan->falls_through,$plan->locals(),$plan->blocks(),$missing); }
        catch (\LogicException $error) { $rejected = true; }
        if (!$rejected) { return false; }
        $duplicate = $cleanups; $duplicate[] = $cleanups[0]; $rejected = false;
        try { $unused = new \analyze_lifetimes\Lifetime_Plan($plan->body,$plan->values(),$plan->reachable_statement_count,$plan->falls_through,$plan->locals(),$plan->blocks(),$duplicate); }
        catch (\LogicException $error) { $rejected = true; }
        if (!$rejected) { return false; }
        if (q_count($cleanups) > 1) {
            $reversed /** vector<\analyze_lifetimes\Cleanup_Obligation> */ = [];
            for ($i = q_count($cleanups); $i > 0; $i = $i-1) { $reversed[] = $cleanups[$i-1]; }
            $rejected = false;
            try { $unused = new \analyze_lifetimes\Lifetime_Plan($plan->body,$plan->values(),$plan->reachable_statement_count,$plan->falls_through,$plan->locals(),$plan->blocks(),$reversed); }
            catch (\LogicException $error) { $rejected = true; }
            if (!$rejected) { return false; }
        }
        return true;
    }
    public static function run(string $text): void {
        $cases = json_read($text);
        for ($ci = 0; $ci < $cases->size(); $ci++) {
            $case_data = $cases->at($ci); $f = \lifetime_plan_fixture\Fixture::prepare($case_data->member('source')->text());
            $snapshot = Probe::snapshot($f); $names = $f->reader->annotations->bindings($f->input->owner);
            $body = \check_bodies\Body_Worker::prepare($f->input,$names,$snapshot)->check();
            $before = $snapshot->types->type_count(); $shapes = $snapshot->types->representation_count(); $members = $snapshot->types->member_count();
            $worker = new \analyze_lifetimes\Lifetime_Plan_Worker($body); $plan = $worker->analyze();
            $valid = ($plan->body === $body) && ($plan->reachable_statement_count === $case_data->member('statements')->integer()) && ($plan->falls_through === $case_data->member('fall')->boolean());
            $counts = $case_data->member('counts');
            if ((q_count($plan->values()) !== $counts->at(0)->integer()) || (q_count($plan->locals()) !== $counts->at(1)->integer()) || (q_count($plan->cleanups()) !== $counts->at(2)->integer())) { $valid = false; }
            if ($case_data->member('exact')->boolean()) {
                if ((Probe::values($plan) !== $case_data->member('values_json')->text()) || (Probe::locals($plan) !== $case_data->member('locals_json')->text()) || (Probe::cleanups($plan) !== $case_data->member('cleanups_json')->text())) { $valid = false; }
            }
            foreach ($plan->locals() as $local) { $first = $plan->local_for($local->local_id); if ($first === null) { $valid = false; } else { if ($first->initialized_statement_id !== $local->initialized_statement_id) { $valid = false; } } }
            $again = (new \analyze_lifetimes\Lifetime_Plan_Worker($body))->analyze();
            if ((Probe::values($plan) !== Probe::values($again)) || (Probe::locals($plan) !== Probe::locals($again)) || (Probe::cleanups($plan) !== Probe::cleanups($again))) { $valid = false; }
            if (!Probe::malformed($plan)) { $valid = false; }
            $one_shot = false;
            try { $worker->analyze(); } catch (\LogicException $error) { $one_shot = $error->getMessage() === 'Lifetime plan worker is one-shot'; }
            if (!$one_shot) { $valid = false; }
            if (($before !== $snapshot->types->type_count()) || ($shapes !== $snapshot->types->representation_count()) || ($members !== $snapshot->types->member_count())) { $valid = false; }
            echo $valid ? "true\n" : "false\n";
        }
    }
}
