<?php
declare(strict_types=1);
namespace type_model;

/** Authoritative value definition; producer-specific metadata is added by its owning model. Storage identity alone never grants language capabilities. */
final class Named_Definition {
    public function __construct(public readonly string $name, public readonly string $namespace_name,
        public readonly Representation $representation, public readonly ?Lifetime_Contract $lifetime,
        public readonly ?bool $signed, public readonly string $integer_family,
        public readonly bool $wrapping_addition, public readonly bool $ordered_comparison,
        public readonly bool $struct_field, public readonly ?Resource_Obligations $ownership = null,
        public readonly ?Native_Record_Layout $native_layout = null, public readonly ?Element_Storage $element_storage = null) {
        if ($element_storage !== null) {
            if ($ownership === null) { throw new \InvalidArgumentException('Typed storage requires its family descriptor and allocation obligation'); }
            if (($ownership->kind !== \type_model\RESOURCE_ALLOCATION)
                || ($representation !== $element_storage->family->descriptor->representation)
                || ($lifetime !== $element_storage->family->descriptor->lifetime)) {
                throw new \InvalidArgumentException('Typed storage requires its family descriptor and allocation obligation');
            }
        }
        if ($name === '') { throw new \InvalidArgumentException('Named definition requires a name'); }
        $kind = $representation->kind();
        if (($kind === \type_model\REPRESENTATION_VOID) !== ($lifetime === null)) { throw new \InvalidArgumentException('Value types require lifetime; void must have none'); }
        if (($kind === \type_model\REPRESENTATION_INTEGER) !== ($signed !== null)) { throw new \InvalidArgumentException('Only integer definitions require signedness'); }
        if ($ownership !== null) {
            if ($lifetime === null) { throw new \InvalidArgumentException('Resource obligations require a value lifetime'); }
            $ownership->validate($representation,$lifetime);
        }
        if ($native_layout !== null) {
            if ($kind !== \type_model\REPRESENTATION_STRUCTURE) { throw new \InvalidArgumentException('Native field layout requires structural storage'); }
        }
        if ($struct_field) {
            if (($kind !== \type_model\REPRESENTATION_INTEGER) && ($kind !== \type_model\REPRESENTATION_ARRAY)
                && ($kind !== \type_model\REPRESENTATION_STRUCTURE) && ($kind !== \type_model\REPRESENTATION_OPAQUE)) { throw new \InvalidArgumentException('Struct field requires eligible inline storage'); }
        }
        if ($kind !== \type_model\REPRESENTATION_INTEGER) {
            if (($integer_family !== '') || $wrapping_addition || $ordered_comparison) { throw new \InvalidArgumentException('Integer capabilities require integer representation'); }
        }
    }
}
