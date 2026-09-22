<?php
declare(strict_types=1);
namespace layout_join_test;
final class Probe {
    private static function other_config(): \prepare_backend\Backend_Configuration { return new \prepare_backend\Backend_Configuration('b','t','e','','','a','r'); }
    private static function definition_copy(\type_model\Named_Definition $definition): \type_model\Named_Definition {
        return new \type_model\Named_Definition($definition->name,$definition->namespace_name,$definition->representation,$definition->lifetime,
            $definition->signed,$definition->integer_family,$definition->wrapping_addition,$definition->ordered_comparison,$definition->struct_field,null,$definition->native_layout);
    }
    private static function task_copy(\prepare_backend\Layout_Task $task, string $mode): \prepare_backend\Layout_Task {
        $id = $task->type_id; $definition = $task->definition; $config = $task->configuration; $input = $task->input;
        $fields = $task->fields; $spellings = $task->field_types; $aligned = $task->aligned;
        if ($mode === 'unknown_task') { $id = 99; }
        elseif ($mode === 'task_definition') { $definition = Probe::definition_copy($definition); }
        elseif ($mode === 'task_config') { $config = Probe::other_config(); }
        elseif ($mode === 'task_input') { $input = new \prepare_backend\Layout_Input($input->lineage,$input->roots,$input->dependencies); }
        elseif ($mode === 'task_fields') { $empty /** vector<\type_model\Type_Member> */ = []; $fields = $empty; }
        elseif ($mode === 'task_type_count') { $empty_types /** vector<string> */ = []; $spellings = $empty_types; }
        elseif ($mode === 'task_field_identity') { $fields[0] = new \type_model\Type_Member($fields[0]->type_id,$fields[0]->name,$fields[0]->writable); }
        elseif ($mode === 'task_field_spelling') { $spellings[0] = 'wrong'; }
        elseif ($mode === 'task_policy') { $aligned = !$aligned; }
        return new \prepare_backend\Layout_Task($id,$definition,$fields,$spellings,$config,$task->command,$input,$task->native_command,$aligned);
    }
    private static function layout(\prepare_backend\Layout_Task $task, string $mode): \prepare_backend\Storage_Layout {
        $definition = $task->definition; $config = $task->configuration; $fields = $task->fields;
        $lineage = $task->input->lineage; $dependency = $task->input->dependencies[$task->type_id];
        $size = q_count($fields); $alignment = 1; $offsets /** vector<int> */ = [];
        $spelling = \prepare_backend\LLVM_Storage::compound($task->input,$task->type_id);
        if ($task->aligned) { $alignment = 8; $size = $size*8; $spelling = '[' . $size . ' x i8]'; }
        foreach ($fields as $index => $field) { $offsets[] = $index*$alignment; }
        if ($mode === 'provider_offset') { if ($task->type_id === 3) { $size = 3; } }
        if ($mode === 'result_definition') { $definition = Probe::definition_copy($definition); }
        elseif (($mode === 'result_config') || ($mode === 'stale_previous')) { $config = Probe::other_config(); }
        elseif (($mode === 'result_lineage') || ($mode === 'stale_lineage')) { $lineage = new \type_model\Type_Lineage(); }
        elseif ($mode === 'result_dependency') { $dependency = new \prepare_backend\Layout_Dependency($dependency->type_id,$dependency->definition,$dependency->fields,$dependency->children); }
        elseif ($mode === 'result_field_identity') { $fields[0] = new \type_model\Type_Member($fields[0]->type_id,$fields[0]->name,$fields[0]->writable); }
        elseif ($mode === 'result_spelling') { $spelling = 'wrong'; }
        return new \prepare_backend\Storage_Layout($definition,$config,$fields,$spelling,$size,$alignment,$offsets,$lineage,$dependency);
    }
    public static function run(string $text): void {
        $cases = json_read($text);
        for ($case_index = 0; $case_index < $cases->size(); $case_index++) {
            $fixture = $cases->at($case_index); $mode = $fixture->member('mode')->text();
            $types = \type_model\Type_Store::fresh(new \type_model\Type_Context('c','p','t'));
            $operations /** vector<\type_model\Lifecycle_Operation> */ = []; $life = new \type_model\Lifetime_Contract(new \type_model\Lifetime_Policy(),$operations);
            $declared = $types->declare_type('Scalar',''); $declared = $types->declare_type('R2',''); $declared = $types->declare_type('R3','');
            $scalar = $types->intern_integer(8); if ($mode === 'opaque') { $scalar = $types->intern_opaque(8,8); }
            $types->set_representation(1,$scalar);
            $definition = new \type_model\Named_Definition('Scalar','',\type_model\Representation::integer(8),$life,true,'',false,false,true);
            if ($mode === 'opaque') { $definition = new \type_model\Named_Definition('Scalar','',$types->representation_by_id($scalar),$life,null,'',false,false,true); }
            $types->bind_definition(1,$definition);
            for ($id = 2; $id < 4; $id++) {
                $fields /** vector<\type_model\Type_Member> */ = [];
                for ($index = 0; $index < $id-1; $index++) { $fields[] = new \type_model\Type_Member(1,'f' . $index,true); }
                $representation = $types->intern_structure($fields); $types->set_representation($id,$representation);
                $record = new \type_model\Named_Definition('R' . $id,'',$types->representation_by_id($representation),$life,null,'',false,false,true);
                if ($id === 3) {
                    if (string_byte_starts_with($mode,'provider')) {
                        $offsets /** vector<int> */ = [0,1]; $size = 2; $alignment = 1; $triple = 't'; $data_layout = 'e';
                        if ($mode === 'provider_target') { $triple = 'wrong'; }
                        elseif ($mode === 'provider_layout') { $data_layout = 'wrong'; }
                        elseif ($mode === 'provider_size') { $size = 3; }
                        elseif ($mode === 'provider_alignment') { $alignment = 2; }
                        elseif ($mode === 'provider_count') { $one /** vector<int> */ = [0]; $offsets = $one; }
                        elseif ($mode === 'provider_offset') { $offsets[1] = 2; $size = 3; }
                        $native = new \type_model\Native_Record_Layout($triple,$data_layout,$size,$alignment,$offsets);
                        $record = new \type_model\Named_Definition('R' . $id,'',$types->representation_by_id($representation),$life,null,'',false,false,true,null,$native);
                    }
                }
                $types->bind_definition($id,$record);
            }
            $roots /** vector<int> */ = [2,3]; $known /** hash<\prepare_backend\Layout_Dependency,int> */ = [];
            $input = \prepare_backend\Layout_Capture::capture($types,$roots,$known); $config = Probe::other_config();
            $commands /** vector<string> */ = []; $previous /** hash<\prepare_backend\Storage_Layout,int> */ = [];
            $all = \prepare_backend\Layout_Selection::select($input,$config,$previous,true,$commands,$commands);
            foreach ($all as $task) { $previous[$task->type_id] = Probe::layout($task,''); }
            if (($mode === 'removed') || ($mode === 'empty')) {
                $current_roots /** vector<int> */ = []; if ($mode === 'removed') { $current_roots[] = 2; }
                $input = \prepare_backend\Layout_Capture::subset($input,$current_roots);
            }
            $tasks /** vector<\prepare_backend\Layout_Task> */ = []; $results /** vector<\prepare_backend\Layout_Result> */ = [];
            if (($mode !== 'reuse') && ($mode !== 'removed') && ($mode !== 'empty') && ($mode !== 'missing_previous') && ($mode !== 'stale_previous') && ($mode !== 'stale_lineage')) {
                foreach ($all as $task) {
                    if ($mode === 'partial') { if ($task->type_id === 3) { continue; } }
                    $selected = $task;
                    if ($task->type_id === 2) { $selected = Probe::task_copy($task,$mode); }
                    $tasks[] = $selected;
                    $result_task = $selected;
                    if ($mode === 'equal_result_task') { $result_task = Probe::task_copy($selected,''); }
                    // Build result payload from the original valid selection so task corruption reaches the join.
                    $payload = Probe::layout($task,$mode); $results[] = new \prepare_backend\Layout_Result($result_task,$payload);
                }
            }
            if ($mode === 'duplicate_task') { $tasks[] = $tasks[0]; }
            elseif ($mode === 'missing_result') { $only /** vector<\prepare_backend\Layout_Result> */ = [$results[0]]; $results = $only; }
            elseif ($mode === 'duplicate_result') { $results[] = $results[0]; }
            elseif ($mode === 'unexpected_result') { $none /** vector<\prepare_backend\Layout_Task> */ = []; $tasks = $none; }
            elseif ($mode === 'reversed') { $reverse /** vector<\prepare_backend\Layout_Result> */ = [$results[1],$results[0]]; $results = $reverse; }
            elseif ($mode === 'missing_previous') { $none_previous /** hash<\prepare_backend\Storage_Layout,int> */ = []; $previous = $none_previous; }
            elseif (($mode === 'stale_previous') || ($mode === 'stale_lineage')) { $previous[3] = Probe::layout($all[1],$mode); }
            if ($mode === 'new') { $initial /** hash<\prepare_backend\Storage_Layout,int> */ = []; $previous = $initial; }
            $before = $previous; $accepted = true; $matches = true;
            try {
                $join = new \prepare_backend\Layout_Join($input,$config,$previous,$tasks); $candidate = $join->join($results);
                if (!$fixture->member('accept')->boolean()) { $matches = false; }
                if (q_count($candidate) !== q_count($input->roots)) { $matches = false; }
                $ordinal = 0;
                foreach ($candidate as $id => $layout) {
                    if ($id !== $input->roots[$ordinal]) { $matches = false; } $ordinal = $ordinal+1;
                    $expected = $layout; $found = false;
                    if (isset($previous[$id])) { $expected = $previous[$id]; $found = true; }
                    foreach ($results as $result) { if ($result->task->type_id === $id) { $expected = $result->layout; $found = true; } }
                    if (!$found) { $matches = false; }
                    if ($layout !== $expected) { $matches = false; }
                }
            } catch (\LogicException $error) { $accepted = false; }
            catch (\RuntimeException $error) { $accepted = false; }
            if (q_count($previous) !== q_count($before)) { $matches = false; }
            foreach ($before as $id => $layout) { if ($previous[$id] !== $layout) { $matches = false; } }
            echo (($accepted === $fixture->member('accept')->boolean()) && $matches) ? "true\n" : "false\n";
        }
    }
}
