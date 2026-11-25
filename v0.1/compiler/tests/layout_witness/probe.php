<?php
declare(strict_types=1);
namespace layout_witness_test;
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
            $result = '';
            try {
                $witness = \prepare_backend\Native_Layout::source($input,$roots[0]);
                $result = '{"source":' . json_quote($witness->source) . ',"primitives":['; $separator = '';
                foreach ($witness->primitives as $id => $type) {
                    $result = $result . $separator . '[' . $id . ',' . json_quote($type) . ']'; $separator = ',';
                }
                $result = $result . ']}';
            } catch (\LogicException $error) { $result = '{"rejected":true}'; }
            echo $result . "\n";
        }
    }
}
