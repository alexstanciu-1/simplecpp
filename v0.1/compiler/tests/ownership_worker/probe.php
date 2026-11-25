<?php
declare(strict_types=1);
namespace ownership_worker_test;
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
    private static function summary(int $required, int $result, string $path, bool $source): \analyze_lifetimes\Ownership_Summary {
        $fields /** hash<\analyze_lifetimes\Resource_Transition> */ = []; $fields[$path] = new \analyze_lifetimes\Resource_Transition($required,$result,true,true);
        $parameters /** vector<\analyze_lifetimes\Parameter_Effects> */ = [new \analyze_lifetimes\Parameter_Effects(0,$fields)];
        $pairs /** vector<\analyze_lifetimes\Distinct_Endpoints> */ = []; $output /** hash<int> */ = [];
        if ($source) {
            $fields[$path] = new \analyze_lifetimes\Resource_Transition(2,9,false,true); $parameters[] = new \analyze_lifetimes\Parameter_Effects(1,$fields);
            $pairs[] = new \analyze_lifetimes\Distinct_Endpoints('0:' . $path,'1:' . $path);
        }
        return new \analyze_lifetimes\Ownership_Summary($parameters,$pairs,$output);
    }
    private static function subject(int $type, \type_model\Named_Definition $definition, int $kind,
        array $children /** vector<\analyze_lifetimes\Ownership_Child> */, bool $body): \analyze_lifetimes\Ownership_Lifecycle {
        if ($body) { return new \analyze_lifetimes\Ownership_Lifecycle($type,$definition,$kind,$children,7); }
        return new \analyze_lifetimes\Ownership_Lifecycle($type,$definition,$kind,$children,null);
    }
    private static function run_case(\scpp\Json_View $input, \check_bodies\Checked_Body $body): string {
        $children /** vector<\analyze_lifetimes\Ownership_Child> */ = []; $dependencies /** hash<\analyze_lifetimes\Ownership_Summary> */ = [];
        if ($input->member('child')->boolean()) {
            $children[] = new \analyze_lifetimes\Ownership_Child(0,'child');
            $dependencies['child'] = Probe::summary($input->member('required')->integer(),$input->member('result')->integer(),'0',$input->member('source')->boolean());
        }
        if ($input->member('body')->boolean()) { $dependencies['body:7'] = Probe::summary(1,10,'0.0',false); }
        $type = $body->local_type_for(1); $definition = $body->definition_for($type);
        $subject = Probe::subject($type,$definition,\type_model\Lifecycle_Roles::parse($input->member('role')->text()),$children,$input->member('body')->boolean());
        $task = new \analyze_lifetimes\Ownership_Task('lifecycle',null,$subject,$dependencies); $worker = new \analyze_lifetimes\Ownership_Worker($task);
        $rows = '['; $pairs = '[';
        try {
            $result = $worker->run();
            if (($result->task !== $task) || ($result->allocations !== null)) { throw new \LogicException('Lifecycle result lost provenance'); }
            $separator = ''; $parameters = $result->summary->parameters();
            for ($position = 0; $position < 2; $position++) {
                if (isset($parameters[$position])) {
                    foreach ($parameters[$position]->fields() as $path => $transition) {
                        $mutates = 'false'; if ($transition->mutates) { $mutates = 'true'; } $accessed = 'false'; if ($transition->accessed) { $accessed = 'true'; }
                        $rows .= $separator . '[' . $position . ',' . json_quote('' . $path) . ',' . $transition->required . ',' . $transition->result . ',' . $mutates . ',' . $accessed . ']'; $separator = ',';
                    }
                }
            }
            $separator = ''; foreach ($result->summary->distinct() as $pair) { $pairs .= $separator . json_quote(\analyze_lifetimes\Resource_Locations::distinct_key($pair)); $separator = ','; }
            $rejected = false; try { $worker->run(); } catch (\LogicException $error) { $rejected = true; }
            if (!$rejected) { throw new \LogicException('Ownership worker reused'); }
        } catch (\RuntimeException $error) { return '[false,' . json_quote($error->getMessage()) . ']'; }
        return '[true,' . $rows . '],' . $pairs . ']]';
    }
    private static function contracts(\check_bodies\Checked_Body $body): void {
        $empty_parameters /** vector<\analyze_lifetimes\Parameter_Effects> */ = []; $pairs /** vector<\analyze_lifetimes\Distinct_Endpoints> */ = []; $empty_result /** hash<int> */ = [];
        $summary = new \analyze_lifetimes\Ownership_Summary($empty_parameters,$pairs,$empty_result);
        $dependencies /** hash<\analyze_lifetimes\Ownership_Summary> */ = []; $dependencies['snapshot'] = $summary;
        $task = new \analyze_lifetimes\Ownership_Task('body',$body,null,$dependencies);
        $dependencies['snapshot'] = new \analyze_lifetimes\Ownership_Summary($empty_parameters,$pairs,$empty_result);
        $exported = $task->dependencies(); unset($exported['snapshot']);
        if ($task->dependency('snapshot') !== $summary) { throw new \LogicException('Task dependencies were not captured'); }
        $worker = new \analyze_lifetimes\Ownership_Worker($task); $result = $worker->run(); $analysis = $result->allocations;
        if (($result->task !== $task) || ($analysis === null)) { throw new \LogicException('Body ownership result lost provenance'); }
        if ($analysis->body !== $body) { throw new \LogicException('Body allocation identity mismatch'); }
        $transition = $result->summary->parameter(0)->at('0.0');
        if (($transition->required !== 3) || ($transition->result !== 9) || $transition->mutates || $transition->accessed) { throw new \LogicException('Wrong body ownership summary'); }
        $children /** vector<\analyze_lifetimes\Ownership_Child> */ = [];
        $subject = Probe::subject($body->local_type_for(1),$body->definition_for($body->local_type_for(1)),\type_model\LIFECYCLE_DEFAULT,$children,false);
        $children[] = new \analyze_lifetimes\Ownership_Child(0,'snapshot'); $exported_children = $subject->children(); $exported_children[] = $children[0];
        if (q_count($subject->children()) !== 0) { throw new \LogicException('Lifecycle children escaped'); }
        $order = $subject->order(); $order->body_before_members = true;
        if ($subject->order()->body_before_members) { throw new \LogicException('Lifecycle order escaped'); }
        $rejected = 0;
        try { $invalid = new \analyze_lifetimes\Ownership_Task('none',null,null,$dependencies); } catch (\InvalidArgumentException $error) { $rejected++; }
        try { $invalid = new \analyze_lifetimes\Ownership_Task('both',$body,$subject,$dependencies); } catch (\InvalidArgumentException $error) { $rejected++; }
        $lifecycle_task = new \analyze_lifetimes\Ownership_Task('lifecycle',null,$subject,$dependencies);
        try { $invalid_result = new \analyze_lifetimes\Ownership_Result($lifecycle_task,$summary,$analysis); } catch (\LogicException $error) { $rejected++; }
        if ($rejected !== 3) { throw new \LogicException('Malformed ownership task/result accepted'); }
    }
    public static function run(string $text): void {
        $f = \resource_locations_fixture\Fixture::prepare('function f(const Nested &$n): int { return 0; } return 0;'); $snapshot = Probe::snapshot($f);
        $body = \check_bodies\Body_Worker::prepare($f->input,$f->reader->annotations->bindings($f->input->owner),$snapshot)->check();
        Probe::contracts($body);
        $cases = json_read($text); for ($i = 0; $i < $cases->size(); $i++) { echo Probe::run_case($cases->at($i),$body) . "\n"; }
    }
}
