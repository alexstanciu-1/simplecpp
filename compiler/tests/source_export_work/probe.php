<?php
declare(strict_types=1);
namespace source_export_work_test;
final class Probe {
    private static function task(int $id, string $layout_text): \prepare_backend\Source_Export_Task {
        $policy = new \type_model\Lifetime_Policy(); $policy->copy = 1;
        $operations /** vector<\type_model\Lifecycle_Operation> */ = [];
        $life = new \type_model\Lifetime_Contract($policy,$operations);
        $definition = new \type_model\Named_Definition('R' . $id,'',\type_model\Representation::structure(0,0),$life,null,'',false,false,true);
        $config = new \prepare_backend\Backend_Configuration('b','t',$layout_text,'','','a','r'); $lineage = new \type_model\Type_Lineage();
        $fields /** vector<\type_model\Type_Member> */ = []; $children /** hash<\prepare_backend\Layout_Dependency,int> */ = [];
        $dependency = new \prepare_backend\Layout_Dependency($id,$definition,$fields,$children); $offsets /** vector<int> */ = [];
        $layout = new \prepare_backend\Storage_Layout($definition,$config,$fields,'{}',1,1,$offsets,$lineage,$dependency);
        $project = new \compile\Native_Project('p','/src','/out'); $arguments /** vector<\resolve_types\Export_Argument> */ = [];
        $identity = \resolve_types\Export_Type_Identity::source('p','r.phs','','R' . $id,$arguments);
        $identities /** hash<\resolve_types\Export_Type_Identity,int> */ = []; $identities[$id] = $identity;
        $capabilities /** hash<\prepare_backend\Source_Export_Capability> */ = [];
        $members /** vector<\type_model\Lifecycle_Member> */ = [];
        foreach (\prepare_backend\Source_Export_Roles::all() as $role) {
            $name = \prepare_backend\Source_Export_Roles::name($role);
            if (($role === 4) || ($role === 6)) { $capabilities[$name] = new \prepare_backend\Source_Export_Capability($role,2,'deferred'); }
            else {
                $operation = \type_model\Lifecycle_Operation::source($id,'impl_' . $id . '_' . $role,$role,$members,0,'ccc',0);
                $capabilities[$name] = new \prepare_backend\Source_Export_Capability($role,0,'',$operation);
            }
        }
        return new \prepare_backend\Source_Export_Task($project,$identity,$layout,$identities,$capabilities);
    }
    private static function changed(\prepare_backend\Source_Export_Task $old, string $mode): \prepare_backend\Source_Export_Task {
        $project = new \compile\Native_Project('p','/src','/out'); $identity = $old->identity; $layout = $old->layout;
        $identities = $old->identities; $capabilities = $old->capabilities;
        if ($mode === 'project') { $project = new \compile\Native_Project('q','/src','/out'); }
        if ($mode === 'root') { $project = new \compile\Native_Project('p','/other','/out'); }
        if ($mode === 'identity') { $identity = \resolve_types\Export_Type_Identity::language('p','','R'); }
        if ($mode === 'identity_map') { $identities[9] = $identity; }
        if ($mode === 'reason') { $capabilities['move_assign'] = new \prepare_backend\Source_Export_Capability(6,2,'changed'); }
        if ($mode === 'state') { $capabilities['move_assign'] = new \prepare_backend\Source_Export_Capability(6,1,'deferred'); }
        if (($mode === 'operation') || ($mode === 'equal_task')) {
            $members /** vector<\type_model\Lifecycle_Member> */ = [];
            $link = $mode === 'operation' ? 'changed' : 'impl_1_3';
            $operation = \type_model\Lifecycle_Operation::source(1,$link,3,$members,0,'ccc',0);
            $capabilities['copy_construct'] = new \prepare_backend\Source_Export_Capability(3,0,'',$operation);
        }
        if ($mode === 'layout') { $other = Probe::task(1,'e'); $layout = $other->layout; }
        return new \prepare_backend\Source_Export_Task($project,$identity,$layout,$identities,$capabilities);
    }
    public static function run(string $text): void {
        $cases = json_read($text);
        for ($index = 0; $index < $cases->size(); $index++) {
            $fixture = $cases->at($index); $mode = $fixture->member('mode')->text(); $accepted = true; $correct = true;
            $first = Probe::task(1,'e'); $second = Probe::task(2,'e');
            $previous /** hash<\prepare_backend\Source_Type_Export,int> */ = [];
            $previous[1] = \prepare_backend\Source_Export_Work::prepare($first); $previous[2] = \prepare_backend\Source_Export_Work::prepare($second);
            foreach ($previous as $old) {
                \prepare_backend\Source_Export_Preparation::validate($old);
                foreach ($old->operations as $name => $operation) {
                    if ($operation->capability !== $old->task->capabilities[$name]) { $correct = false; }
                    if ($operation->capability->state === 0) {
                        if ($operation->implementation === null) { $correct = false; }
                        else { if ($operation->implementation->lifecycle_operation !== $operation->capability->operation) { $correct = false; } }
                    }
                }
            }
            $prior = $previous;
            if ($mode === 'new') { $empty_previous /** hash<\prepare_backend\Source_Type_Export,int> */ = []; $previous = $empty_previous; }
            $current /** hash<\prepare_backend\Source_Export_Task,int> */ = []; $current[1] = $first; $current[2] = $second;
            $full = ($mode === 'full') || ($mode === 'reordered') || ($mode === 'missing') || ($mode === 'duplicate') || ($mode === 'foreign_result') || ($mode === 'invalid_abi');
            $mutation = ($mode === 'project') || ($mode === 'root') || ($mode === 'identity') || ($mode === 'identity_map') || ($mode === 'reason') || ($mode === 'state') || ($mode === 'operation') || ($mode === 'layout');
            if ($mutation || ($mode === 'equal_task') || ($mode === 'stale_previous')) { $current[1] = Probe::changed($first,$mode === 'stale_previous' ? 'operation' : $mode); }
            if ($mode === 'removed') { $one /** hash<\prepare_backend\Source_Export_Task,int> */ = []; $one[2] = $second; $current = $one; }
            if ($mode === 'duplicate_identity') { $current[2] = new \prepare_backend\Source_Export_Task($second->project,$first->identity,$second->layout,$second->identities,$second->capabilities); }
            if ($mode === 'wrong_id') { $current[1] = $second; }
            $selected = \prepare_backend\Source_Export_Work::select($current,$previous,$full);
            if ($mode === 'stale_previous') { $none /** hash<\prepare_backend\Source_Export_Task,int> */ = []; $selected = $none; }
            if ($mode === 'stale_selection') { $selected[1] = Probe::changed($first,'equal_task'); }
            if ($fixture->member('accept')->boolean()) {
                $wanted = ($full || ($mode === 'new')) ? 2 : ($mutation ? 1 : 0);
                if (q_count($selected) !== $wanted) { $correct = false; }
            }
            $results /** vector<\prepare_backend\Source_Type_Export> */ = [];
            try {
                if ($mode === 'nonzero_stack') { $invalid = \prepare_backend\Source_Export_Work::prepare(Probe::task(1,'A1')); }
                foreach ($selected as $id => $task) { $results[] = \prepare_backend\Source_Export_Work::prepare($task); }
                if ($mode === 'missing') { $empty /** vector<\prepare_backend\Source_Type_Export> */ = []; $results = $empty; }
                if ($mode === 'duplicate') { $results[] = $results[0]; }
                if ($mode === 'foreign_result') { $results[0] = \prepare_backend\Source_Export_Work::prepare(Probe::changed($first,'equal_task')); }
                if ($mode === 'unexpected') { $results[] = $previous[1]; }
                if ($mode === 'reordered') { $reverse /** vector<\prepare_backend\Source_Type_Export> */ = [$results[1],$results[0]]; $results = $reverse; }
                if ($mode === 'invalid_abi') {
                    $bad_rows = $results[0]->operations; $copy = $bad_rows['copy_construct']; $params /** vector<\prepare_backend\Abi_Parameter> */ = [];
                    $bad_rows['copy_construct'] = new \prepare_backend\Source_Operation_Export($copy->capability,$copy->implementation,new \prepare_backend\Abi_Target('bad','ccc','i32',$params));
                    $results[0] = new \prepare_backend\Source_Type_Export($results[0]->task,$bad_rows);
                }
                $join = new \prepare_backend\Source_Export_Join($current,$previous,$selected); $joined = $join->join($results);
                if (!$fixture->member('accept')->boolean()) { $correct = false; }
                else {
                    if (q_count($joined) !== q_count($current)) { $correct = false; }
                    foreach ($current as $id => $task) {
                        if (isset($selected[$id])) { if ($joined[$id]->task !== $task) { $correct = false; } }
                        else { if ($joined[$id] !== $previous[$id]) { $correct = false; } }
                    }
                }
            } catch (\LogicException $error) { $accepted = false; }
            if (($prior[1]->task !== $first) || ($prior[2]->task !== $second)) { $correct = false; }
            echo (($accepted === $fixture->member('accept')->boolean()) && $correct) ? "true\n" : "false\n";
        }
    }
}
