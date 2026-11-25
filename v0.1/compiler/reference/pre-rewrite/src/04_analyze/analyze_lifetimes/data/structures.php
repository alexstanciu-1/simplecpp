<?php
declare(strict_types=1);

/*
 * Role: Temporary/local lifetime records and end reasons.
 * Used by: Lifetime_Worker; Lowering_Worker
 * Flow: checked values/locals -> lifetime facts
 */

namespace analyze_lifetimes;

/** @compiler-api Reachable temporary end/use classification, consumed by lowering; copying policy lives in its type. */
enum lifetime_end: string
{
    case discard = 'discard';
    case condition = 'condition';
    case return_copy = 'return_copy';
    case return_construct = 'return_construct';
    case local_copy = 'local_copy';
    case argument_copy = 'argument_copy';
    case argument_borrow = 'argument_borrow';
    case local_construct = 'local_construct';
    case copy_source = 'copy_source';
    case assignment_source = 'assignment_source';
    case conversion_input = 'conversion_input';
    case operation_input = 'operation_input';
    case index_input = 'index_input';
    case target_index = 'target_index';
}

// A reachable temporary's lifetime is currently contained in one statement.
// Type copying/cleanup rules stay in the checked body's shared type definitions.
/**
 * @compiler-api Read-only value_id/statement_id/end fact; both IDs are one-based in the exact checked body.
 * consumer_id identifies a call for argument_copy/argument_borrow or a result for conversion_input/operation_input;
 * zero for statement-boundary ends. A borrow end closes access; cleanup_obligation
 * separately records when the owned object is destroyed.
 * No independent temporary type/cleanup definition is copied here.
 */
final class value_lifetime
{
    /** @compiler-internal Producer-only construction; readiness follows the owning process contract. */
    public function __construct(
        public readonly int $value_id,

        // One-based index in the checked body's statements, not an AST node ID.
        public readonly int $statement_id,
        public readonly lifetime_end $end,
        public readonly int $consumer_id = 0,
    )
    {
        if (($value_id <= 0) || ($statement_id <= 0) || ($consumer_id < 0)
            || (in_array($end, [lifetime_end::argument_copy, lifetime_end::argument_borrow, lifetime_end::conversion_input, lifetime_end::operation_input, lifetime_end::index_input], true) !== ($consumer_id !== 0))) {
            throw new \LogicException('Invalid temporary consumption boundary');
        }
    }
}

/** @compiler-api Local lifetime end classification: lexical scope exit or callable return exit. */
enum local_end: string {
    case scope_exit = 'scope_exit';
    case return_exit = 'return_exit';
}

// One static binding exit per control-flow boundary; a loop exit executes repeatedly.
/**
 * @compiler-api Read-only reached local fact; local_id belongs to checked names. Initialization is a
 * one-based statement ID, or zero for incoming parameters initialized at entry;
 * end_after_statement is the source statement boundary in block_id (zero for empty entry).
 * Return copying occurs before local exits. A binding may have several possible exit rows.
 */
final class local_lifetime
{
    /** @compiler-internal Producer-only construction; readiness follows the owning process contract. */
    public function __construct(
        public readonly int $local_id,
        public readonly int $initialized_statement_id,

        // Static statement boundary within block_id; repeated iterations reuse this fact.
        public readonly int $end_after_statement,
        public readonly local_end $end,
        public readonly int $block_id = 1,
    )
    {
    }
}

// Private worker state. The ordered stack provides reverse declaration exits;
// the live index allows reads/writes without searching the stack or AST.
/** @compiler-internal Private lifetime worker live-stack row; not a retained result or cross-step API. */
final class active_local
{
    /** @compiler-internal Producer-only construction; readiness follows the owning process contract. */
    public function __construct(
        public readonly int $local_id,
        public readonly int $initialized_statement_id,
    )
    {
    }
}

/** @compiler-api Separate ID domains for an owned local and an owned expression temporary. */
enum cleanup_subject: string {
    case local = 'local';
    case temporary = 'temporary';
}

/**
 * @compiler-api Ordered destruction obligation after a statement in a reached block.
 * subject_id is a checked local/value ID selected by subject; zero is never valid.
 * Boundary zero is permitted for an empty block. Type/implementation stay shared in the body.
 */
final class cleanup_obligation
{
    public function __construct(public readonly cleanup_subject $subject, public readonly int $subject_id,
        public readonly int $after_statement, public readonly int $block_id)
    {
        if (($subject_id <= 0) || ($after_statement < 0) || ($block_id <= 0)) {
            throw new \InvalidArgumentException('Invalid cleanup obligation');
        }
    }
}

/** @compiler-api Stable resource transfer relations; keys identify static subobjects within this body. */
final class allocation_analysis {
    /** @param array<int, array<string|int, int>> $entries Block -> resource location key -> two input-state lanes. */
    public function __construct(public readonly \check_bodies\Checked_Body $body, public readonly array $entries)
    {
    }
}
