<?php
declare(strict_types=1);
namespace resource_calls_test;
final class Probe {
    private static function transition(string $kind): \analyze_lifetimes\Resource_Transition {
        $required = 3; $result = 9; $mutates = false; $accessed = true;
        if ($kind === 'release') { $required = 2; $result = 5; $mutates = true; }
        else if ($kind === 'acquire') { $required = 1; $result = 10; $mutates = true; }
        else if ($kind === 'mutate') { $required = 2; $mutates = true; }
        else if ($kind === 'inspect') { $required = 2; }
        else if ($kind === 'unused') { $accessed = false; }
        else if ($kind !== 'observe') { throw new \LogicException('Unknown test transition'); }
        return new \analyze_lifetimes\Resource_Transition($required,$result,$mutates,$accessed);
    }
    private static function run_case(\scpp\Json_View $input): string {
        $path /** vector<int> */ = [0];
        $left = new \analyze_lifetimes\Resource_Location(1,$path); $right = new \analyze_lifetimes\Resource_Location(2,$path);
        $locations /** hash<\analyze_lifetimes\Resource_Location> */ = []; $locations['1:0'] = $left; $locations['2:0'] = $right;
        $parameters /** hash<bool> */ = []; $incoming /** hash<int> */ = [];
        if ($input->member('parameter')->boolean()) { $parameters['1:0'] = true; $parameters['2:0'] = true; $incoming['1:0'] = 3; $incoming['2:0'] = 3; }
        $flow = new \analyze_lifetimes\Resource_Flow_State(); $flow->states['1:0'] = $input->member('state')->integer(); $flow->states['2:0'] = 10;
        if ($input->member('preceding')->boolean()) { $flow->mutations['2:0'] = true; }
        $observations = new \analyze_lifetimes\Ownership_Observations($incoming);
        $calls = new \analyze_lifetimes\Resource_Calls($locations,$parameters,null);
        if ($input->member('validate')->boolean()) { $calls = new \analyze_lifetimes\Resource_Calls($locations,$parameters,$observations); }
        $first_fields /** hash<\analyze_lifetimes\Resource_Transition> */ = []; $first_fields[''] = Probe::transition($input->member('first')->text());
        $second_fields /** hash<\analyze_lifetimes\Resource_Transition> */ = []; $second_fields[''] = Probe::transition($input->member('second')->text());
        $first = new \analyze_lifetimes\Parameter_Effects(0,$first_fields); $second = new \analyze_lifetimes\Parameter_Effects(1,$second_fields);
        $rows /** vector<\analyze_lifetimes\Parameter_Effects> */ = [$first,$second];
        $distinct /** vector<\analyze_lifetimes\Distinct_Endpoints> */ = [];
        if ($input->member('distinct')->boolean()) { $distinct[] = new \analyze_lifetimes\Distinct_Endpoints('0:','1:'); }
        $result /** hash<int> */ = []; $summary = new \analyze_lifetimes\Ownership_Summary($rows,$distinct,$result);
        $operands /** hash<\analyze_lifetimes\Resource_Location,int> */ = []; $operands[0] = $left; $operands[1] = $right;
        if ($input->member('alias')->boolean()) { $operands[1] = $left; }
        $borrows /** vector<\analyze_lifetimes\Resource_Location> */ = []; $borrow = $input->member('borrow')->integer(); $root /** vector<int> */ = [];
        if ($borrow === 1) { $borrows[] = new \analyze_lifetimes\Resource_Location(1,$root); }
        else if ($borrow === 2) { $borrows[] = new \analyze_lifetimes\Resource_Location(2,$root); }
        else if ($borrow === 3) { $sibling /** vector<int> */ = [1]; $borrows[] = new \analyze_lifetimes\Resource_Location(1,$sibling); }
        try {
            if ($input->member('fields')->boolean()) { $calls->apply_fields($left,$first,$flow,$borrows,17); }
            else { $calls->apply_summary($summary,$operands,$flow,$borrows,17); }
        } catch (\RuntimeException $error) {}
        $failure = $calls->failure(); $reason = ''; $node = 0;
        if ($failure !== null) { $reason = $failure->reason; $node = $failure->node; }
        $required_left = -1; $required_right = -1;
        if ($input->member('validate')->boolean()) {
            if (isset($observations->required['1:0'])) { $required_left = $observations->required['1:0']; }
            if (isset($observations->required['2:0'])) { $required_right = $observations->required['2:0']; }
        }
        $keys = '['; $separator = '';
        foreach ($observations->distinct as $key => $pair) { $keys .= $separator . json_quote($key); $separator = ','; }
        $keys .= ']';
        return '[' . $flow->states['1:0'] . ',' . $flow->states['2:0'] . ',' . $required_left . ',' . $required_right . ',' . q_count($flow->mutations) . ',' . q_count($observations->mutated) . ',' . q_count($observations->accessed) . ',' . $keys . ',' . json_quote($reason) . ',' . $node . ']';
    }
    public static function run(string $text): void {
        $cases = json_read($text);
        for ($i = 0; $i < $cases->size(); $i++) { echo Probe::run_case($cases->at($i)) . "\n"; }
    }
}
