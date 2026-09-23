<?php
declare(strict_types=1);
namespace resource_states_test;
final class Probe {
    private static function laws(): void {
        for ($a = 0; $a < 16; $a++) {
            if ((\analyze_lifetimes\Resource_States::compose($a,\analyze_lifetimes\RESOURCE_IDENTITY) !== $a)
                || (\analyze_lifetimes\Resource_States::compose(\analyze_lifetimes\RESOURCE_IDENTITY,$a) !== $a)) { throw new \LogicException('Resource identity failed'); }
            for ($b = 0; $b < 16; $b++) {
                for ($c = 0; $c < 16; $c++) {
                    $left = \analyze_lifetimes\Resource_States::compose(\analyze_lifetimes\Resource_States::compose($a,$b),$c);
                    $right = \analyze_lifetimes\Resource_States::compose($a,\analyze_lifetimes\Resource_States::compose($b,$c));
                    if ($left !== $right) { throw new \LogicException('Resource associativity failed'); }
                }
            }
        }
        $rejected = 0;
        try { \analyze_lifetimes\Resource_States::compose(-1,9); } catch (\InvalidArgumentException $error) { $rejected++; }
        try { \analyze_lifetimes\Resource_States::compose(9,16); } catch (\InvalidArgumentException $error) { $rejected++; }
        try { \analyze_lifetimes\Resource_States::compatible(16,3); } catch (\InvalidArgumentException $error) { $rejected++; }
        try { \analyze_lifetimes\Resource_States::compatible(9,-1); } catch (\InvalidArgumentException $error) { $rejected++; }
        try { \analyze_lifetimes\Resource_States::compatible(9,4); } catch (\InvalidArgumentException $error) { $rejected++; }
        try { \analyze_lifetimes\Resource_States::deterministic(-1); } catch (\InvalidArgumentException $error) { $rejected++; }
        if ($rejected !== 6) { throw new \LogicException('Accepted invalid resource domain'); }
    }
    public static function run(string $text): void {
        Probe::laws(); $cases = json_read($text);
        for ($i = 0; $i < $cases->size(); $i++) {
            $case_data = $cases->at($i); $kind = $case_data->member('kind')->text(); $state = $case_data->member('state')->integer(); $operand = $case_data->member('operand')->integer(); $result = 0;
            if ($kind === 'compose') { $result = \analyze_lifetimes\Resource_States::compose($state,$operand); }
            else if ($kind === 'compatible') { $result = \analyze_lifetimes\Resource_States::compatible($state,$operand); }
            else { $result = \analyze_lifetimes\Resource_States::deterministic($state); }
            echo $result . "\n";
        }
    }
}
