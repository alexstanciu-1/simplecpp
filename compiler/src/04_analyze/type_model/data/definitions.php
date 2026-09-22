<?php
declare(strict_types=1);
namespace type_model;

/** Authoritative scalar definition. Storage identity alone never grants language capabilities. */
final class Named_Definition {
    public function __construct(public readonly string $name, public readonly string $namespace_name,
        public readonly Representation $representation, public readonly ?Lifetime_Contract $lifetime,
        public readonly ?bool $signed, public readonly string $integer_family,
        public readonly bool $wrapping_addition, public readonly bool $ordered_comparison,
        public readonly bool $struct_field) {
        if ($name === '') { throw new \InvalidArgumentException('Named definition requires a name'); }
        $kind = $representation->kind();
        if (($kind !== \type_model\REPRESENTATION_VOID) && ($kind !== \type_model\REPRESENTATION_INTEGER)
            && ($kind !== \type_model\REPRESENTATION_FLOATING)) { throw new \InvalidArgumentException('Scalar catalog does not describe this representation'); }
        if (($kind === \type_model\REPRESENTATION_VOID) !== ($lifetime === null)) { throw new \InvalidArgumentException('Value types require lifetime; void must have none'); }
        if (($kind === \type_model\REPRESENTATION_INTEGER) !== ($signed !== null)) { throw new \InvalidArgumentException('Only integer definitions require signedness'); }
        if ($kind !== \type_model\REPRESENTATION_INTEGER) {
            if (($integer_family !== '') || $wrapping_addition || $ordered_comparison || $struct_field) { throw new \InvalidArgumentException('Integer capabilities require integer representation'); }
        }
    }
}
