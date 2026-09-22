<?php
declare(strict_types=1);
namespace layout_measurement_test;
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
            $roots /** vector<int> */ = [$fixture->member('root')->integer()];
            $known /** hash<\prepare_backend\Layout_Dependency,int> */ = [];
            $input = \prepare_backend\Layout_Capture::capture($types,$roots,$known);
            $config = new \prepare_backend\Backend_Configuration('b',$fixture->member('triple')->text(),$fixture->member('layout')->text(),'','','a','r');
            $commands /** vector<string> */ = []; $previous /** hash<\prepare_backend\Storage_Layout,int> */ = [];
            $tasks = \prepare_backend\Layout_Selection::select($input,$config,$previous,true,$commands,'',$commands);
            $task = $tasks[0]; $accepted = true; $matches = true;
            try {
                if ($fixture->member('mode')->text() === 'facts') {
                    $names /** vector<string> */ = ['size'];
                    $facts = \prepare_backend\Layout_Facts::read($fixture->member('output')->text(),$config,$names);
                    if (!$fixture->member('accept')->boolean()) { $matches = false; }
                    if ($facts['size'] !== $fixture->member('value')->integer()) { $matches = false; }
                } else {
                    $result = \prepare_backend\Layout_Measurement::read($task,$fixture->member('output')->text(),$fixture->member('primitive')->text());
                    if (!$fixture->member('accept')->boolean()) { $matches = false; }
                    $layout = $result->layout;
                    if (($result->task !== $task) || ($layout->definition !== $task->definition) || ($layout->configuration !== $config)
                        || ($layout->lineage !== $input->lineage) || ($layout->dependency !== $input->dependencies[$roots[0]])) { $matches = false; }
                    foreach ($layout->fields as $index => $field) { if ($field !== $task->fields[$index]) { $matches = false; } }
                    $want = $fixture->member('facts');
                    if (($layout->size !== $want->member('size')->integer()) || ($layout->alignment !== $want->member('alignment')->integer())) { $matches = false; }
                    foreach ($layout->offsets as $index => $offset) { if ($offset !== $want->member('field_' . $index)->integer()) { $matches = false; } }
                    if ($layout->llvm_type !== $fixture->member('spelling')->text()) { $matches = false; }
                }
            } catch (\LogicException $error) { $accepted = false; }
            catch (\RuntimeException $error) { $accepted = false; }
            // A negative fixture must fail in the operation itself, not in later expected-result access.
            echo (($accepted === $fixture->member('accept')->boolean()) && $matches) ? "true\n" : "false\n";
        }
    }
}
