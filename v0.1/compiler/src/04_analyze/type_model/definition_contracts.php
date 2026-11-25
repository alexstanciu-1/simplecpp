<?php
declare(strict_types=1);
namespace type_model;

/** Structural equality of accepted immutable definitions in one type/symbol lineage.
 * Constructors form an acyclic dependency graph; this is not arbitrary object-graph equality. */
final class Definition_Contracts {
    public static function layout(Native_Record_Layout $a, Native_Record_Layout $b): bool {
        if ($a === $b) { return true; }
        if (($a->target_triple !== $b->target_triple) || ($a->data_layout !== $b->data_layout)
            || ($a->size !== $b->size) || ($a->alignment !== $b->alignment) || ($a->field_count() !== $b->field_count())) { return false; }
        for ($index = 0; $index < $a->field_count(); $index++) { if ($a->field_offset($index) !== $b->field_offset($index)) { return false; } }
        return true;
    }
    public static function resources(Resource_Obligations $a, Resource_Obligations $b): bool {
        if ($a === $b) { return true; }
        if (($a->kind !== $b->kind) || ($a->path_count() !== $b->path_count())) { return false; }
        for ($index = 0; $index < $a->path_count(); $index++) {
            $left = $a->path_at($index); $right = $b->path_at($index);
            if (q_count($left) !== q_count($right)) { return false; }
            foreach ($left as $ordinal => $value) { if ($value !== $right[$ordinal]) { return false; } }
        }
        return true;
    }
    public static function same(Named_Definition $a, Named_Definition $b): bool {
        if ($a === $b) { return true; }
        if (($a->name !== $b->name) || ($a->namespace_name !== $b->namespace_name) || ($a->signed !== $b->signed)
            || ($a->integer_family !== $b->integer_family) || ($a->wrapping_addition !== $b->wrapping_addition)
            || ($a->ordered_comparison !== $b->ordered_comparison) || ($a->struct_field !== $b->struct_field)) { return false; }
        if (!$a->representation->same($b->representation)) { return false; }
        $left_life = $a->lifetime; $right_life = $b->lifetime;
        if ($left_life === null) { if ($right_life !== null) { return false; } }
        else {
            if ($right_life === null) { return false; }
            if (!Lifecycle_Contracts::same($left_life,$right_life)) { return false; }
        }
        // The prototype held a nullable resource enum plus paths, not an optional wrapper identity.
        $left_resource = $a->ownership; $right_resource = $b->ownership;
        if ($left_resource === null) {
            if ($right_resource !== null) { if ($right_resource->has_owners()) { return false; } }
        } elseif ($right_resource === null) { if ($left_resource->has_owners()) { return false; } }
        else { if (!Definition_Contracts::resources($left_resource,$right_resource)) { return false; } }
        $left_layout = $a->native_layout; $right_layout = $b->native_layout;
        if ($left_layout === null) { if ($right_layout !== null) { return false; } }
        else {
            if ($right_layout === null) { return false; }
            if (!Definition_Contracts::layout($left_layout,$right_layout)) { return false; }
        }
        $left_storage = $a->element_storage; $right_storage = $b->element_storage;
        if ($left_storage === null) { return $right_storage === null; }
        if ($right_storage === null) { return false; }
        if ($left_storage->element_type !== $right_storage->element_type) { return false; }
        if (!Definition_Contracts::same($left_storage->element,$right_storage->element)) { return false; }
        return Definition_Contracts::family($left_storage->family,$right_storage->family);
    }
    public static function primitive(Storage_Primitive $a, Storage_Primitive $b): bool {
        if ($a === $b) { return true; }
        if (($a->link_name !== $b->link_name) || ($a->parameter_count() !== $b->parameter_count())) { return false; }
        $left = $a->result; $right = $b->result;
        if ($left === null) { if ($right !== null) { return false; } }
        else {
            if ($right === null) { return false; }
            if (!Callable_Contracts::position($left,$right)) { return false; }
        }
        for ($index = 0; $index < $a->parameter_count(); $index++) {
            if (!Callable_Contracts::position($a->parameter_at($index),$b->parameter_at($index))) { return false; }
        }
        return true;
    }
    public static function family(Storage_Family $a, Storage_Family $b): bool {
        if ($a === $b) { return true; }
        if (($a->provider !== $b->provider) || ($a->id !== $b->id) || ($a->name !== $b->name) || ($a->namespace_name !== $b->namespace_name)) { return false; }
        if (!Definition_Contracts::same($a->descriptor,$b->descriptor)) { return false; }
        if (!Definition_Contracts::same($a->counter,$b->counter)) { return false; }
        if (!Definition_Contracts::same($a->void_type,$b->void_type)) { return false; }
        $left = $a->primitive_contracts(); $right = $b->primitive_contracts();
        if (q_count($left) !== q_count($right)) { return false; }
        foreach ($left as $key => $primitive) {
            if (!isset($right[$key])) { return false; }
            if (!Definition_Contracts::primitive($primitive,$right[$key])) { return false; }
        }
        $left_names = $a->operation_spellings(); $right_names = $b->operation_spellings();
        if (q_count($left_names) !== q_count($right_names)) { return false; }
        foreach ($left_names as $key => $name) {
            if (!isset($right_names[$key])) { return false; }
            if ($name !== $right_names[$key]) { return false; }
        }
        return true;
    }
    public static function record(Record_Declaration $a, Record_Declaration $b): bool {
        if ($a === $b) { return true; }
        if (($a->name !== $b->name) || ($a->namespace_name !== $b->namespace_name) || ($a->automatic_lifecycle !== $b->automatic_lifecycle)
            || ($a->layout_policy !== $b->layout_policy) || ($a->constructor_body !== $b->constructor_body)
            || ($a->destructor_body !== $b->destructor_body) || ($a->copy_body !== $b->copy_body)
            || ($a->assignment_body !== $b->assignment_body) || ($a->field_count() !== $b->field_count())) { return false; }
        $left = $a->native_layout; $right = $b->native_layout;
        if ($left === null) { if ($right !== null) { return false; } }
        else {
            if ($right === null) { return false; }
            if (!Definition_Contracts::layout($left,$right)) { return false; }
        }
        for ($index = 0; $index < $a->field_count(); $index++) {
            $left_field = $a->field_at($index); $right_field = $b->field_at($index);
            if (($left_field->name !== $right_field->name) || ($left_field->writable !== $right_field->writable)
                || ($left_field->definition->extent !== $right_field->definition->extent)) { return false; }
            if (!Definition_Contracts::same($left_field->definition->element,$right_field->definition->element)) { return false; }
        }
        return true;
    }
}
