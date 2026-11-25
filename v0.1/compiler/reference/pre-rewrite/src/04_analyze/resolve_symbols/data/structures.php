<?php
declare(strict_types=1);

/*
 * Role: Callable scopes, locals, name bindings and traversal cursors.
 * Used by: Resolution_Worker; semantic consumers
 * Flow: syntax uses -> local/project bindings
 */

namespace resolve_symbols;

// A use within the owning Symbol_Resolution syntax snapshot, referencing
// a target declaration by stable symbol ID. Owner/origin live on the result.
/**
 * @compiler-api Read-only name-resolution fact shared with semantic consumers.
 * use_node_id is an AST callee-name ID; target_symbol_id is a project identity.
 * IDs are interpreted with the owning Symbol_Resolution; not across snapshots.
 */
final class symbol_binding
{
    /** @compiler-internal Producer-only construction; readiness follows the owning process contract. */
    public function __construct(
        public readonly int $use_node_id,
        public readonly int $target_symbol_id
    )
    {
    }
}

// IDs are one-based positions within one callable resolution, never project IDs.
/**
 * @compiler-api Read-only name-resolution fact shared with semantic consumers.
 * block_node_id belongs to syntax; parent_scope_id is a local scope ID, zero at root.
 * IDs are interpreted with the owning Symbol_Resolution; not across snapshots.
 */
final class lexical_scope
{
    /** @compiler-internal Producer-only construction; readiness follows the owning process contract. */
    public function __construct(
        public readonly int $block_node_id,
        public readonly int $parent_scope_id,
    )
    {
    }
}

// Callable-local declaration: a formal parameter or a body-local declaration.
// The source node supplies its kind, name and annotation without duplicated data.
/**
 * @compiler-api Read-only name-resolution fact shared with semantic consumers.
 * declaration_node_id belongs to syntax; scope_id identifies its lexical scope.
 * IDs are interpreted with the owning Symbol_Resolution; not across snapshots.
 */
final class local_record
{
    /** @compiler-internal Producer-only construction; readiness follows the owning process contract. */
    public function __construct(
        public readonly int $declaration_node_id,
        public readonly int $scope_id,
        public readonly bool $receiver = false,
    )
    {
    }
}

/**
 * @compiler-api Read-only name-resolution fact shared with semantic consumers.
 * Local-use classification; it does not by itself establish a lifetime or write validity.
 * IDs are interpreted with the owning Symbol_Resolution; not across snapshots.
 */
enum local_access {
    case read;
    case write;
}

/**
 * @compiler-api Read-only name-resolution fact shared with semantic consumers.
 * use_node_id belongs to syntax; local_id is callable-local and access is read/write.
 * IDs are interpreted with the owning Symbol_Resolution; not across snapshots.
 */
final class local_binding
{
    /** @compiler-internal Producer-only construction; readiness follows the owning process contract. */
    public function __construct(
        public readonly int $use_node_id,
        public readonly int $local_id,
        public readonly local_access $access,
    )
    {
    }
}

// Private worker traversal state; never retained in a resolution result.
/** @compiler-internal Private name-worker traversal state, discarded after resolution. */
final class scope_cursor
{
    /** @compiler-internal Producer-only construction; readiness follows the owning process contract. */
    public function __construct(
        public readonly int $scope_id,
        public int $next_statement_id,
        public readonly bool $owns_scope = true,
    )
    {
    }
}

/** A member call preserves its receiver occurrence; concrete type preparation selects the member definition. */
final class member_call_binding {
    public function __construct(public readonly int $use_node_id, public readonly int $receiver_node_id)
    {
    }
}
