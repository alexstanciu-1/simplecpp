<?php
declare(strict_types=1);

/*
 * Role: Canonical type rows, value representations and cache context.
 * Used by: Type_Store; resolution joins; backend preparation
 * Flow: fixed inputs -> owned contracts -> read-only consumers
 */

namespace type_model;

/** Identity anchor shared by cloned stores; canonical numeric IDs are local to this lineage. */
final class type_lineage {
}

// Immutable identities of the complete inputs governing this cache. Producers
// supply canonical content/version keys; display names or timestamps alone are
// insufficient. There is no default/unknown context that permits reuse.
/** @compiler-api Read-only cache context: configuration_key, provider_key, target_key; complete version/content facts required. */
final class type_context
{
    /** @compiler-api Construct an immutable shape/context in the authoritative producer; consumers share it unchanged. */
    public function __construct(
        public readonly string $configuration_key,
        public readonly string $provider_key,
        public readonly string $target_key,
    )
    {
        if (($configuration_key === '') || ($provider_key === '') || ($target_key === '')) {
            throw new \InvalidArgumentException('Type context requires configuration, provider and target keys');
        }
    }
}

/** @compiler-api Value shape tags; declared variants do not imply frontend, layout or backend support. */
enum representation_kind: int
{
    case void_type = 0;
    case integer = 1;
    case floating_point = 2;
    case pointer = 3;
    case fixed_array = 4;
    case structure = 5;
    case function_signature = 6;
    case opaque_inline = 7;
    case byte_span = 8;
}

// Formats describe values, not target storage/alignment or backend availability.
/** @compiler-api Floating value formats; no target storage/alignment or operation availability. */
enum floating_format: string {
    case ieee_binary16 = 'ieee_binary16';
    case bfloat16 = 'bfloat16';
    case ieee_binary32 = 'ieee_binary32';
    case ieee_binary64 = 'ieee_binary64';
    case ieee_binary128 = 'ieee_binary128';
}

// Kind-specific payloads become an inline tagged union in the eventual native port.
/** @compiler-api Read-only bit_width payload; language signedness is in named_type_definition. */
final class integer_representation
{
    /** @compiler-api Construct an immutable shape/context in the authoritative producer; consumers share it unchanged. */
    public function __construct(public readonly int $bit_width)
    {
        if ($bit_width < 1) {
            throw new \InvalidArgumentException('Integer width must be positive');
        }
    }
}

/** @compiler-api Read-only floating format payload; bit_width is value width, not storage size. */
final class floating_representation
{
    /** @compiler-api Construct an immutable shape/context in the authoritative producer; consumers share it unchanged. */
    public function __construct(public readonly floating_format $format)
    {
    }

    /** @compiler-api Return format value width in bits; does not query target layout. */
    public function bit_width(): int
    {
        return match ($this->format) {
            floating_format::ieee_binary16, floating_format::bfloat16 => 16,
            floating_format::ieee_binary32 => 32,
            floating_format::ieee_binary64 => 64,
            floating_format::ieee_binary128 => 128,
        };
    }
}

/** @compiler-api Read-only element_type/address_space payload; element ID belongs to the same type lineage. */
final class pointer_representation
{
    /** @compiler-api Construct an immutable shape/context in the authoritative producer; consumers share it unchanged. */
    public function __construct(
        public readonly int $element_type,
        public readonly int $address_space = 0,
    )
    {
        if (($element_type <= 0) || ($address_space < 0)) {
            throw new \InvalidArgumentException('Invalid pointer type or address space');
        }
    }
}

/** @compiler-api Read-only element_type/count payload; count is fixed, not a dynamic vector length. */
final class array_representation
{
    /** @compiler-api Construct an immutable shape/context in the authoritative producer; consumers share it unchanged. */
    public function __construct(public readonly int $element_type, public readonly int $count)
    {
        if (($element_type <= 0) || ($count < 0)) {
            throw new \InvalidArgumentException('Invalid array type or element count');
        }
    }
}

// Zero-based contiguous ranges in Type_Store's member dataset, never node pointers.
/** @compiler-api Read-only first/count range in Type_Store members; zero-based, not syntax node IDs. */
final class structure_representation {
    /** @compiler-api Construct an immutable shape/context in the authoritative producer; consumers share it unchanged. */
    public function __construct(public readonly int $first, public readonly int $count)
    {
    }
}

/** @compiler-api Read-only return, parameter range and ordered passing modes; IDs belong to its type lineage. */
final class signature_representation
{
    /** @var list<argument_passing> Ordered semantic passing contracts, included in interning identity. */
    public readonly array $parameter_passing;
    /** @compiler-api Construct an immutable shape/context in the authoritative producer; consumers share it unchanged. */
    public function __construct(
        public readonly int $return_type,
        public readonly int $first,
        public readonly int $count,
        array $parameter_passing = [],
        public readonly result_production $result = result_production::value,
    )
    {
        $parameter_passing = $parameter_passing === [] ? array_fill(0, $count, argument_passing::value) : $parameter_passing;
        if (!array_is_list($parameter_passing) || (count($parameter_passing) !== $count)) {
            throw new \InvalidArgumentException('Signature passing count differs from parameter count');
        }
        foreach ($parameter_passing as $passing) {
            if (!$passing instanceof argument_passing) {
                throw new \InvalidArgumentException('Invalid signature passing mode');
            }
        }
        $this->parameter_passing = $parameter_passing;
    }
}

/** @compiler-api Read-only type_id/name in an owning Type_Store member range; names apply to fields. */
final class type_member
{
    // Field names are present for structures; signature parameters carry types only.
    /** @compiler-api Construct an immutable shape/context in the authoritative producer; consumers share it unchanged. */
    public function __construct(public readonly int $type_id, public readonly string $name = '', public readonly bool $writable = true)
    {
        if ($type_id <= 0) {
            throw new \InvalidArgumentException('Invalid member type');
        }
    }
}

/** @compiler-api Measured opaque object storage; valid within its type context's provider/target revision. */
final class opaque_representation
{
    public function __construct(public readonly int $size_bytes, public readonly int $alignment_bytes)
    {
        if (($size_bytes < 1) || ($alignment_bytes < 1) || (($alignment_bytes & ($alignment_bytes - 1)) !== 0)
            || (($size_bytes % $alignment_bytes) !== 0)) {
            throw new \InvalidArgumentException('Invalid opaque storage layout');
        }
    }
}

/** @compiler-api Read-only kind/payload discriminated shape; constructor validates the pairing, not backend readiness. */
final class representation_record
{
    /** @compiler-api Construct an immutable shape/context in the authoritative producer; consumers share it unchanged. */
    public function __construct(
        public readonly representation_kind $kind,
        public readonly null|integer_representation|floating_representation|pointer_representation|array_representation|structure_representation|signature_representation|opaque_representation $payload,
    )
    {
        $valid = match ($kind)
        {
            representation_kind::void_type, representation_kind::byte_span => $payload === null,
            representation_kind::integer => $payload instanceof integer_representation,
            representation_kind::floating_point => $payload instanceof floating_representation,
            representation_kind::pointer => $payload instanceof pointer_representation,
            representation_kind::fixed_array => $payload instanceof array_representation,
            representation_kind::structure => $payload instanceof structure_representation,
            representation_kind::function_signature => $payload instanceof signature_representation,
            representation_kind::opaque_inline => $payload instanceof opaque_representation,
        };
        if (!$valid) {
            throw new \InvalidArgumentException('Representation kind/payload mismatch');
        }
    }
}

/**
 * @compiler-api Read-only name/namespace/declaration/representation/definition facts from Type_Store.
 * representation_id zero means unresolved; declared alone does not authorize semantic use.
 */
final class type_record
{
    /** @compiler-internal Producer-only construction; readiness follows the owning process contract. */
    public function __construct(
        public readonly string $name,
        public readonly string $namespace_name,

        // Zero means no representation; it never means void or capability readiness.
        public readonly int $representation_id = 0,
        public readonly bool $declared = false,

        // Shared authoritative meaning, not a copy per annotation. A representation
        // alone is insufficient for semantic consumers or operation selection.
        public readonly ?named_type_definition $definition = null,
    )
    {
    }
}
