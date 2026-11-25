<?php
declare(strict_types=1);
namespace ownership_join_test;
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
    private static function summary(string $mode): \analyze_lifetimes\Ownership_Summary {
        $parameters /** vector<\analyze_lifetimes\Parameter_Effects> */ = []; $fields /** hash<\analyze_lifetimes\Resource_Transition> */ = [];
        $pairs /** vector<\analyze_lifetimes\Distinct_Endpoints> */ = []; $result /** hash<int> */ = [];
        if ($mode !== 'missing_parameter') {
            $path = '0.0'; if ($mode === 'wrong_field') { $path = '0'; }
            $post = 5; if ($mode === 'changed') { $post = 10; }
            $accessed = true; if ($mode === 'unaccessed') { $accessed = false; }
            $fields[$path] = new \analyze_lifetimes\Resource_Transition(1,$post,false,$accessed);
            $parameters[] = new \analyze_lifetimes\Parameter_Effects(0,$fields);
            if (($mode === 'const_source') || ($mode === 'move_source')) {
                $fields[$path] = new \analyze_lifetimes\Resource_Transition(2,5,true,true);
                $parameters[] = new \analyze_lifetimes\Parameter_Effects(1,$fields);
            }
        }
        if ($mode === 'extra_result') { $result['0.0'] = 10; }
        return new \analyze_lifetimes\Ownership_Summary($parameters,$pairs,$result);
    }
    private static function run_case(string $mode, \check_bodies\Checked_Body $body): string {
        $children /** vector<\analyze_lifetimes\Ownership_Child> */ = []; $dependencies /** hash<\analyze_lifetimes\Ownership_Summary> */ = [];
        $kind = \type_model\LIFECYCLE_DEFAULT;
        if ($mode === 'const_source') { $kind = \type_model\LIFECYCLE_COPY; }
        if ($mode === 'move_source') { $kind = \type_model\LIFECYCLE_MOVE; }
        $subject = new \analyze_lifetimes\Ownership_Lifecycle($body->local_type_for(1),$body->definition_for($body->local_type_for(1)),$kind,$children,null);
        $old_task = new \analyze_lifetimes\Ownership_Task('a',null,$subject,$dependencies);
        $old_result = new \analyze_lifetimes\Ownership_Result($old_task,Probe::summary('normal'),null);
        $task = new \analyze_lifetimes\Ownership_Task('a',null,$subject,$dependencies);
        $fresh = new \analyze_lifetimes\Ownership_Result($task,Probe::summary($mode),null);
        $tasks /** vector<\analyze_lifetimes\Ownership_Task> */ = [$task];
        $previous /** hash<\analyze_lifetimes\Ownership_Result> */ = []; $previous['a'] = $old_result;
        $results /** vector<\analyze_lifetimes\Ownership_Result> */ = [$fresh];
        if ($mode === 'missing_result') { $empty /** vector<\analyze_lifetimes\Ownership_Result> */ = []; $results = $empty; }
        if ($mode === 'duplicate_result') { $results[] = $fresh; }
        if ($mode === 'duplicate_task') { $tasks[] = $task; }
        if ($mode === 'stale') { $results[0] = $old_result; }
        if ($mode === 'unexpected') {
            $foreign = new \analyze_lifetimes\Ownership_Task('foreign',null,$subject,$dependencies); $results[0] = new \analyze_lifetimes\Ownership_Result($foreign,Probe::summary('normal'),null);
        }
        if ($mode === 'reverse_batch') {
            $second = new \analyze_lifetimes\Ownership_Task('b',null,$subject,$dependencies); $tasks[] = $second;
            $results[0] = new \analyze_lifetimes\Ownership_Result($second,Probe::summary('normal'),null); $results[] = $fresh;
        }
        if ($mode === 'body') {
            $body_task = new \analyze_lifetimes\Ownership_Task('body',$body,null,$dependencies); $tasks[0] = $body_task;
            $worker = new \analyze_lifetimes\Ownership_Worker($body_task); $results[0] = $worker->run();
        }
        try {
            $join = new \analyze_lifetimes\Ownership_Join($tasks,$previous); $accepted = $join->join($results);
            if ($mode === 'body') {
                if ($accepted['body'] !== $results[0]) { throw new \RuntimeException('Body result changed'); }
            } else {
                $current = $accepted['a'];
                if ($current->task !== $task) { throw new \RuntimeException('Stale task provenance reused'); }
                $same = \analyze_lifetimes\Ownership_Contracts::same($fresh->summary,$old_result->summary);
                if ($same) { if ($current->summary !== $old_result->summary) { throw new \RuntimeException('Equal summary identity lost'); } }
                else { if ($current->summary !== $fresh->summary) { throw new \RuntimeException('Changed summary reused'); } }
                if ($mode === 'reverse_batch') { if (q_count($accepted) !== 2) { throw new \RuntimeException('Reordered batch incomplete'); } }
            }
        } catch (\LogicException $error) {
            if (($previous['a'] !== $old_result) || ($old_result->task !== $old_task)) { throw new \RuntimeException('Rejected batch mutated previous result'); }
            return json_quote($error->getMessage());
        }
        return json_quote('accepted');
    }
    private static function comparison(): void {
        $base_fields /** hash<\analyze_lifetimes\Resource_Transition> */ = [];
        $base_fields['0.0'] = new \analyze_lifetimes\Resource_Transition(3,9,false,true);
        $base_fields['0.1'] = new \analyze_lifetimes\Resource_Transition(3,9,false,true);
        $base_parameters /** vector<\analyze_lifetimes\Parameter_Effects> */ = [new \analyze_lifetimes\Parameter_Effects(0,$base_fields),new \analyze_lifetimes\Parameter_Effects(1,$base_fields)];
        $base_pairs /** vector<\analyze_lifetimes\Distinct_Endpoints> */ = [new \analyze_lifetimes\Distinct_Endpoints('0:0.0','1:0.0')];
        $base_result /** hash<int> */ = []; $base_result['0'] = 5;
        $base = new \analyze_lifetimes\Ownership_Summary($base_parameters,$base_pairs,$base_result);
        for ($mode = 0; $mode < 9; $mode++) {
            $fields /** hash<\analyze_lifetimes\Resource_Transition> */ = [];
            $fields['0.1'] = $base_fields['0.1'];
            $required = 3; $result = 9; $mutates = false; $accessed = true;
            if ($mode === 1) { $required = 2; } else if ($mode === 2) { $result = 5; }
            else if ($mode === 3) { $mutates = true; } else if ($mode === 4) { $accessed = false; }
            $fields['0.0'] = new \analyze_lifetimes\Resource_Transition($required,$result,$mutates,$accessed);
            if ($mode === 5) { unset($fields['0.1']); }
            $parameters /** vector<\analyze_lifetimes\Parameter_Effects> */ = [new \analyze_lifetimes\Parameter_Effects(1,$base_fields),new \analyze_lifetimes\Parameter_Effects(0,$fields)];
            if ($mode === 6) { $parameters[1] = new \analyze_lifetimes\Parameter_Effects(2,$fields); }
            $pairs /** vector<\analyze_lifetimes\Distinct_Endpoints> */ = [];
            if (($mode !== 6) && ($mode !== 7)) { $pairs[] = new \analyze_lifetimes\Distinct_Endpoints('1:0.0','0:0.0'); }
            $output /** hash<int> */ = []; $output['0'] = 5; if ($mode === 8) { $output['0'] = 10; }
            $other = new \analyze_lifetimes\Ownership_Summary($parameters,$pairs,$output);
            if (\analyze_lifetimes\Ownership_Contracts::same($base,$other) !== ($mode === 0)) { throw new \LogicException('Ownership meaning comparison failed'); }
            if (\analyze_lifetimes\Ownership_Contracts::same($other,$base) !== ($mode === 0)) { throw new \LogicException('Ownership meaning comparison is asymmetric'); }
        }
    }
    public static function run(string $text): void {
        $f = \resource_locations_fixture\Fixture::prepare('function f(const Nested &$n): int { return 0; } return 0;'); $snapshot = Probe::snapshot($f);
        $body = \check_bodies\Body_Worker::prepare($f->input,$f->reader->annotations->bindings($f->input->owner),$snapshot)->check();
        Probe::comparison();
        $cases = json_read($text); for ($i = 0; $i < $cases->size(); $i++) { echo Probe::run_case($cases->at($i)->member('mode')->text(),$body) . "\n"; }
    }
}
