<?php
declare(strict_types=1);

/*
 * Role: ABI-independent semantic call contracts.
 * Used by: package/family adapters; type and body checking; ABI validation
 * Flow: declared types/passing/ownership -> fixed signature -> concrete consumers
 */
namespace type_model;

/** @compiler-api Semantic argument use; references grant const or mutable access only through this call. */
enum argument_passing: string
{
    case value = 'value';
    case borrow_const = 'borrow_const';
    case borrow_mutable = 'borrow_mutable';
    case byte_span = 'byte_span';

    /** An object or scalar borrow supplies stable storage; a byte span has a separate ABI. */
    public function is_borrow(): bool
    {
        return ($this === self::borrow_const) || ($this === self::borrow_mutable);
    }
}

enum result_production: string {
    case none = 'none';
    case value = 'value';
    case owned = 'owned';
    // Unresolved family value result: binding determines value versus an owned object.
    case dependent_value = 'dependent_value';
}

final class semantic_parameter {
    public function __construct(public readonly type_reference $type, public readonly argument_passing $passing)
    {
    }
}

final class semantic_result {
    public function __construct(public readonly type_reference $type, public readonly result_production $production)
    {
    }
}

final class semantic_signature
{
    /** Parameters are semantic positions; the receiver is an ordinary parameter when present.
     * @param list<semantic_parameter> $parameters */
    public function __construct(public readonly array $parameters, public readonly semantic_result $result,
        public readonly ?allocation_effect $allocation_effect = null)
    {
        if (!array_is_list($parameters)) {
            throw new \InvalidArgumentException('Semantic parameters require an ordered list');
        }
    }
}
