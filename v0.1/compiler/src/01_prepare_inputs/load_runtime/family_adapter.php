<?php
declare(strict_types=1);
namespace load_runtime;

/** Import semantic families without running preparation or allocating canonical types. */
final class Family_Adapter {
    public static function accept(array $definitions /** vector<\type_model\Family_Definition> */): array /** hash<\type_model\Family_Definition> */ {
        $accepted /** hash<\type_model\Family_Definition> */ = [];
        foreach ($definitions as $definition) {
            \type_model\Family_Contracts::validate($definition); $key = $definition->key();
            if (isset($accepted[$key])) { throw new \RuntimeException('Duplicate provider family declaration'); }
            $accepted[$key] = $definition;
        }
        return $accepted;
    }
    public static function expose(array $definitions /** vector<\type_model\Family_Definition> */,
        array $language_types /** hash<\type_model\Type_Reference> */): array /** vector<\type_model\Family_Declaration> */ {
        $declarations /** vector<\type_model\Family_Declaration> */ = [];
        $accepted = Family_Adapter::accept($definitions);
        foreach ($accepted as $key => $definition) {
            if ($definition->language_type !== null) { $declarations[] = new \type_model\Family_Declaration($definition, $language_types); }
        }
        return $declarations;
    }
    /** Validate all exposures and explicit provider mappings against the accepted catalog. */
    public static function validate_exposures(array $declarations /** vector<\type_model\Family_Declaration> */,
        \type_model\Type_Catalog $catalog): void {
        $identities /** hash<bool> */ = []; $names /** hash<bool> */ = [];
        foreach ($declarations as $declaration) {
            $family = $declaration->definition; \type_model\Family_Contracts::validate($family);
            $identity = $family->key();
            $name_key = '[' . json_quote($declaration->namespace_name) . ',' . json_quote($declaration->name) . ']';
            if (isset($identities[$identity]) || isset($names[$name_key])
                || ($catalog->find_type($declaration->name, $declaration->namespace_name) !== null)
                || ($catalog->find_record($declaration->name, $declaration->namespace_name) !== null)) {
                throw new \RuntimeException('Conflicting source family declaration');
            }
            $identities[$identity] = true; $names[$name_key] = true;
            for ($index = 0; $index < $family->operation_count(); $index++) {
                $operation = $family->operation_at($index); $signature = $operation->signature;
                for ($position = 0; $position < $signature->parameter_count(); $position++) {
                    $parameter = $signature->parameter_at($position);
                    Family_Adapter::require_mapping($parameter->type, $declaration, $catalog);
                }
                Family_Adapter::require_mapping($signature->result->type, $declaration, $catalog);
            }
        }
    }
    private static function require_mapping(\type_model\Type_Reference $reference,
        \type_model\Family_Declaration $declaration, \type_model\Type_Catalog $catalog): void {
        if ($reference->kind !== \type_model\TYPE_REFERENCE_PROVIDER) { return; }
        $key = '[' . json_quote($reference->provider()) . ',' . json_quote($reference->id()) . ']';
        $name = $declaration->find_language_type($key);
        if ($name === null) { throw new \RuntimeException('Missing explicit family language type mapping: ' . $reference->id()); }
        if (($catalog->find_type($name->name(), $name->namespace_name()) === null)
            && ($catalog->find_record($name->name(), $name->namespace_name()) === null)) {
            throw new \RuntimeException('Missing explicit family language type mapping: ' . $reference->id());
        }
    }
}
