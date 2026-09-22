<?php
declare(strict_types=1);
namespace layout_worker_test;
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
            $command = Probe::strings($fixture->member('command')); $native_command = Probe::strings($fixture->member('native_command'));
            $previous /** hash<\prepare_backend\Storage_Layout,int> */ = [];
            $tasks = \prepare_backend\Layout_Selection::select($input,$config,$previous,true,$command,$native_command);
            $task = $tasks[0];
            if ($fixture->member('policy_flip')->boolean()) {
                $task = new \prepare_backend\Layout_Task($task->type_id,$task->definition,$task->fields,$task->field_types,$config,$command,$input,$native_command,!$task->aligned);
            }
            $accepted = true; $matches = true;
            try {
                $result = \prepare_backend\Layout_Worker::prepare($task,$fixture->member('timeout')->integer());
                if (!$fixture->member('accept')->boolean()) { $matches = false; }
                $layout = $result->layout; $want = $fixture->member('facts');
                if (($layout->size !== $want->member('size')->integer()) || ($layout->alignment !== $want->member('alignment')->integer())) { $matches = false; }
                foreach ($layout->offsets as $index => $offset) { if ($offset !== $want->member('offsets')->at($index)->integer()) { $matches = false; } }
                $results /** vector<\prepare_backend\Layout_Result> */ = [$result];
                $join = new \prepare_backend\Layout_Join($input,$config,$previous,$tasks); $candidate = $join->join($results);
                if ($candidate[$roots[0]] !== $layout) { $matches = false; }
                $reuse = \prepare_backend\Layout_Selection::select($input,$config,$candidate,false,$command,$native_command);
                if (q_count($reuse) !== 0) { $matches = false; }
                $none /** vector<\prepare_backend\Layout_Result> */ = [];
                $reuse_join = new \prepare_backend\Layout_Join($input,$config,$candidate,$reuse); $again = $reuse_join->join($none);
                if ($again[$roots[0]] !== $layout) { $matches = false; }
                if ($fixture->member('changed')->boolean()) {
                    $fork = $types->fork(); $fork->invalidate_definition(1);
                    $wide = $fork->intern_integer(64); $fork->set_representation(1,$wide);
                    $replacement = new \type_model\Named_Definition('T1','',$fork->representation_by_id($wide),$life,true,'',false,false,true);
                    $fork->bind_definition(1,$replacement);
                    $next_input = \prepare_backend\Layout_Capture::capture($fork,$roots,$input->dependencies);
                    $next_tasks = \prepare_backend\Layout_Selection::select($next_input,$config,$candidate,false,$command,$native_command);
                    if (q_count($next_tasks) !== 1) { $matches = false; }
                    $next_result = \prepare_backend\Layout_Worker::prepare($next_tasks[0],$fixture->member('timeout')->integer());
                    $next_results /** vector<\prepare_backend\Layout_Result> */ = [$next_result];
                    $next_join = new \prepare_backend\Layout_Join($next_input,$config,$candidate,$next_tasks); $next = $next_join->join($next_results);
                    $measured = $next[$roots[0]];
                    if (($measured->size !== 16) || ($measured->alignment !== 8) || ($measured->offsets[0] !== 0) || ($measured->offsets[1] !== 8)) { $matches = false; }
                    if (($layout->size !== 8) || ($layout->alignment !== 4) || ($layout->offsets[1] !== 4) || ($candidate[$roots[0]] !== $layout)) { $matches = false; }
                    if (($input->representation_for_type(1)->bit_width() !== 8) || ($next_input->representation_for_type(1)->bit_width() !== 64)) { $matches = false; }
                }
            } catch (\LogicException $error) { $accepted = false; }
            catch (\RuntimeException $error) { $accepted = false; }
            catch (\InvalidArgumentException $error) { $accepted = false; }
            echo (($accepted === $fixture->member('accept')->boolean()) && $matches) ? "true\n" : "false\n";
        }
    }
    private static function strings(\scpp\Json_View $rows): array /** vector<string> */ {
        $out /** vector<string> */ = []; for ($index = 0; $index < $rows->size(); $index++) { $out[] = $rows->at($index)->text(); } return $out;
    }
}
