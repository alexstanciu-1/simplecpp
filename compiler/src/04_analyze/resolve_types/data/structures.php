<?php
declare(strict_types=1);

/*
 * Role: Resolution worker requests and selected language entry.
 * Used by: Entry_Resolver; signature and local type workers/joins
 * Flow: fixed inputs -> owned contracts -> read-only consumers
 */

namespace resolve_types;

use type_model\representation_kind;

// Language-level entry selection; native startup/exit adaptation is a separate
// target-owned result and must not be inferred from this return definition.
/** @compiler-api Read-only selected symbol/return_type pair from Entry_Resolver; language policy, not native ABI. */
final class entry_contract implements \compile\Step_Result
{
    /** @compiler-internal Producer-only construction; readiness follows the owning process contract. */
    public function __construct(
        public readonly \collect_symbols\symbol_record $symbol,
        public readonly \type_model\named_type_definition $return_type,
    )
    {
        if (($symbol->kind !== \collect_symbols\symbol_kind::file_entry)
            || ($return_type->representation->kind !== representation_kind::integer)) {
            throw new \LogicException('Entry contract requires a file entry and integer return definition');
        }
    }
}

/** @compiler-api Read-only worker request: symbol, return annotation/definition and ordered parameter definitions; no canonical IDs allocated yet. */
final class signature_request
{
    public readonly int $callable_id;
    /**
     * @compiler-internal Producer-only construction; definitions retain catalog identity.
     * @param list<\type_model\named_type_definition> $parameter_definitions In declaration order.
     * @param list<\type_model\argument_passing> $parameter_passing Ordered contracts; omitted means all value parameters.
     */
    public function __construct(
        public readonly \collect_symbols\symbol_record $symbol,
        public readonly int $return_annotation_id,
        public readonly \type_model\named_type_definition $definition,
        public readonly array $parameter_definitions = [],
        public readonly ?\instantiate\instance_context $instance = null,
        public readonly array $parameter_passing = [],
    )
    {
        $this->callable_id = $instance?->context_id ?? $symbol->symbol_id;
    }
}

// Worker output references authoritative definitions; only the join assigns IDs.
/** @compiler-api Read-only worker request: owner, names, ordered definitions; retains exact input identities. */
final class local_type_request
{
    public readonly int $callable_id;
    /**
     * @compiler-internal Producer-only construction; readiness follows the owning process contract.
     * @param list<\type_model\named_type_definition> $definitions Body-local suffix in local-ID order; parameter types come from the signature join.
     */
    public function __construct(
        public readonly \collect_symbols\symbol_record $owner,
        public readonly \resolve_symbols\Symbol_Resolution $names,
        public readonly array $definitions,
        public readonly ?\instantiate\instance_context $instance = null,
    )
    {
        $this->callable_id = $instance?->context_id ?? $owner->symbol_id;
    }
}
