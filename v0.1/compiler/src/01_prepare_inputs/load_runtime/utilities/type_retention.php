<?php
declare(strict_types=1);
namespace load_runtime;

/** Adapter-internal canonicalization after validating current rows and establishing the same
 * provider/directory/target/base-catalog context. This helper does not authorize cross-context reuse. */
final class Type_Retention {
    public static function same(Runtime_Type $a, Runtime_Type $b): bool {
        if ($a === $b) { return true; }
        if (($a->id !== $b->id) || ($a->storage->kind !== $b->storage->kind)
            || ($a->storage->size_bytes !== $b->storage->size_bytes) || ($a->storage->alignment_bytes !== $b->storage->alignment_bytes)
            || ($a->integer_bits !== $b->integer_bits) || ($a->signed !== $b->signed)) { return false; }
        $left = $a->language_type; $right = $b->language_type;
        if ($left === null) { if ($right !== null) { return false; } }
        else {
            if ($right === null) { return false; }
            if (!\type_model\Definition_Contracts::same($left,$right)) { return false; }
        }
        $left_record = $a->record; $right_record = $b->record;
        if ($left_record === null) { return $right_record === null; }
        if ($right_record === null) { return false; }
        return \type_model\Definition_Contracts::record($left_record,$right_record);
    }
    public static function retain(array $types /** hash<Runtime_Type> */, Package_Bindings $bindings, Runtime_Package $previous): array /** hash<Runtime_Type> */ {
        $result = $types; $old_types = $previous->types(); $old_bindings = $previous->bindings;
        foreach ($bindings->types as $id => $binding) {
            if (!isset($old_types[$id])) { continue; }
            if ($old_bindings === null) { continue; }
            if (!isset($old_bindings->types[$id])) { continue; }
            if (!\type_model\Callable_Contracts::reference($old_bindings->types[$id],$binding)) { continue; }
            if (!isset($types[$id])) { throw new \RuntimeException('Unknown compiler runtime type binding'); }
            if (!Type_Retention::same($old_types[$id],$types[$id])) {
                throw new \RuntimeException('Prepared family type contract changed; a fresh compiler type context is required');
            }
            $result[$id] = $old_types[$id];
        }
        foreach ($bindings->sources as $id => $source_export) {
            if (!isset($result[$id])) { throw new \RuntimeException('Unknown compiler source payload binding'); }
            if ($result[$id]->language_type !== $source_export->task->layout->definition) { throw new \RuntimeException('Unknown compiler source payload binding'); }
        }
        return $result;
    }
}
