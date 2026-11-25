<?php
declare(strict_types=1);

/*
 * Role: Import normalized family declarations without native C++ bindings or ABI.
 * Call map: Family_Adapter::accept() -> type_model\Family_Contracts::validate()
 *           expose() -> accept(); validate_exposures() -> Family_Contracts::validate() [fixed source mappings]
 */
namespace load_runtime;

final class Family_Adapter
{
    /** Accept records from the preparation boundary; this never registers types or invokes preparation.
     * @param list<\type_model\family_definition> $definitions
     * @return array<string, \type_model\family_definition> */
    public static function accept(array $definitions): array
    {
        $accepted = [];
        foreach ($definitions as $definition) {
            \type_model\Family_Contracts::validate($definition);
            if (isset($accepted[$definition->key()])) {
                throw new \RuntimeException('Duplicate provider family declaration');
            }
            $accepted[$definition->key()] = $definition;
        }
        return $accepted;
    }

    /** Prepare source declaration payloads without layouts, native tools or source ASTs.
     * @param list<\type_model\family_definition> $definitions
     * @param array<string, \type_model\named_type_reference> $language_types
     * @return list<\type_model\family_declaration> */
    public static function expose(array $definitions, array $language_types): array
    {
        $declarations = [];
        foreach (self::accept($definitions) as $definition) {
            if ($definition->language_type !== null) {
                $declarations[] = new \type_model\family_declaration($definition, $language_types);
            }
        }
        return $declarations;
    }

    /** Validate the complete source-facing input against this compile's catalog before collection.
     * @param list<\type_model\family_declaration> $declarations */
    public static function validate_exposures(array $declarations, \type_model\Type_Catalog $catalog): void
    {
        $identities = [];
        $names = [];
        foreach ($declarations as $declaration)
        {
            $family = $declaration->definition;
            \type_model\Family_Contracts::validate($family);
            $key = json_encode([$declaration->namespace_name, $declaration->name], JSON_THROW_ON_ERROR);
            if (isset($identities[$family->key()]) || isset($names[$key])
                || ($catalog->find_type($declaration->name, $declaration->namespace_name) !== null)
                || ($catalog->find_record($declaration->name, $declaration->namespace_name) !== null)) {
                throw new \RuntimeException('Conflicting source family declaration');
            }
            $identities[$family->key()] = true;
            $names[$key] = true;

            // Provider primitive identities must have explicit language mappings, even for lifecycle results.
            foreach ($family->operations as $operation)
            {
                foreach ([...$operation->signature->parameters, $operation->signature->result] as $position)
                {
                    $reference = $position->type;
                    if (!($reference instanceof \type_model\provider_type_reference)) {
                        continue;
                    }
                    $key = json_encode([$reference->provider, $reference->id], JSON_THROW_ON_ERROR);
                    $name = $declaration->language_types[$key] ?? null;
                    if (!($name instanceof \type_model\named_type_reference)
                        || (($catalog->find_type($name->name, $name->namespace_name) === null)
                            && ($catalog->find_record($name->name, $name->namespace_name) === null))) {
                        throw new \RuntimeException('Missing explicit family language type mapping: ' . $reference->id);
                    }
                }
            }
        }
    }
}
