<?php
declare(strict_types=1);
namespace load_runtime;

/** Bind an import to its exact existing type owner; never infer a new definition from layout. */
final class Native_Type_Import {
    private static function present(\scpp\Json_View $row, string $key): bool {
        if (!$row->has($key)) { return false; }
        return $row->member($key)->kind() !== 'null';
    }
    public static function definition(\scpp\Json_View $row, Package_Type_Measurement $measurement, Runtime_Type_Import $owner): \type_model\Named_Definition {
        $reference = $row->member('native_import');
        if ($reference->kind() !== 'object') { throw new \RuntimeException('Native type import does not match its accepted owner'); }
        if ($reference->size() !== 2) { throw new \RuntimeException('Native type import does not match its accepted owner'); }
        // Preserve the version-1 producer's exact provider/id record protocol.
        if (($reference->key(0) !== 'provider') || ($reference->key(1) !== 'id')) { throw new \RuntimeException('Native type import does not match its accepted owner'); }
        if (($reference->member('provider')->text() !== $owner->provider) || ($reference->member('id')->text() !== $owner->type_id)) { throw new \RuntimeException('Native type import does not match its accepted owner'); }
        $lifecycle = $row->member('lifecycle');
        if (($lifecycle->kind() !== 'object') && ($lifecycle->kind() !== 'array')) { throw new \RuntimeException('Native type import does not match its accepted owner'); }
        if ($lifecycle->size() !== 0) { throw new \RuntimeException('Native type import does not match its accepted owner'); }
        if (Native_Type_Import::present($row,'language_type') || Native_Type_Import::present($row,'source_payload')
            || $row->has('resource') || Native_Type_Import::present($row,'storage_family') || Native_Type_Import::present($row,'struct_field')) {
            throw new \RuntimeException('Native type import does not match its accepted owner');
        }
        $storage = $measurement->storage; $accepted = $owner->type;
        if (($storage->kind !== \load_runtime\RUNTIME_STORAGE_OPAQUE) || ($accepted->storage->kind !== $storage->kind)
            || ($accepted->storage->size_bytes !== $storage->size_bytes) || ($accepted->storage->alignment_bytes !== $storage->alignment_bytes)
            || ($accepted->id !== $owner->type_id)) { throw new \RuntimeException('Native type import does not match its accepted owner'); }
        $language = $accepted->language_type;
        if ($language === null) { throw new \RuntimeException('Native type import does not match its accepted owner'); }
        $traits = $row->member('cpp_traits');
        if (!$traits->member('copy_constructible')->boolean()) { throw new \RuntimeException('Native type import lost its generic capabilities'); }
        if (!$traits->member('copy_assignable')->boolean()) { throw new \RuntimeException('Native type import lost its generic capabilities'); }
        if (\type_model\Generic_Contracts::missing($language, \type_model\GENERIC_COPYABLE_VALUE) !== null) { throw new \RuntimeException('Native type import lost its generic capabilities'); }
        return $language;
    }
}
