<?php
declare(strict_types=1);
namespace layout_contracts_test;
final class Probe {
    private static function check(bool $value): void { echo $value ? "true\n" : "false\n"; }
    public static function run(string $text): void {
        $policy = new \type_model\Lifetime_Policy(); $policy->copy = 1;
        $operations /** vector<\type_model\Lifecycle_Operation> */ = [];
        $life = new \type_model\Lifetime_Contract($policy,$operations);
        $definition = new \type_model\Named_Definition('Record','ns',\type_model\Representation::structure(0,2),$life,null,'',false,false,true);
        $config = new \prepare_backend\Backend_Configuration('backend','target','layout','','','abi','runtime');
        $lineage = new \type_model\Type_Lineage();
        $member = new \type_model\Type_Member(1,'field',true);
        $empty /** hash<\prepare_backend\Layout_Dependency,int> */ = [];
        $fields /** vector<\type_model\Type_Member> */ = [$member];
        $leaf = new \prepare_backend\Layout_Dependency(1,$definition,$fields,$empty);
        $children /** hash<\prepare_backend\Layout_Dependency,int> */ = [];
        $children[1] = $leaf;
        $dependency = new \prepare_backend\Layout_Dependency(2,$definition,$fields,$children);
        $dependencies /** hash<\prepare_backend\Layout_Dependency,int> */ = [];
        $dependencies[2] = $dependency;
        $roots /** vector<int> */ = [2];
        $input = new \prepare_backend\Layout_Input($lineage,$roots,$dependencies);
        Probe::check($input->definition_for_type(2) === $definition);
        Probe::check($input->representation_for_type(2) === $definition->representation);
        Probe::check($input->field_for(2,0) === $member);
        Probe::check($input->lineage === $lineage);
        $roots[] = 9; $dependencies[9] = $leaf; $children[9] = $leaf; $fields[] = $member;
        Probe::check(q_count($input->roots) === 1);
        Probe::check(q_count($input->dependencies) === 1);
        Probe::check(q_count($dependency->children) === 1);
        Probe::check(q_count($dependency->fields) === 1);
        $copied = $input->fields_for(2); $copied[] = $member;
        Probe::check(q_count($input->fields_for(2)) === 1);
        $missing = false;
        try { $bad_definition = $input->definition_for_type(9); } catch (\LogicException $error) { $missing = true; }
        Probe::check($missing);
        $bad_index = false;
        try { $bad_field = $input->field_for(2,-1); } catch (\LogicException $error) { $bad_index = true; }
        Probe::check($bad_index);
        $bad_end = false;
        try { $bad_field_end = $input->field_for(2,1); } catch (\LogicException $error) { $bad_end = true; }
        Probe::check($bad_end);
        $field_types /** vector<string> */ = ['i32']; $command /** vector<string> */ = ['clang','-c']; $native_command /** vector<string> */ = [];
        $task = new \prepare_backend\Layout_Task(2,$definition,$input->fields_for(2),$field_types,$config,$command,'launcher',$input,$native_command,true);
        $command[] = 'changed';
        Probe::check(($task->input === $input) && ($task->configuration === $config) && $task->aligned && (q_count($task->command) === 2));
        Probe::check(q_is_int(1));
        Probe::check(!q_is_int(true));
        Probe::check(!q_is_int('1'));
        Probe::check(!q_is_int(null));
        $cases = json_read($text);
        for ($case_index = 0; $case_index < $cases->size(); $case_index++) {
            $fixture = $cases->at($case_index); $members /** vector<\type_model\Type_Member> */ = []; $offsets /** vector<int> */ = [];
            for ($ordinal = 0; $ordinal < $fixture->member('fields')->integer(); $ordinal++) { $members[] = $member; }
            $values = $fixture->member('offsets');
            for ($ordinal = 0; $ordinal < $values->size(); $ordinal++) { $offsets[] = $values->at($ordinal)->integer(); }
            $accepted = true; $matches = true;
            try {
                $layout = new \prepare_backend\Storage_Layout($definition,$config,$members,'{i32,i32}',$fixture->member('size')->integer(),$fixture->member('alignment')->integer(),$offsets,$lineage,$dependency);
                $result = new \prepare_backend\Layout_Result($task,$layout);
                $matches = ($result->task === $task) && ($result->layout === $layout) && ($layout->definition === $definition) && ($layout->lineage === $lineage) && ($layout->dependency === $dependency) && ($layout->configuration === $config);
                $offsets[] = 99; $members[] = $member;
                if ((q_count($layout->offsets) !== $values->size()) || (q_count($layout->fields) !== $fixture->member('fields')->integer())) { $matches = false; }
            } catch (\LogicException $error) { $accepted = false; }
            Probe::check(($accepted === $fixture->member('accept')->boolean()) && $matches);
        }
    }
}
