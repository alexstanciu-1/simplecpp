<?php
declare(strict_types=1);
namespace ownership_records_test;
final class Probe {
    private static function snapshots(): void {
        $flow = new \analyze_lifetimes\Resource_Flow_State(); $flow->states['1'] = 9; $flow->mutations['1'] = true;
        $copy = $flow->copy(); $copy->states['1'] = 5; $copy->states['2:0'] = 10; unset($copy->mutations['1']);
        if (($flow->states['1'] !== 9) || (q_count($flow->states) !== 1) || !isset($flow->mutations['1'])) { throw new \LogicException('Flow copy changed entry'); }
        if (($copy->states['1'] !== 5) || (q_count($copy->states) !== 2) || (q_count($copy->mutations) !== 0)) { throw new \LogicException('Flow copy lost changes'); }
        $incoming /** hash<int> */ = []; $incoming['1:0'] = 3; $observations = new \analyze_lifetimes\Ownership_Observations($incoming);
        $incoming['1:0'] = 1; $observations->required['1:0'] = 2; $observations->mutated['1:0'] = true;
        if (($incoming['1:0'] !== 1) || ($observations->required['1:0'] !== 2) || (q_count($observations->returns) !== 0) || (q_count($observations->result) !== 0) || (q_count($observations->accessed) !== 0) || (q_count($observations->distinct) !== 0)) { throw new \LogicException('Observation boundary failed'); }
        $writer = new \analyze_lifetimes\Resource_Transition(2,5,true,true);
        $reader = new \analyze_lifetimes\Resource_Transition(2,9,false,true);
        $fields /** hash<\analyze_lifetimes\Resource_Transition> */ = []; $fields['0'] = $writer; $fields['0.1'] = $reader;
        $first = new \analyze_lifetimes\Parameter_Effects(0,$fields); $fields['0'] = $reader;
        $second = new \analyze_lifetimes\Parameter_Effects(2,$fields);
        $parameters /** vector<\analyze_lifetimes\Parameter_Effects> */ = [$first,$second];
        $pairs /** vector<\analyze_lifetimes\Distinct_Endpoints> */ = [new \analyze_lifetimes\Distinct_Endpoints('2:0','0:0'),new \analyze_lifetimes\Distinct_Endpoints('0:0','2:0')];
        $result /** hash<int> */ = []; $result['0'] = 10; $result[''] = 5;
        $summary = new \analyze_lifetimes\Ownership_Summary($parameters,$pairs,$result);
        $parameters[0] = $second; $result['0'] = 5;
        $exported = $summary->parameter(0)->fields(); $exported['0'] = $reader;
        $exported_result = $summary->result(); $exported_result['0'] = 5;
        $exported_parameters = $summary->parameters(); unset($exported_parameters[0]);
        $distinct = $summary->distinct(); unset($distinct['0:0|2:0']);
        if ($summary->parameter(0)->at('0') !== $writer) { throw new \LogicException('Summary lost immutable transition identity'); }
        if (($summary->result()['0'] !== 10) || (q_count($summary->parameters()) !== 2) || (q_count($summary->distinct()) !== 1) || (q_count($distinct) !== 0)) { throw new \LogicException('Summary membership escaped'); }
        $path /** vector<int> */ = [0]; $location = new \analyze_lifetimes\Resource_Location(1,$path);
        $bound = new \analyze_lifetimes\Bound_Resource_Effect($location,$writer);
        if (($bound->location !== $location) || ($bound->transition !== $writer)) { throw new \LogicException('Bound effect lost identity'); }
        $empty_parameters /** vector<\analyze_lifetimes\Parameter_Effects> */ = [];
        $empty_pairs /** vector<\analyze_lifetimes\Distinct_Endpoints> */ = []; $empty_result /** hash<int> */ = [];
        $empty = new \analyze_lifetimes\Ownership_Summary($empty_parameters,$empty_pairs,$empty_result);
        if ((q_count($empty->parameters()) !== 0) || (q_count($empty->result()) !== 0)) { throw new \LogicException('Empty summary rejected'); }
        $rejected = 0;
        try { $bad = new \analyze_lifetimes\Parameter_Effects(-1,$fields); } catch (\InvalidArgumentException $error) { $rejected++; }
        $bad_fields /** hash<\analyze_lifetimes\Resource_Transition> */ = []; $bad_fields['01'] = $reader;
        try { $bad = new \analyze_lifetimes\Parameter_Effects(0,$bad_fields); } catch (\LogicException $error) { $rejected++; }
        $duplicates /** vector<\analyze_lifetimes\Parameter_Effects> */ = [$first,$first];
        try { $bad_summary = new \analyze_lifetimes\Ownership_Summary($duplicates,$empty_pairs,$empty_result); } catch (\InvalidArgumentException $error) { $rejected++; }
        $missing /** vector<\analyze_lifetimes\Distinct_Endpoints> */ = [new \analyze_lifetimes\Distinct_Endpoints('0:0','1:0')];
        $valid_parameters /** vector<\analyze_lifetimes\Parameter_Effects> */ = [$first];
        try { $bad_summary = new \analyze_lifetimes\Ownership_Summary($valid_parameters,$missing,$empty_result); } catch (\InvalidArgumentException $error) { $rejected++; }
        $missing_field /** vector<\analyze_lifetimes\Distinct_Endpoints> */ = [new \analyze_lifetimes\Distinct_Endpoints('0:0','0:2')];
        try { $bad_summary = new \analyze_lifetimes\Ownership_Summary($valid_parameters,$missing_field,$empty_result); } catch (\InvalidArgumentException $error) { $rejected++; }
        $ambiguous /** hash<int> */ = []; $ambiguous['0'] = 15;
        try { $bad_summary = new \analyze_lifetimes\Ownership_Summary($empty_parameters,$empty_pairs,$ambiguous); } catch (\InvalidArgumentException $error) { $rejected++; }
        try { $missing_parameter = $summary->parameter(1); } catch (\OutOfBoundsException $error) { $rejected++; }
        try { $missing_transition = $first->at('2'); } catch (\OutOfBoundsException $error) { $rejected++; }
        $bad_mask /** hash<int> */ = []; $bad_mask['1'] = 4;
        try { $bad_observations = new \analyze_lifetimes\Ownership_Observations($bad_mask); } catch (\InvalidArgumentException $error) { $rejected++; }
        $self_pair /** vector<\analyze_lifetimes\Distinct_Endpoints> */ = [new \analyze_lifetimes\Distinct_Endpoints('0:0','0:0')];
        try { $bad_summary = new \analyze_lifetimes\Ownership_Summary($valid_parameters,$self_pair,$empty_result); } catch (\InvalidArgumentException $error) { $rejected++; }
        if ($rejected !== 10) { throw new \LogicException('Malformed ownership contract accepted'); }
    }
    public static function run(string $text): void {
        Probe::snapshots(); $cases = json_read($text);
        for ($i = 0; $i < $cases->size(); $i++) {
            $input = $cases->at($i);
            try {
                $row = new \analyze_lifetimes\Resource_Transition($input->member('required')->integer(),$input->member('result')->integer(),$input->member('mutates')->boolean(),$input->member('accessed')->boolean());
                $mutates = 'false'; if ($row->mutates) { $mutates = 'true'; }
                $accessed = 'false'; if ($row->accessed) { $accessed = 'true'; }
                echo '[' . $row->required . ',' . $row->result . ',' . $mutates . ',' . $accessed . "]\n";
            } catch (\InvalidArgumentException $error) { echo "false\n"; }
        }
    }
}
