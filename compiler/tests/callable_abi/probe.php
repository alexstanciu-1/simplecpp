<?php
declare(strict_types=1);
namespace callable_abi_test;
final class Probe {
    private static function check(bool $ok): void { echo $ok ? "true\n" : "false\n"; }
    public static function run(): void {
        $type = \type_model\Type_Reference::provided('runtime', 'value');
        $integer = \type_model\Runtime_Abi_Position::integer(32, \type_model\ABI_EXTENSION_SIGN);
        $length = \type_model\Runtime_Abi_Position::integer(64, \type_model\ABI_EXTENSION_ZERO);
        $span = \type_model\Runtime_Abi_Position::byte_span($length);
        $borrow = \type_model\Runtime_Abi_Position::borrow(true);
        $shapes /** vector<\type_model\Runtime_Abi_Position> */ = [$integer, \type_model\Runtime_Abi_Position::borrow(false), $borrow, $span];
        for ($passing = 0; $passing < 4; $passing++) {
            for ($shape = 0; $shape < 4; $shape++) {
                $parameters /** vector<\type_model\Semantic_Parameter> */ = [new \type_model\Semantic_Parameter($type, $passing)];
                $signature = new \type_model\Semantic_Signature($parameters, new \type_model\Semantic_Result($type, \type_model\RESULT_NONE));
                $physical /** vector<\type_model\Runtime_Abi_Position> */ = [$shapes[$shape]];
                $abi = new \type_model\Runtime_Callable_Abi('link', 'c', null, $physical);
                $accepted = true;
                try { $abi->validate($signature); } catch (\InvalidArgumentException $error) { $accepted = false; }
                Probe::check($accepted === ($passing === $shape));
            }
        }
        $empty_parameters /** vector<\type_model\Semantic_Parameter> */ = [];
        $empty_physical /** vector<\type_model\Runtime_Abi_Position> */ = [];
        for ($production = 0; $production < 4; $production++) {
            for ($transport = 0; $transport < 2; $transport++) {
                for ($present = 0; $present < 2; $present++) {
                    $signature = new \type_model\Semantic_Signature($empty_parameters, new \type_model\Semantic_Result($type, $production));
                    $abi = new \type_model\Runtime_Callable_Abi('link', 'c', null, $empty_physical, $transport);
                    if ($present === 1) { $abi = new \type_model\Runtime_Callable_Abi('link', 'c', $integer, $empty_physical, $transport); }
                    $accepted = true;
                    try { $abi->validate($signature); } catch (\InvalidArgumentException $error) { $accepted = false; }
                    $expected = (($production === 0) && ($transport === 0) && ($present === 0))
                        || (($production === 1) && ($transport === 0) && ($present === 1))
                        || (($production === 2) && ($transport === 1) && ($present === 0));
                    Probe::check($accepted === $expected);
                }
            }
        }
        $extension_names /** vector<string> */ = ['', 'signext', 'zeroext'];
        for ($value = 0; $value < 3; $value++) {
            Probe::check((\type_model\Callable_Modes::extension_name($value) === $extension_names[$value]) && (\type_model\Callable_Modes::extension($extension_names[$value]) === $value));
        }
        $result_names /** vector<string> */ = ['direct', 'caller_storage'];
        for ($value = 0; $value < 2; $value++) {
            Probe::check((\type_model\Callable_Modes::result_name($value) === $result_names[$value]) && (\type_model\Callable_Modes::result($result_names[$value]) === $value));
        }
        $binding_names /** vector<string> */ = ['byte_literal', 'echo'];
        for ($value = 0; $value < 2; $value++) {
            Probe::check((\type_model\Callable_Modes::binding_name($value) === $binding_names[$value]) && (\type_model\Callable_Modes::binding($binding_names[$value]) === $value));
        }
        $conversion_names /** vector<string> */ = ['implicit', 'explicit_cast', 'condition', 'text'];
        for ($value = 0; $value < 4; $value++) {
            Probe::check((\type_model\Callable_Modes::conversion_name($value) === $conversion_names[$value]) && (\type_model\Callable_Modes::conversion($conversion_names[$value]) === $value));
        }
        $physical /** vector<\type_model\Runtime_Abi_Position> */ = [$integer, $span, $borrow];
        $direct = new \type_model\Runtime_Callable_Abi('direct', 'c', null, $physical);
        $owned = new \type_model\Runtime_Callable_Abi('owned', 'custom', null, $physical, \type_model\ABI_RESULT_CALLER_STORAGE);
        $physical[0] = $borrow;
        $first = $direct->parameter_indices(0); $middle = $direct->parameter_indices(1); $last = $direct->parameter_indices(2);
        Probe::check(($first[0] === 0) && (q_count($first) === 1));
        Probe::check(($middle[0] === 1) && ($middle[1] === 2) && (q_count($middle) === 2));
        Probe::check(($last[0] === 3) && (q_count($last) === 1));
        $first = $owned->parameter_indices(0); $middle = $owned->parameter_indices(1); $last = $owned->parameter_indices(2);
        Probe::check($first[0] === 1); Probe::check(($middle[0] === 2) && ($middle[1] === 3)); Probe::check($last[0] === 4);
        Probe::check($owned->parameter_at(0) === $integer);
        $first[0] = 99; $again = $owned->parameter_indices(0); Probe::check($again[0] === 1);
        $parameters /** vector<\type_model\Semantic_Parameter> */ = [new \type_model\Semantic_Parameter($type, 0), new \type_model\Semantic_Parameter($type, 3), new \type_model\Semantic_Parameter($type, 2)];
        $signature = new \type_model\Semantic_Signature($parameters, new \type_model\Semantic_Result($type, \type_model\RESULT_OWNED));
        $callable = new \type_model\Runtime_Callable('provider', 'id', 'name', 'ns', $signature, $owned, 0, true, 0);
        Probe::check(($callable->provider === 'provider') && ($callable->id === 'id'));
        Probe::check(($callable->name === 'name') && ($callable->namespace_name === 'ns'));
        Probe::check(($callable->signature === $signature) && ($callable->abi === $owned));
        Probe::check(($callable->language_binding === 0) && ($callable->conversion_purpose === 0) && $callable->default_literal);
        Probe::check($callable->passing_for(2) === \type_model\PASS_BORROW_MUTABLE);
        Probe::check(($span->length === $length) && ($length->bits === 64));
        for ($case_index = 0; $case_index < 9; $case_index++) {
            $rejected = false;
            try {
                if ($case_index === 0) { $bad = \type_model\Runtime_Abi_Position::integer(0, 0); }
                elseif ($case_index === 1) { $bad = \type_model\Runtime_Abi_Position::byte_span($borrow); }
                elseif ($case_index === 2) { $bad_abi = new \type_model\Runtime_Callable_Abi('bad', 'c', $span, $empty_physical); }
                elseif ($case_index === 3) { $owned->parameter_at(3); }
                elseif ($case_index === 4) { $owned->parameter_indices(-1); }
                elseif ($case_index === 5) { $callable->passing_for(3); }
                elseif ($case_index === 6) { \type_model\Callable_Modes::conversion('unknown'); }
                elseif ($case_index === 7) { \type_model\Callable_Modes::extension('unknown'); }
                else { $bad_call = new \type_model\Runtime_Callable('p', 'i', 'n', '', $signature, $direct); }
            } catch (\InvalidArgumentException $error) { $rejected = true; }
            catch (\OutOfBoundsException $bounds) { $rejected = true; }
            Probe::check($rejected);
        }
    }
}
