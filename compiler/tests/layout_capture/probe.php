<?php
declare(strict_types=1);
namespace layout_capture_test;
final class Probe {
    private static function ids(\scpp\Json_View $rows): array /** vector<int> */ {
        $ids /** vector<int> */ = []; for ($index = 0; $index < $rows->size(); $index++) { $ids[] = $rows->at($index)->integer(); } return $ids;
    }
    private static function has(\scpp\Json_View $rows, int $id): bool {
        for ($index = 0; $index < $rows->size(); $index++) { if ($rows->at($index)->integer() === $id) { return true; } } return false;
    }
    private static function definition(string $name, \type_model\Representation $shape, \type_model\Lifetime_Contract $life): \type_model\Named_Definition {
        if ($shape->kind() === \type_model\REPRESENTATION_INTEGER) { return new \type_model\Named_Definition($name,'',$shape,$life,true,'',false,false,true); }
        return new \type_model\Named_Definition($name,'',$shape,$life,null,'',false,false,false);
    }
    public static function run(string $text): void {
        $cases = json_read($text);
        for ($case_index = 0; $case_index < $cases->size(); $case_index++) {
            $fixture = $cases->at($case_index); $nodes = $fixture->member('nodes');
            $types = \type_model\Type_Store::fresh(new \type_model\Type_Context('c','p','t'));
            $life_operations /** vector<\type_model\Lifecycle_Operation> */ = [];
            $life = new \type_model\Lifetime_Contract(new \type_model\Lifetime_Policy(),$life_operations);
            for ($index = 0; $index < $nodes->size(); $index++) { $declared = $types->declare_type('T' . ($index + 1),''); }
            for ($index = 0; $index < $nodes->size(); $index++) {
                $row = $nodes->at($index); $id = $index + 1; $kind = $row->member('kind')->text(); $children = Probe::ids($row->member('children'));
                $representation = $types->intern_integer(64);
                if ($kind === 'record') {
                    $fields /** vector<\type_model\Type_Member> */ = [];
                    foreach ($children as $ordinal => $child) { $fields[] = new \type_model\Type_Member($child,'f' . $ordinal,true); }
                    $representation = $types->intern_structure($fields);
                } elseif ($kind === 'array') { $representation = $types->intern_array($children[0],4); }
                elseif ($kind === 'pointer') { $representation = $types->intern_pointer($children[0],0); }
                $types->set_representation($id,$representation);
                $definition = Probe::definition('T' . $id,$types->representation_by_id($representation),$life);
                $types->bind_definition($id,$definition);
            }
            $roots = Probe::ids($fixture->member('roots')); $known /** hash<\prepare_backend\Layout_Dependency,int> */ = [];
            $accepted = true; $matches = true;
            try {
                $input = \prepare_backend\Layout_Capture::capture($types,$roots,$known);
                if (!$fixture->member('accept')->boolean()) { $matches = false; }
                else {
                    $expected = $fixture->member('expected'); $ordered = $fixture->member('ordered');
                    $matches = (q_count($input->dependencies) === $expected->size()) && (q_count($input->roots) === $ordered->size()) && ($input->lineage === $types->lineage);
                    foreach ($input->roots as $slot => $root) { if ($root !== $ordered->at($slot)->integer()) { $matches = false; } }
                    $slot = 0;
                    foreach ($input->dependencies as $id => $node) {
                        if (($id !== $expected->at($slot)->integer()) || ($node->definition !== $types->definition_for_type($id))) { $matches = false; }
                        $slot = $slot + 1;
                        foreach ($node->fields as $ordinal => $field) { if ($field !== $types->field_for($id,$ordinal)) { $matches = false; } }
                        foreach ($node->children as $child => $link) { if ($link !== $input->dependencies[$child]) { $matches = false; } }
                    }
                    $again = \prepare_backend\Layout_Capture::capture($types,$roots,$input->dependencies);
                    foreach ($input->dependencies as $id => $node) { if ($again->dependencies[$id] !== $node) { $matches = false; } }
                    $subset = \prepare_backend\Layout_Capture::subset($input,Probe::ids($fixture->member('subset')));
                    $expected_subset = $fixture->member('subset_nodes'); $slot = 0;
                    if (q_count($subset->dependencies) !== $expected_subset->size()) { $matches = false; }
                    foreach ($subset->dependencies as $id => $node) {
                        if (($id !== $expected_subset->at($slot)->integer()) || ($node !== $input->dependencies[$id])) { $matches = false; } $slot = $slot + 1;
                    }
                    $changed = $fixture->member('changed')->integer(); $candidate = $types->fork();
                    if ($changed !== 0) {
                        $old = $types->definition_for_type($changed); $candidate->invalidate_definition($changed);
                        $candidate->set_representation($changed,$types->type_by_id($changed)->representation_id);
                        $replacement = Probe::definition($old->name,$old->representation,$life);
                        $candidate->bind_definition($changed,$replacement);
                    }
                    $next = \prepare_backend\Layout_Capture::capture($candidate,$roots,$input->dependencies);
                    foreach ($input->dependencies as $id => $node) {
                        $affected = Probe::has($fixture->member('affected'),$id);
                        if (($next->dependencies[$id] === $node) === $affected) { $matches = false; }
                        if (\prepare_backend\Layout_Capture::dependencies_match($node,$next->dependencies[$id]) === $affected) { $matches = false; }
                    }
                    if (q_count($input->roots) > 0) {
                        $root = $input->roots[0]; $node = $input->dependencies[$root]; $config = new \prepare_backend\Backend_Configuration('b','t','e','','','a','r');
                        $offsets /** vector<int> */ = []; for ($i = 0; $i < q_count($node->fields); $i++) { $offsets[] = $i; }
                        $layout = new \prepare_backend\Storage_Layout($node->definition,$config,$node->fields,'fixture',q_count($node->fields)+1,1,$offsets,$input->lineage,$node);
                        if (!\prepare_backend\Layout_Capture::current($layout,$input,$root,$config)) { $matches = false; }
                        $foreign = new \prepare_backend\Layout_Input(new \type_model\Type_Lineage(),$input->roots,$input->dependencies);
                        if (\prepare_backend\Layout_Capture::current($layout,$foreign,$root,$config)) { $matches = false; }
                        $other_config = new \prepare_backend\Backend_Configuration('b','t','e','','','a','r');
                        if (\prepare_backend\Layout_Capture::current($layout,$input,$root,$other_config)) { $matches = false; }
                        if (\prepare_backend\Layout_Capture::current($layout,$next,$root,$config) === Probe::has($fixture->member('affected'),$root)) { $matches = false; }
                    }
                }
            } catch (\LogicException $error) { $accepted = false; }
            catch (\InvalidArgumentException $error) { $accepted = false; }
            echo (($accepted === $fixture->member('accept')->boolean()) && $matches) ? "true\n" : "false\n";
        }
    }
}
