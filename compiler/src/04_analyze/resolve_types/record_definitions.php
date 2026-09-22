<?php
declare(strict_types=1);

/*
 * Role: Materialize normalized structural definitions independently of their producer.
 * Call map: Record_Join::join() -> Record_Definitions::materialize() -> Lifecycle_Composition::derive()
 * Output: canonical lifecycle/nominal identity and shared field/representation rows in a private store.
 */
namespace resolve_types;

use type_model\Type_Store;
use type_model\field_declaration;
use type_model\record_declaration;
use type_model\record_layout_policy;
use type_model\type_member;

final class Record_Definitions
{
    /** Common normalized-input boundary for source and provider records; layout authority is explicit. */
    public static function materialize(Type_Store $types, record_declaration $record): int
    {
        if ((!$record->automatic_lifecycle) || ($record->fields === [])
            || !array_is_list($record->fields) || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $record->name)) {
            throw new \LogicException('Unsupported record layout or construction contract');
        }
        if ((($record->layout_policy === record_layout_policy::native_verified) !== ($record->native_layout !== null))
            || (($record->native_layout !== null) && (count($record->native_layout->offsets) !== count($record->fields)))) {
            throw new \LogicException('Record layout policy requires its complete native measurement');
        }
        $fields = [];
        $resources = [];
        $names = [];
        foreach ($record->fields as $field)
        {
            if (!($field instanceof field_declaration) || (!$field->definition->struct_field)
                || isset($names[$field->name]) || !preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/D', $field->name)) {
                throw new \LogicException('Unsupported record field contract');
            }
            $definition = $field->definition;
            $paths = $definition instanceof \type_model\named_type_definition
                ? ($definition->resource !== null ? [[]] : $definition->resource_paths) : [];
            foreach ($paths as $path) {
                $resources[] = [count($fields), ...$path];
            }
            $names[$field->name] = true;
            $fields[] = new type_member(Type_Cache::materialize($types, $field->definition), $field->name, $field->writable);
        }
        $shape_id = $types->intern_structure($fields);
        $id = $types->declare_type($record->name, $record->namespace_name);
        $definition = new \type_model\named_type_definition($record->name, $record->namespace_name,
            $types->representation_by_id($shape_id), Lifecycle_Composition::derive($types, $id, array_map(static fn($field) => $field->type_id, $fields), constructor_body: $record->constructor_body, destructor_body: $record->destructor_body, copy_body: $record->copy_body, assignment_body: $record->assignment_body), struct_field: true, native_layout: $record->native_layout, resource_paths: $resources);
        $types->bind_definition($id, $definition);
        $types->set_representation($id, $shape_id);
        return $id;
    }
}
