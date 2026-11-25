<?php
declare(strict_types=1);
namespace family_test;
final class Probe {
    private static function check(bool $ok): void { echo $ok ? "true\n" : "false\n"; }
    public static function candidate(int $case_index): \type_model\Family_Definition {
        $provider = 'runtime'; $id = 'vector'; $key = '["runtime","vector"]';
        if ($case_index === 1) { $provider = ''; }
        if ($case_index === 2) { $id = ''; }
        $first = new \type_model\Family_Parameter('T', \type_model\GENERIC_COPYABLE_VALUE); $second = new \type_model\Family_Parameter('U', \type_model\GENERIC_COPYABLE_VALUE);
        $formals /** vector<\type_model\Family_Parameter> */ = [$first, $second];
        if ($case_index === 3) { $formals = []; }
        if ($case_index === 4) { $formals[1] = $first; }
        $formal = \type_model\Type_Reference::parameter($key, 0);
        if ($case_index === 15) { $formal = \type_model\Type_Reference::parameter('foreign', 0); }
        if ($case_index === 16) { $formal = \type_model\Type_Reference::parameter($key, 2); }
        $other = \type_model\Type_Reference::parameter($key, 1);
        $arguments /** vector<\type_model\Type_Reference> */ = [$formal, $other];
        if ($case_index === 18) { $arguments = [$formal]; }
        if ($case_index === 19) { $arguments = [$other, $formal]; }
        $self_key = $key;
        if ($case_index === 17) { $self_key = 'foreign'; }
        $self = \type_model\Type_Reference::family($self_key, $arguments);
        $primitive = \type_model\Type_Reference::provided('runtime', 'void');
        if ($case_index === 13) { $primitive = \type_model\Type_Reference::provided('foreign', 'void'); }
        if ($case_index === 14) { $primitive = \type_model\Type_Reference::provided('runtime', ''); }
        if ($case_index === 20) { $primitive = \type_model\Type_Reference::named('void', ''); }
        $receiver_type = $self; $receiver_passing = \type_model\PASS_BORROW_MUTABLE;
        if ($case_index === 24) { $receiver_type = $formal; }
        if ($case_index === 25) { $receiver_passing = \type_model\PASS_VALUE; }
        if ($case_index === 26) { $receiver_passing = \type_model\PASS_BORROW_CONST; }
        $input_type = $formal; $input_passing = \type_model\PASS_BORROW_CONST;
        if ($case_index === 29) { $input_passing = \type_model\PASS_VALUE; }
        if ($case_index === 30) { $input_type = $primitive; }
        $parameters /** vector<\type_model\Semantic_Parameter> */ = [
            new \type_model\Semantic_Parameter($receiver_type, $receiver_passing),
            new \type_model\Semantic_Parameter($input_type, $input_passing)];
        $result = new \type_model\Semantic_Result($primitive, \type_model\RESULT_NONE);
        $signature = new \type_model\Semantic_Signature($parameters, $result);
        $requirements /** vector<\type_model\Capability_Requirement> */ = [new \type_model\Capability_Requirement(0, \type_model\LIFECYCLE_COPY)];
        if ($case_index === 21) { $requirements[0] = new \type_model\Capability_Requirement(2, \type_model\LIFECYCLE_COPY); }
        if ($case_index === 22) { $requirements[0] = new \type_model\Capability_Requirement(0, \type_model\LIFECYCLE_DEFAULT); }
        $effects /** vector<\type_model\Element_Effect> */ = [new \type_model\Element_Effect(\type_model\ELEMENT_SAFE_INPUT, 0, 1), new \type_model\Element_Effect(\type_model\ELEMENT_INVALIDATE, 0)];
        if ($case_index === 27) { $effects[0] = new \type_model\Element_Effect(\type_model\ELEMENT_SAFE_INPUT, 0); }
        if ($case_index === 28) { $effects[0] = new \type_model\Element_Effect(\type_model\ELEMENT_SAFE_INPUT, 0, 2); }
        if ($case_index === 31) { $effects[1] = new \type_model\Element_Effect(\type_model\ELEMENT_INVALIDATE, 0, 1); }
        if ($case_index === 32) { $effects[] = $effects[0]; }
        $exposure = \type_model\Type_Reference::named('append', '');
        if ($case_index === 11) { $exposure = \type_model\Type_Reference::named('append', 'library'); }
        $receiver = 0;
        if ($case_index === 23) { $receiver = 2; }
        $operation = new \type_model\Family_Operation('push', $signature, $requirements, $effects, $receiver, $exposure);
        if ($case_index === 9) { $operation = new \type_model\Family_Operation('push', $signature, $requirements, $effects, null, $exposure); }
        $operations /** hash<\type_model\Family_Operation> */ = [];
        $operations['push'] = $operation;
        if ($case_index === 8) { $operations['again'] = new \type_model\Family_Operation('again', $signature, $requirements, $effects, 0, $exposure); }
        $lifecycle /** hash<string> */ = [];
        if ($case_index === 5) { $lifecycle['destroy'] = 'missing'; }
        if ($case_index === 12) { $lifecycle['destroy'] = 'push'; }
        $language = \type_model\Type_Reference::named('Vector', 'library::collections');
        if ($case_index === 6) { $language = \type_model\Type_Reference::named('0Vector', 'library'); }
        if ($case_index === 7) { $language = \type_model\Type_Reference::named('Vector', 'library::'); }
        if ($case_index === 10) { return new \type_model\Family_Definition($provider, $id, $formals, $operations, $lifecycle); }
        return new \type_model\Family_Definition($provider, $id, $formals, $operations, $lifecycle, $language);
    }
    public static function run(): void {
        for ($case_index = 0; $case_index < 33; $case_index++) {
            $accepted = true;
            try { $family = Probe::candidate($case_index); \type_model\Family_Contracts::validate($family); }
            catch (\RuntimeException $error) { $accepted = false; }
            Probe::check($accepted === ($case_index === 0));
        }
        $family = Probe::candidate(0); $operation = $family->operation_at(0);
        $mappings /** hash<\type_model\Type_Reference> */ = [];
        $void_type = \type_model\Type_Reference::named('void', '');
        $mappings['["runtime","void"]'] = $void_type;
        $declaration = new \type_model\Family_Declaration($family, $mappings);
        $mappings['["runtime","void"]'] = \type_model\Type_Reference::named('int', '');
        $method = new \type_model\Family_Method($declaration, $operation);
        Probe::check($declaration->definition === $family);
        Probe::check(($declaration->provider === 'runtime') && ($declaration->id === 'vector'));
        Probe::check(($declaration->name === 'Vector') && ($declaration->namespace_name === 'library::collections'));
        Probe::check($declaration->find_language_type('["runtime","void"]') === $void_type);
        Probe::check($declaration->find_language_type('missing') === null);
        Probe::check($method->family === $declaration);
        Probe::check($method->operation === $operation);
        Probe::check(($method->name === 'append') && ($method->id === 'push'));
        Probe::check($method->namespace_name === $declaration->namespace_name);
        Probe::check($family->find_operation('push') === $operation);
        Probe::check($family->find_operation('missing') === null);
        Probe::check($family->key() === '["runtime","vector"]');
        $parameters /** vector<\type_model\Family_Parameter> */ = [$family->parameter_at(0), $family->parameter_at(1)];
        $requirements /** vector<\type_model\Capability_Requirement> */ = [];
        $effects /** vector<\type_model\Element_Effect> */ = [];
        $hidden = new \type_model\Family_Operation('push', $operation->signature, $requirements, $effects, 0);
        $operations /** hash<\type_model\Family_Operation> */ = [];
        $operations['push'] = $hidden;
        $lifecycle /** hash<string> */ = [];
        $lifecycle['destroy'] = 'push';
        $copy = new \type_model\Family_Definition('runtime', 'vector', $parameters, $operations, $lifecycle);
        \type_model\Family_Contracts::validate($copy);
        $parameters[0] = new \type_model\Family_Parameter('Changed', \type_model\GENERIC_COPYABLE_VALUE);
        $operations['push'] = $operation; $lifecycle['destroy'] = 'missing';
        Probe::check($copy->parameter_at(0)->name === 'T');
        Probe::check($copy->find_operation('push') === $hidden);
        $bindings = $copy->lifecycle_bindings();
        Probe::check($bindings['destroy'] === 'push');
        $bindings['destroy'] = 'changed'; $bindings_again = $copy->lifecycle_bindings();
        Probe::check($bindings_again['destroy'] === 'push');
        $key_family = new \type_model\Family_Definition('a/b', 'é', $parameters, $operations, $lifecycle);
        Probe::check($key_family->key() === '["a\\/b","\\u00e9"]');
        $rejected = false;
        try { $bad_method = new \type_model\Family_Method($declaration, $hidden); }
        catch (\InvalidArgumentException $error) { $rejected = true; }
        Probe::check($rejected);
    }
}
