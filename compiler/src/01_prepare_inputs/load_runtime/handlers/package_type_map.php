<?php
declare(strict_types=1);
namespace load_runtime;

/** Normalize a complete package type map privately; publish only after all binding checks pass. */
final class Package_Type_Map {
    private static function present(\scpp\Json_View $row, string $key): bool {
        if (!$row->has($key)) { return false; }
        return $row->member($key)->kind() !== 'null';
    }
    private static function exposed(\scpp\Json_View $row, Package_Type_Measurement $measurement, \type_model\Type_Reference $binding,
        \type_model\Type_Catalog $catalog, array $operations /** hash<\scpp\Json_View> */, string $provider): Runtime_Type {
        $definition = Package_Type_Exposure::definition($row,$measurement,$binding,$catalog,$operations,$provider);
        return new Runtime_Type($measurement->id,$measurement->storage,$measurement->integer_bits,$measurement->signed,$definition);
    }
    private static function ordinary(\scpp\Json_View $row, Package_Type_Measurement $measurement, Package_Bindings $bindings,
        \type_model\Type_Catalog $catalog, array $operations /** hash<\scpp\Json_View> */, string $provider): Runtime_Type {
        if (isset($bindings->types[$measurement->id])) {
            return Package_Type_Map::exposed($row,$measurement,$bindings->types[$measurement->id],$catalog,$operations,$provider);
        }
        if (Package_Type_Map::present($row,'language_type')) {
            return Package_Type_Map::exposed($row,$measurement,Package_Syntax::language_name($row->member('language_type')),$catalog,$operations,$provider);
        }
        return new Runtime_Type($measurement->id,$measurement->storage,$measurement->integer_bits,$measurement->signed,null);
    }
    public static function types(array $rows /** vector<\scpp\Json_View> */, \type_model\Type_Catalog $catalog,
        array $operations /** vector<\scpp\Json_View> */, string $provider, Package_Bindings $bindings): array /** hash<Runtime_Type> */ {
        $by_operation /** hash<\scpp\Json_View> */ = [];
        foreach ($operations as $operation) {
            $id = Package_Syntax::identifier($operation->member('id'));
            if (isset($by_operation[$id])) { throw new \RuntimeException('Duplicate runtime operation: ' . $id); }
            $by_operation[$id] = $operation;
        }
        $types /** hash<Runtime_Type> */ = [];
        foreach ($rows as $row) {
            $measurement = Package_Type_Import::measure($row); $id = $measurement->id;
            if (isset($types[$id])) { throw new \RuntimeException('Duplicate runtime type: ' . $id); }
            // Validate resource metadata even for unexposed entries, before selecting an owner.
            $resource = Resource_Import::resource_type($row);
            if (Package_Type_Map::present($row,'source_payload') || isset($bindings->sources[$id])) {
                if (isset($bindings->types[$id]) || isset($bindings->imports[$id])) { throw new \RuntimeException('Source payload cannot introduce another type owner'); }
                if (!isset($bindings->sources[$id])) { throw new \RuntimeException('Source payload requires an explicit compiler export binding'); }
                $definition = Project_Import::source_type($row,$bindings->sources[$id],$measurement->storage);
                $storage = new Runtime_Storage(\load_runtime\RUNTIME_STORAGE_RECORD,$measurement->storage->size_bytes,$measurement->storage->alignment_bytes);
                $types[$id] = new Runtime_Type($id,$storage,$measurement->integer_bits,$measurement->signed,$definition);
            } elseif (Package_Type_Map::present($row,'native_import') || isset($bindings->imports[$id])) {
                if (!isset($bindings->imports[$id])) { throw new \RuntimeException('Native type import does not match its accepted owner'); }
                if (isset($bindings->types[$id])) { throw new \RuntimeException('Native type import does not match its accepted owner'); }
                $definition = Native_Type_Import::definition($row,$measurement,$bindings->imports[$id]);
                $types[$id] = new Runtime_Type($id,$measurement->storage,$measurement->integer_bits,$measurement->signed,$definition);
            } else { $types[$id] = Package_Type_Map::ordinary($row,$measurement,$bindings,$catalog,$by_operation,$provider); }
        }
        foreach ($bindings->types as $id => $binding) {
            if (!isset($types[$id])) { throw new \RuntimeException('Unknown or invalid compiler runtime type binding'); }
            if ($binding->kind !== \type_model\TYPE_REFERENCE_NAMED) { throw new \RuntimeException('Unknown or invalid compiler runtime type binding'); }
        }
        foreach ($bindings->imports as $id => $owner) {
            if (!isset($types[$id])) { throw new \RuntimeException('Unknown compiler runtime type import'); }
        }
        foreach ($bindings->sources as $id => $source_export) {
            if (!isset($types[$id])) { throw new \RuntimeException('Unknown compiler source payload binding'); }
            if ($types[$id]->language_type !== $source_export->task->layout->definition) { throw new \RuntimeException('Unknown compiler source payload binding'); }
        }
        return $types;
    }
}
