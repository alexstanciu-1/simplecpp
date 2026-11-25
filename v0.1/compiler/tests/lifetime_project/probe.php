<?php
declare(strict_types=1);
namespace lifetime_project_test;
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
    private static function inputs(\check_bodies\Body_Set $bodies, \resolve_types\Type_Resolution $types, \analyze_lifetimes\Lifetime_Set $previous, bool $full): \analyze_lifetimes\Lifetime_Inputs {
        $requests = (new \analyze_lifetimes\Ownership_Selection($bodies,$types->types))->select();
        return new \analyze_lifetimes\Lifetime_Inputs((new \analyze_lifetimes\Ownership_Queue($requests,$previous->ownership_results(),$full))->run());
    }
    private static function execute(array $tasks /** vector<\check_bodies\Checked_Body> */, \analyze_lifetimes\Lifetime_Inputs $inputs): array /** vector<\analyze_lifetimes\Analyzed_Body> */ {
        $results /** vector<\analyze_lifetimes\Analyzed_Body> */ = [];
        foreach ($tasks as $task) { $results[] = (new \analyze_lifetimes\Lifetime_Worker($task,$inputs->for_body($task->callable_id)))->analyze(); }
        return $results;
    }
    private static function run_case(string $mode): string {
        $f = \resource_locations_fixture\Fixture::prepare('function f(const Nested &$n): int { return 0; } function g(): int { return 0; } return 0;'); $types = Probe::snapshot($f);
        $body = \check_bodies\Body_Worker::prepare($f->input,$f->reader->annotations->bindings($f->input->owner),$types)->check();
        $input = new \resolve_types\Callable_Input($f->symbols->symbol_by_id($f->symbols->find_symbol('g',\collect_symbols\SYMBOL_FUNCTION,0,'')));
        $other = \check_bodies\Body_Worker::prepare($input,$f->reader->annotations->bindings($input->owner),$types)->check();
        $rows /** vector<\check_bodies\Checked_Body> */ = [$body,$other]; $bodies = new \check_bodies\Body_Set($rows);
        $none /** vector<\analyze_lifetimes\Analyzed_Body> */ = []; $no_ownership /** hash<\analyze_lifetimes\Ownership_Result> */ = [];
        $empty = new \analyze_lifetimes\Lifetime_Set($none,$no_ownership); $inputs = Probe::inputs($bodies,$types,$empty,false);
        $tasks = \analyze_lifetimes\Lifetime_Selection::select($bodies,$empty,$inputs,false);
        $old = (new \analyze_lifetimes\Lifetime_Join($bodies,$empty,$tasks,$inputs))->join(Probe::execute($tasks,$inputs));
        $previous_f = $old->for_callable($body->callable_id); $previous_g = $old->for_callable($other->callable_id);
        if (($previous_f === null) || ($previous_g === null)) { throw new \LogicException('Missing baseline'); }
        if ($mode === 'changed_body') { $body = \check_bodies\Body_Worker::prepare($f->input,$f->reader->annotations->bindings($f->input->owner),$types)->check(); $rows[0] = $body; }
        if ($mode === 'removed') { $rows = [$body]; }
        $bodies = new \check_bodies\Body_Set($rows); $inputs = Probe::inputs($bodies,$types,$old,($mode === 'changed_ownership') || ($mode === 'stale_ownership'));
        $full = ($mode === 'full') || ($mode === 'reverse') || ($mode === 'duplicate_task') || ($mode === 'duplicate_result') || ($mode === 'missing_result') || ($mode === 'stale_result');
        $tasks = \analyze_lifetimes\Lifetime_Selection::select($bodies,$old,$inputs,$full);
        $expected_tasks = 0;
        if ($full) { $expected_tasks = 2; }
        if (($mode === 'changed_body') || ($mode === 'changed_ownership') || ($mode === 'stale_ownership')) { $expected_tasks = 1; }
        if (q_count($tasks) !== $expected_tasks) { throw new \LogicException('Incorrect lifetime selection'); }
        if ($mode === 'stale_ownership') { $no_tasks /** vector<\check_bodies\Checked_Body> */ = []; $tasks = $no_tasks; }
        $results = Probe::execute($tasks,$inputs);
        if ($mode === 'reverse') { $results = [$results[1],$results[0]]; }
        if ($mode === 'duplicate_task') { $tasks[] = $tasks[0]; }
        if ($mode === 'duplicate_result') { $results[] = $results[0]; }
        if ($mode === 'missing_result') { $results = [$results[0]]; }
        if ($mode === 'unexpected_result') { $results[] = $previous_f; }
        if ($mode === 'stale_result') {
            $replacement = \check_bodies\Body_Worker::prepare($f->input,$f->reader->annotations->bindings($f->input->owner),$types)->check();
            $rows[0] = $replacement; $bodies = new \check_bodies\Body_Set($rows); $tasks[0] = $replacement;
        }
        $message = 'accepted';
        try {
            $current = (new \analyze_lifetimes\Lifetime_Join($bodies,$old,$tasks,$inputs))->join($results);
            $published = $current->bodies(); if (q_count($published) !== q_count($rows)) { throw new \RuntimeException('Incorrect current membership'); }
            for ($i = 0; $i < q_count($rows); $i++) { if ($published[$i]->body !== $rows[$i]) { throw new \RuntimeException('Lost publication order'); } }
            if (($mode === 'reuse') || ($mode === 'removed')) { if ($current->for_callable($body->callable_id) !== $previous_f) { throw new \RuntimeException('Unchanged lifetime not reused'); } }
            if (($mode === 'changed_body') || ($mode === 'changed_ownership') || $full) { if ($current->for_callable($body->callable_id) === $previous_f) { throw new \RuntimeException('Changed lifetime reused'); } }
            if (!$full && ($mode !== 'removed')) { if ($current->for_callable($other->callable_id) !== $previous_g) { throw new \RuntimeException('Unchanged peer not reused'); } }
            if ($mode === 'removed') { if ($current->for_callable($other->callable_id) !== null) { throw new \RuntimeException('Removed result survived'); } }
        } catch (\LogicException $error) { $message = $error->getMessage(); }
        if (($old->for_callable($previous_f->body->callable_id) !== $previous_f) || ($old->for_callable($previous_g->body->callable_id) !== $previous_g)) { throw new \LogicException('Previous publication mutated'); }
        return $message;
    }
    public static function run(string $text): void {
        $cases = json_read($text); for ($i = 0; $i < $cases->size(); $i++) { echo json_quote(Probe::run_case($cases->at($i)->member('mode')->text())) . "\n"; }
    }
}
