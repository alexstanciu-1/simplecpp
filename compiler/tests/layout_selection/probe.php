<?php
declare(strict_types=1);
namespace layout_selection_test;
final class Probe {
    public static function run(string $text): void {
        $cases = json_read($text);
        for ($case_index = 0; $case_index < $cases->size(); $case_index++) {
            $fixture = $cases->at($case_index); $rows = $fixture->member('nodes');
            $types = \type_model\Type_Store::fresh(new \type_model\Type_Context('c','p','t'));
            $operations /** vector<\type_model\Lifecycle_Operation> */ = [];
            $life = new \type_model\Lifetime_Contract(new \type_model\Lifetime_Policy(),$operations);
            for ($index = 0; $index < $rows->size(); $index++) { $declared = $types->declare_type('T' . ($index+1),''); }
            for ($index = 0; $index < $rows->size(); $index++) {
                $row = $rows->at($index); $id = $index+1; $kind = $row->member('kind')->text();
                $shape = \type_model\Representation::integer(8);
                if ($kind === 'integer') { $shape = \type_model\Representation::integer($row->member('width')->integer()); }
                elseif ($kind === 'float') { $shape = \type_model\Representation::floating($row->member('format')->text()); }
                elseif ($kind === 'opaque') { $shape = \type_model\Representation::opaque($row->member('size')->integer(),$row->member('alignment')->integer()); }
                elseif ($kind === 'array') { $shape = \type_model\Representation::fixed_array($row->member('children')->at(0)->integer(),$row->member('count')->integer()); }
                $representation = 0;
                if ($kind === 'record') {
                    $fields /** vector<\type_model\Type_Member> */ = []; $children = $row->member('children');
                    for ($j = 0; $j < $children->size(); $j++) { $fields[] = new \type_model\Type_Member($children->at($j)->integer(),'f' . $j,true); }
                    $representation = $types->intern_structure($fields);
                } elseif ($kind === 'integer') { $representation = $types->intern_integer($shape->bit_width()); }
                elseif ($kind === 'float') { $representation = $types->intern_float($shape->floating_format()); }
                elseif ($kind === 'opaque') { $representation = $types->intern_opaque($shape->opaque_size(),$shape->opaque_alignment()); }
                else { $representation = $types->intern_array($shape->element(),$shape->member_count()); }
                $types->set_representation($id,$representation); $shape = $types->representation_by_id($representation);
                $definition = new \type_model\Named_Definition('temporary','',\type_model\Representation::void_type(),null,null,'',false,false,false);
                if ($kind === 'integer') { $definition = new \type_model\Named_Definition('T' . $id,'',$shape,$life,true,'',false,false,true); }
                else { $definition = new \type_model\Named_Definition('T' . $id,'',$shape,$life,null,'',false,false,false); }
                $types->bind_definition($id,$definition);
            }
            $roots /** vector<int> */ = []; $root_rows = $fixture->member('roots');
            for ($i = 0; $i < $root_rows->size(); $i++) { $roots[] = $root_rows->at($i)->integer(); }
            $known /** hash<\prepare_backend\Layout_Dependency,int> */ = [];
            $captured = \prepare_backend\Layout_Capture::capture($types,$roots,$known);
            // Preserve requested root order to prove selection does not re-sort its supplied snapshot.
            $input = new \prepare_backend\Layout_Input($captured->lineage,$roots,$captured->dependencies);
            $config = new \prepare_backend\Backend_Configuration('b','t','e','','','a','r');
            $command /** vector<string> */ = ['clang','-target','t']; $native_command /** vector<string> */ = ['clang++','-S'];
            $previous /** hash<\prepare_backend\Storage_Layout,int> */ = [];
            $tasks = \prepare_backend\Layout_Selection::select($input,$config,$previous,false,$command,$native_command);
            $ok = q_count($tasks) === q_count($roots);
            foreach ($tasks as $index => $task) {
                $id = $roots[$index]; $node = $input->dependencies[$id];
                if (($task->type_id !== $id) || ($task->definition !== $node->definition) || ($task->configuration !== $config) || ($task->input !== $input)) { $ok = false; }
                if (($task->command[2] !== 't') || ($task->native_command[1] !== '-S')) { $ok = false; }
                if ($task->aligned !== $fixture->member('aligned')->at($index)->boolean()) { $ok = false; }
                if (\prepare_backend\LLVM_Storage::compound($input,$id) !== $fixture->member('texts')->at($index)->text()) { $ok = false; }
                foreach ($task->fields as $j => $field) {
                    if ($field !== $node->fields[$j]) { $ok = false; }
                    if ($task->field_types[$j] !== $fixture->member('fields')->at($index)->at($j)->text()) { $ok = false; }
                }
                $offsets /** vector<int> */ = []; foreach ($node->fields as $j => $field) { $offsets[] = $j; }
                $previous[$id] = new \prepare_backend\Storage_Layout($node->definition,$config,$node->fields,'fixture',q_count($node->fields)+1,1,$offsets,$input->lineage,$node);
            }
            $reuse = \prepare_backend\Layout_Selection::select($input,$config,$previous,false,$command,$native_command);
            $full = \prepare_backend\Layout_Selection::select($input,$config,$previous,true,$command,$native_command);
            $other = new \prepare_backend\Backend_Configuration('b','t','e','','','a','r');
            $changed = \prepare_backend\Layout_Selection::select($input,$other,$previous,false,$command,$native_command);
            if ((q_count($reuse) !== 0) || (q_count($full) !== q_count($roots)) || (q_count($changed) !== q_count($roots))) { $ok = false; }
            $partial /** hash<\prepare_backend\Storage_Layout,int> */ = [];
            if (q_count($roots) > 0) { $partial[$roots[0]] = $previous[$roots[0]]; }
            $remaining = \prepare_backend\Layout_Selection::select($input,$config,$partial,false,$command,$native_command);
            $expected_remaining = q_count($roots); if ($expected_remaining > 0) { $expected_remaining = $expected_remaining - 1; }
            if (q_count($remaining) !== $expected_remaining) { $ok = false; }
            foreach ($remaining as $j => $task) { if ($task->type_id !== $roots[$j+1]) { $ok = false; } }
            $foreign = new \prepare_backend\Layout_Input(new \type_model\Type_Lineage(),$roots,$input->dependencies);
            $foreign_tasks = \prepare_backend\Layout_Selection::select($foreign,$config,$previous,false,$command,$native_command);
            if (q_count($foreign_tasks) !== q_count($roots)) { $ok = false; }
            echo $ok ? "true\n" : "false\n";
        }
        echo \prepare_backend\LLVM_Storage::scalar(\type_model\Representation::void_type()) === 'void' ? "true\n" : "false\n";
        $bad /** vector<\type_model\Representation> */ = [\type_model\Representation::void_type(),\type_model\Representation::pointer(1,0),\type_model\Representation::byte_span(),\type_model\Representation::structure(0,0)];
        foreach ($bad as $shape) {
            $rejected = false;
            try { $value = \prepare_backend\LLVM_Storage::storage($shape); } catch (\LogicException $error) { $rejected = true; }
            echo $rejected ? "true\n" : "false\n";
        }
    }
}
