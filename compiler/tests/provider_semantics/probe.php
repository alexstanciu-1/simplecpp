<?php
declare(strict_types=1);
namespace provider_test;
final class Probe {
    private static function check(bool $ok): void { echo $ok ? "true\n" : "false\n"; }
    public static function run(): void {
        $named = \type_model\Type_Reference::named('Value', 'library');
        $provided = \type_model\Type_Reference::provided('runtime/v1', 'Value');
        $formal = \type_model\Type_Reference::parameter('["runtime","vector"]', 0);
        $arguments /** vector<\type_model\Type_Reference> */ = [$formal, $provided];
        $family = \type_model\Type_Reference::family('["runtime","vector"]', $arguments);
        $arguments[0] = $named;
        Probe::check($named->name() === 'Value');
        Probe::check($named->namespace_name() === 'library');
        Probe::check($provided->provider() === 'runtime/v1');
        Probe::check($provided->id() === 'Value');
        Probe::check($formal->owner() === '["runtime","vector"]');
        Probe::check($formal->parameter_slot() === 0);
        Probe::check($family->family_key() === $formal->owner());
        Probe::check($family->argument_count() === 2);
        Probe::check($family->argument_at(0) === $formal);
        Probe::check($family->argument_at(1) === $provided);
        $nested_args /** vector<\type_model\Type_Reference> */ = [$family];
        $nested = \type_model\Type_Reference::family('nested', $nested_args);
        Probe::check($nested->argument_at(0) === $family);
        $parameters /** vector<\type_model\Semantic_Parameter> */ = [];
        for ($mode = 0; $mode < 4; $mode++) {
            $parameter = new \type_model\Semantic_Parameter($formal, $mode);
            $parameters[] = $parameter;
        }
        $result = new \type_model\Semantic_Result($family, \type_model\RESULT_DEPENDENT_VALUE);
        $effect = new \type_model\Allocation_Effect(\type_model\ALLOCATION_TRANSFER, 1, 2);
        $signature = new \type_model\Semantic_Signature($parameters, $result, $effect);
        $parameters[0] = new \type_model\Semantic_Parameter($named, \type_model\PASS_VALUE);
        Probe::check($signature->parameter_count() === 4);
        Probe::check($signature->result === $result);
        Probe::check($signature->result->type === $family);
        Probe::check($signature->allocation_effect === $effect);
        for ($mode = 0; $mode < 4; $mode++) {
            $parameter = $signature->parameter_at($mode);
            Probe::check(($parameter->passing === $mode) && ($parameter->type === $formal));
            $value = new \type_model\Semantic_Result($named, $mode);
            Probe::check($value->production === $mode);
        }
        $empty_parameters /** vector<\type_model\Semantic_Parameter> */ = [];
        $empty = new \type_model\Semantic_Signature($empty_parameters, $result);
        Probe::check(($empty->parameter_count() === 0) && ($empty->allocation_effect === null));
        for ($case = 0; $case < 11; $case++) {
            $rejected = false;
            try {
                if ($case === 0) { $named->provider(); }
                elseif ($case === 1) { $provided->name(); }
                elseif ($case === 2) { $family->parameter_slot(); }
                elseif ($case === 3) { $formal->argument_count(); }
                elseif ($case === 4) { $family->argument_at(2); }
                elseif ($case === 5) { $family->argument_at(-1); }
                elseif ($case === 6) { $signature->parameter_at(4); }
                elseif ($case === 7) { $signature->parameter_at(-1); }
                elseif ($case === 8) { $bad = new \type_model\Semantic_Parameter($named, 4); }
                elseif ($case === 9) { $bad_result = new \type_model\Semantic_Result($named, -1); }
                else { $bad_formal = \type_model\Type_Reference::parameter('family', -1); }
            } catch (\LogicException $error) { $rejected = true; }
            catch (\OutOfBoundsException $bounds) { $rejected = true; }
            Probe::check($rejected);
        }
    }
}
