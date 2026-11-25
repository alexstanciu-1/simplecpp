<?php
declare(strict_types=1);
namespace load_runtime;

/** Ordinary package exposure only. Native imports/source payloads retain their separate accepted owner. */
final class Package_Type_Exposure {
    public static function definition(\scpp\Json_View $row, Package_Type_Measurement $measurement,
        \type_model\Type_Reference $binding, \type_model\Type_Catalog $catalog,
        array $operations /** hash<\scpp\Json_View> */, string $provider): ?\type_model\Named_Definition {
        if ($binding->kind !== \type_model\TYPE_REFERENCE_NAMED) { throw new \RuntimeException('Invalid compiler runtime type binding'); }
        if ($row->has('source_payload')) {
            if ($row->member('source_payload')->kind() !== 'null') { throw new \RuntimeException('Source payload requires its accepted export owner'); }
        }
        if ($row->has('native_import')) {
            if ($row->member('native_import')->kind() !== 'null') { throw new \RuntimeException('Native import requires its accepted type owner'); }
        }
        $resource = Resource_Import::resource_type($row);
        $name = $binding->name(); $namespace_name = $binding->namespace_name();
        $language = $catalog->find_type($name, $namespace_name);
        $kind = $measurement->storage->kind;
        if ($kind === \load_runtime\RUNTIME_STORAGE_RECORD) {
            if ($language !== null) { throw new \RuntimeException('Exposed records require a new language name'); }
            // Record_Import owns field normalization and later definition materialization.
            return null;
        }
        if ($kind === \load_runtime\RUNTIME_STORAGE_OPAQUE) {
            if ($language !== null) { throw new \RuntimeException('Exposed inline types require a new language name'); }
            $lifetime = Lifecycle_Import::lifetime($row, $operations, $provider);
            $field = false;
            if ($row->has('struct_field')) { $field = $row->member('struct_field')->boolean(); }
            $representation = \type_model\Representation::opaque($measurement->storage->size_bytes, $measurement->storage->alignment_bytes);
            return new \type_model\Named_Definition($name, $namespace_name, $representation, $lifetime, null, '', false, false, $field, $resource);
        }
        if ($kind === \load_runtime\RUNTIME_STORAGE_BYTE_SPAN) {
            if ($language !== null) { throw new \RuntimeException('A byte span requires a new language name'); }
            $policy = new \type_model\Lifetime_Policy(); $policy->copy = 1; $policy->assignment = 1;
            $none /** vector<\type_model\Lifecycle_Operation> */ = [];
            $lifetime = new \type_model\Lifetime_Contract($policy, $none);
            return new \type_model\Named_Definition($name, $namespace_name, \type_model\Representation::byte_span(), $lifetime, null, '', false, false, false);
        }
        if ($kind === \load_runtime\RUNTIME_STORAGE_VOID) {
            if ($language === null) { throw new \RuntimeException('Invalid runtime void binding'); }
            if ($language->representation->kind() !== \type_model\REPRESENTATION_VOID) { throw new \RuntimeException('Invalid runtime void binding'); }
            if ($row->member('size_bytes')->integer() !== 0) { throw new \RuntimeException('Invalid runtime void binding'); }
            return $language;
        }
        if ($language === null) { throw new \RuntimeException('Runtime type does not match language binding: ' . $name); }
        if ($language->representation->kind() !== \type_model\REPRESENTATION_INTEGER) { throw new \RuntimeException('Runtime type does not match language binding: ' . $name); }
        $bits = 0; $signed_value = false; $language_signed = false;
        if (!take_nullable($bits, $measurement->integer_bits)) { throw new \RuntimeException('Runtime type does not match language binding: ' . $name); }
        if (!take_nullable($signed_value, $measurement->signed)) { throw new \RuntimeException('Runtime type does not match language binding: ' . $name); }
        if (!take_nullable($language_signed, $language->signed)) { throw new \RuntimeException('Runtime type does not match language binding: ' . $name); }
        if (($language->representation->bit_width() !== $bits) || ($language_signed !== $signed_value)) { throw new \RuntimeException('Runtime type does not match language binding: ' . $name); }
        return $language;
    }
}
