<?php
declare(strict_types=1);
namespace provider_catalog_test;
final class Probe {
    private static function check(bool $ok): void { echo $ok ? "true\n" : "false\n"; }
    private static function integer(): \type_model\Named_Definition {
        $policy = new \type_model\Lifetime_Policy(); $policy->copy = 1; $policy->construction = 1; $policy->assignment = 1; $policy->expiring = 1;
        $operations /** vector<\type_model\Lifecycle_Operation> */ = [];
        return new \type_model\Named_Definition('word', '', \type_model\Representation::integer(32), new \type_model\Lifetime_Contract($policy, $operations), true, '', false, false, true);
    }
    private static function record(string $name, string $namespace_name, \type_model\Named_Definition $word): \type_model\Record_Declaration {
        $fields /** vector<\type_model\Field_Declaration> */ = [new \type_model\Field_Declaration('value', \type_model\Field_Type::named($word), true)];
        return new \type_model\Record_Declaration($name, $namespace_name, $fields, true, 0, null, 0, 0, 0, 0);
    }
    private static function family(string $name, string $namespace_name, string $provider, string $id, bool $exposed): \type_model\Family_Definition {
        $parameters /** vector<\type_model\Family_Parameter> */ = [new \type_model\Family_Parameter('T', \type_model\GENERIC_COPYABLE_VALUE)];
        $arguments /** vector<\type_model\Semantic_Parameter> */ = [new \type_model\Semantic_Parameter(\type_model\Type_Reference::provided($provider, 'input'), \type_model\PASS_VALUE)];
        $result = new \type_model\Semantic_Result(\type_model\Type_Reference::provided($provider, 'output'), \type_model\RESULT_OWNED);
        $signature = new \type_model\Semantic_Signature($arguments, $result);
        $requirements /** vector<\type_model\Capability_Requirement> */ = [];
        $effects /** vector<\type_model\Element_Effect> */ = [];
        $operations /** hash<\type_model\Family_Operation> */ = [];
        $operations['make'] = new \type_model\Family_Operation('make', $signature, $requirements, $effects);
        $lifecycle /** hash<string> */ = [];
        if (!$exposed) { return new \type_model\Family_Definition($provider, $id, $parameters, $operations, $lifecycle); }
        return new \type_model\Family_Definition($provider, $id, $parameters, $operations, $lifecycle, \type_model\Type_Reference::named($name, $namespace_name));
    }
    public static function run(): void {
        $word = Probe::integer(); $rows /** vector<\type_model\Named_Definition> */ = [$word];
        $record = Probe::record('Row', 'provider', $word);
        $qualified = Probe::record('word', 'provider', $word);
        $records /** vector<\type_model\Record_Declaration> */ = [$record, $qualified];
        $catalog = new \type_model\Type_Catalog('language', 'snapshot', 'language_values', $rows, $word, $word, null, $records);
        $records[0] = Probe::record('Replacement', 'provider', $word);
        Probe::check($catalog->record_count() === 2);
        Probe::check($catalog->record_at(0) === $record);
        Probe::check($catalog->record_at(1) === $qualified);
        Probe::check($catalog->find_record('Row', 'provider') === $record);
        Probe::check($catalog->find_record('word', 'provider') === $qualified);
        Probe::check($catalog->find_record('Row', '') === null);
        Probe::check($catalog->find_record('word', '') === null);
        Probe::check($catalog->find_type('word', '') === $word);
        Probe::check($catalog->find_type('Row', 'provider') === null);
        Probe::check(($catalog->size() === 1) && ($catalog->entry_return_type === $word));
        $empty = new \type_model\Type_Catalog('language', 'base', 'language_values', $rows, $word, $word, null);
        Probe::check($empty->record_count() === 0);
        for ($case_index = 0; $case_index < 4; $case_index++) {
            $rejected = false;
            try {
                if ($case_index === 0) { $catalog->record_at(-1); }
                elseif ($case_index === 1) { $catalog->record_at(2); }
                else {
                    $bad_records /** vector<\type_model\Record_Declaration> */ = [$record, $record];
                    if ($case_index === 3) { $bad_records = [Probe::record('word', '', $word)]; }
                    $bad = new \type_model\Type_Catalog('language', 'bad', 'language_values', $rows, $word, $word, null, $bad_records);
                }
            } catch (\InvalidArgumentException $error) { $rejected = true; }
            Probe::check($rejected);
        }
        $family = Probe::family('Bag', 'library', 'runtime', 'bag', true);
        $hidden = Probe::family('Hidden', '', 'runtime', 'hidden', false);
        $families /** vector<\type_model\Family_Definition> */ = [$family, $hidden];
        $mappings /** hash<\type_model\Type_Reference> */ = [];
        $mappings['["runtime","input"]'] = \type_model\Type_Reference::named('word', '');
        $mappings['["runtime","output"]'] = \type_model\Type_Reference::named('Row', 'provider');
        $accepted = \load_runtime\Family_Adapter::accept($families);
        Probe::check(q_count($accepted) === 2);
        Probe::check($accepted['["runtime","bag"]'] === $family);
        $declarations = \load_runtime\Family_Adapter::expose($families, $mappings);
        Probe::check(q_count($declarations) === 1);
        Probe::check($declarations[0]->definition === $family);
        \load_runtime\Family_Adapter::validate_exposures($declarations, $catalog);
        Probe::check($catalog->find_record('Row', 'provider') === $record);
        for ($case_index = 0; $case_index < 9; $case_index++) {
            $rejected = false;
            $selected /** vector<\type_model\Family_Definition> */ = [$family];
            $mapping /** hash<\type_model\Type_Reference> */ = [];
            foreach ($mappings as $key => $value) { $mapping[$key] = $value; }
            if ($case_index === 0) { $selected[] = $family; }
            elseif ($case_index === 1) { $selected = [Probe::family('word', '', 'runtime', 'bag', true)]; }
            elseif ($case_index === 2) { $selected = [Probe::family('Row', 'provider', 'runtime', 'bag', true)]; }
            elseif ($case_index === 3) { $selected[] = Probe::family('Bag', 'library', 'runtime', 'other', true); }
            elseif ($case_index === 4) { $mapping = []; }
            elseif ($case_index === 5) { $mapping['["runtime","input"]'] = \type_model\Type_Reference::named('missing', ''); }
            elseif ($case_index === 6) { $mapping['["runtime","output"]'] = \type_model\Type_Reference::named('Row', 'wrong'); }
            elseif ($case_index === 7) { $selected = [Probe::family('Bag', 'library', 'other_provider', 'bag', true)]; }
            try {
                $exposed = \load_runtime\Family_Adapter::expose($selected, $mapping);
                if ($case_index === 8) { $exposed[] = $exposed[0]; }
                \load_runtime\Family_Adapter::validate_exposures($exposed, $catalog);
            } catch (\RuntimeException $error) { $rejected = true; }
            Probe::check($rejected);
        }
        $selected /** vector<\type_model\Family_Definition> */ = [Probe::family('word', 'other', 'runtime', 'qualified', true)];
        $exposed = \load_runtime\Family_Adapter::expose($selected, $mappings);
        \load_runtime\Family_Adapter::validate_exposures($exposed, $catalog);
        Probe::check($exposed[0]->namespace_name === 'other');
    }
}
