<?php
declare(strict_types=1);
namespace ownership_queue_test;
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
    private static function requests(\check_bodies\Checked_Body $body, \resolve_types\Type_Resolution $types, string $mode): array /** vector<\analyze_lifetimes\Ownership_Request> */ {
        $nested_id = $body->local_type_for(1); $box_id = $types->types->field_for($nested_id,0)->type_id;
        $empty_children /** vector<\analyze_lifetimes\Ownership_Child> */ = [];
        $leaf_kind = \type_model\LIFECYCLE_DEFAULT; if ($mode === 'changed_dependency') { $leaf_kind = \type_model\LIFECYCLE_DESTROY; }
        $leaf = new \analyze_lifetimes\Ownership_Lifecycle($box_id,$types->types->definition_for_type($box_id),$leaf_kind,$empty_children,null);
        $children /** vector<\analyze_lifetimes\Ownership_Child> */ = [new \analyze_lifetimes\Ownership_Child(0,'leaf')];
        $parent = new \analyze_lifetimes\Ownership_Lifecycle($nested_id,$body->definition_for($nested_id),\type_model\LIFECYCLE_DEFAULT,$children,null);
        $parent_dependencies /** vector<string> */ = ['leaf','leaf']; $leaf_dependencies /** vector<string> */ = []; $empty /** vector<string> */ = [];
        if ($mode === 'cycle') { $leaf_dependencies[] = 'parent'; }
        if ($mode === 'missing') { $leaf_dependencies[] = 'missing'; }
        $requests /** vector<\analyze_lifetimes\Ownership_Request> */ = [new \analyze_lifetimes\Ownership_Request('parent',null,$parent,$parent_dependencies),new \analyze_lifetimes\Ownership_Request('leaf',null,$leaf,$leaf_dependencies)];
        if ($mode !== 'removed') { $requests[] = new \analyze_lifetimes\Ownership_Request('body',$body,null,$empty); }
        if ($mode === 'duplicate') { $requests[] = $requests[1]; }
        return $requests;
    }
    private static function run_case(string $mode, \check_bodies\Checked_Body $body, \resolve_types\Type_Resolution $types): string {
        $empty /** hash<\analyze_lifetimes\Ownership_Result> */ = []; $requests = Probe::requests($body,$types,'fresh');
        $old = (new \analyze_lifetimes\Ownership_Queue($requests,$empty,false))->run();
        if ($old['parent']->task->dependency('leaf') !== $old['leaf']->summary) { throw new \LogicException('Dependency was not accepted before capture'); }
        $expected = $old['parent']->summary->parameter(0)->at('0.0');
        if (($expected->required !== 1) || ($expected->result !== 5) || !$expected->accessed) { throw new \LogicException('Incorrect composed parent'); }
        $requests = Probe::requests($body,$types,$mode);
        if ($mode === 'body_replaced') {
            $replacement = \check_bodies\Body_Worker::prepare($body->input,$body->names,$types)->check(); $none /** vector<string> */ = [];
            $requests[2] = new \analyze_lifetimes\Ownership_Request('body',$replacement,null,$none);
        }
        try {
            $queue = new \analyze_lifetimes\Ownership_Queue($requests,$old,$mode === 'full'); $current = $queue->run();
            $count = 3; if ($mode === 'removed') { $count = 2; }
            if (q_count($current) !== $count) { throw new \RuntimeException('Incorrect current ownership membership'); }
            foreach ($current as $key => $result) {
                if ($mode === 'full') {
                    if ($result === $old[$key]) { throw new \RuntimeException('Full rebuild reused task result'); }
                    if ($result->summary !== $old[$key]->summary) { throw new \RuntimeException('Stable summary identity lost'); }
                } else if (($mode === 'body_replaced') && ($key === 'body')) {
                    if (($result === $old[$key]) || ($result->summary !== $old[$key]->summary)) { throw new \RuntimeException('Body replacement reuse is incorrect'); }
                } else if (($mode === 'changed_dependency') && ($key !== 'body')) {
                    if ($result === $old[$key]) { throw new \RuntimeException('Changed dependency failed to invalidate work'); }
                    if ($key === 'leaf') { if ($result->summary === $old[$key]->summary) { throw new \RuntimeException('Changed leaf meaning was reused'); } }
                    else { if ($result->summary !== $old[$key]->summary) { throw new \RuntimeException('Stable dependent meaning was not retained'); } }
                } else { if ($result !== $old[$key]) { throw new \RuntimeException('Unchanged ownership result not reused'); } }
            }
            $rejected = false; try { $queue->run(); } catch (\LogicException $error) { $rejected = true; }
            if (!$rejected) { throw new \RuntimeException('Ownership queue reused'); }
        } catch (\LogicException $error) { return json_quote($error->getMessage()); }
        catch (\RuntimeException $error) { return json_quote($error->getMessage()); }
        return json_quote('accepted');
    }
    public static function run(string $text): void {
        $f = \resource_locations_fixture\Fixture::prepare('function f(const Nested &$n): int { return 0; } return 0;'); $types = Probe::snapshot($f);
        $body = \check_bodies\Body_Worker::prepare($f->input,$f->reader->annotations->bindings($f->input->owner),$types)->check();
        $cases = json_read($text); for ($i = 0; $i < $cases->size(); $i++) { echo Probe::run_case($cases->at($i)->member('mode')->text(),$body,$types) . "\n"; }
    }
}
