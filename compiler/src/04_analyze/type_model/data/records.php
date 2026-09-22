<?php
declare(strict_types=1);

/*
 * Role: Normalized structural declarations shared by source and provider producers.
 * Used by: source/provider normalization; Record_Definitions; Layout_Join
 * Flow: fixed inputs -> owned contracts -> read-only consumers
 */

namespace type_model;

/** Physical-layout authority is explicit; imported measurements constrain the generated layout. */
enum record_layout_policy: string {
    case target = 'target';
    case native_verified = 'native_verified';
}

/** Provider measurement in an exact target context, before any canonical type IDs are allocated. */
final class native_record_layout
{
    /** Capture complete native storage facts; ordered offsets correspond to normalized field declarations.
     * @param list<int> $offsets */
    public function __construct(public readonly string $target_triple, public readonly string $data_layout,
        public readonly int $size, public readonly int $alignment, public readonly array $offsets)
    {
        if (($target_triple === '') || ($data_layout === '') || ($size <= 0) || ($alignment <= 0)
            || (($alignment & ($alignment - 1)) !== 0) || (($size % $alignment) !== 0)
            || !array_is_list($offsets) || ($offsets === [])) {
            throw new \InvalidArgumentException('Invalid native record layout');
        }
        $previous = -1;
        foreach ($offsets as $offset) {
            if (!is_int($offset) || ($offset <= $previous) || ($offset >= $size)) {
                throw new \InvalidArgumentException('Invalid native field offset');
            }
            $previous = $offset;
        }
    }
}

/** Normalized field input, independent of syntax and target offsets. */
final class field_declaration {
    public function __construct(public readonly string $name, public readonly named_type_definition|array_type_definition $definition,
        public readonly bool $writable = true)
    {
    }
}

/** Common structural input: automatic fields, optional source body declaration IDs and layout authority. */
final class record_declaration
{
    /** @param list<field_declaration> $fields Ordered semantic fields with explicit layout/construction authority. */
    public function __construct(public readonly string $name, public readonly string $namespace_name,
        public readonly array $fields, public readonly bool $automatic_lifecycle, public readonly record_layout_policy $layout_policy,
        public readonly ?native_record_layout $native_layout = null,
        public readonly int $constructor_body = 0, public readonly int $destructor_body = 0,
        public readonly int $copy_body = 0,
        public readonly int $assignment_body = 0)
    {
    }
}

/** Producer-neutral array recipe; canonical element IDs are allocated only by the type join. */
final class array_type_definition
{
    public readonly bool $struct_field;

    /** Derive inline-storage eligibility from the element contract, never from its spelling. */
    public function __construct(public readonly named_type_definition $element, public readonly int $count)
    {
        if ($count <= 0) {
            throw new \InvalidArgumentException('Array extent must be positive');
        }
        if (($element->resource !== null) || ($element->resource_paths !== [])) {
            throw new \InvalidArgumentException('Arrays of allocation owners require dynamic subobject ownership contracts');
        }
        $this->struct_field = $element->struct_field;
    }
}
