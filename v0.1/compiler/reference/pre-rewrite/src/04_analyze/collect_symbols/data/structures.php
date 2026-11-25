<?php
declare(strict_types=1);

/*
 * Role: Symbol identities, extracted declarations and change records.
 * Used by: Declaration_Collector; Symbol_Comparer; joins
 * Flow: file declarations -> symbols -> change catalog
 */

namespace collect_symbols;

const MAX_SYMBOL_ID = 0xffffffff;

// Project declarations, change descriptions, and separate resolution work.

/** @compiler-api Project declaration categories; file_entry is an unnamed callable indexed by file origin. */
enum symbol_kind: int
{
    case invalid = 0;
    case function_symbol = 1;

    // Implicit callable, indexed by source origin rather than a fabricated name.
    case file_entry = 2;
    case struct_symbol = 3;
    case template_struct = 4;
    case template_function = 5;
    case constant_symbol = 6;
}

/**
 * @compiler-api Readable declaration record produced by collection; all public fields are facts.
 * frontend anchors source declarations; external anchors provider declarations.
 * Provider symbols have no source origin or AST/body IDs.
 * symbol_id is project lineage identity, not a row offset; owner_symbol_id zero is
 * project scope. Only collection fills mutable fields before joining; other steps
 * must not mutate a shared record or reuse its IDs against a newer frontend.
 */
class symbol_record
{
    // All local node IDs refer to this exact frontend snapshot; no syntax copies.
    // Source-file identity and source bytes come from frontend, not a second field.
    /** @compiler-internal Producer-only construction; readiness follows the owning process contract. */
    public function __construct(
        public readonly int $symbol_id,
        public readonly ?\parse\File_Frontend $frontend,
        public readonly \type_model\runtime_callable|\type_model\storage_family|\type_model\storage_function|\type_model\family_declaration|\type_model\family_method|null $external = null,
    )
    {
        if (($symbol_id <= 0) || (($frontend === null) === ($external === null))) {
            throw new \InvalidArgumentException('Invalid symbol identity');
        }
    }
    public string $name = "";
    public string $namespace_name = "";

    // Zero means project scope. Members reference their semantic owner.
    public int $owner_symbol_id = 0;
    public int $template_parameters_node_id = 0;
    public bool $receiver_const = false;

    // Zero when absent (e.g. the implicit entry has no source declaration).
    public int $declaration_node_id = 0;
    public int $body_node_id = 0;
    public symbol_kind $kind = symbol_kind::invalid;

    /** @compiler-api Concrete executable participation; a template can retain body syntax without participating. */
    public function has_executable_body(): bool
    {
        return ($this->frontend !== null) && ($this->body_node_id > 0) && (!$this->is_template());
    }

    /** A definition is not a concrete type or executable callable. */
    public function is_template(): bool
    {
        return in_array($this->kind, [symbol_kind::template_struct, symbol_kind::template_function], true);
    }

    /** @compiler-api On-demand debug view; not a semantic input or a persisted-cache format. */
    public function to_array(): array
    {
        return ['symbol_id' => $this->symbol_id, 'kind' => $this->kind->name,
            'name' => $this->name, 'namespace_name' => $this->namespace_name,
            'owner_symbol_id' => $this->owner_symbol_id,
            'template_parameters_node_id' => $this->template_parameters_node_id, 'receiver_const' => $this->receiver_const,
            'source_file_id' => $this->frontend?->source_file_id,
            'provider' => $this->external?->provider, 'provider_operation' => $this->external?->id,
            'declaration_node_id' => $this->declaration_node_id, 'body_node_id' => $this->body_node_id];
    }
}

/** @compiler-api Own-definition change fact, not work selection. uncompared requires comparison before admission. */
enum change_status: int {
    case unchanged = 0;
    case added = 1;
    case changed = 2;
    case removed = 3;

    // Identity matched, but definition/body comparison has not been performed.
    case uncompared = 4;
}

// One semantic boundary's change within an update, not a work-selection flag.
// Flat catalog rows use the symbols' owner IDs for hierarchy, not nested copies.
// A body is tracked child content, not a separate change category/property.
// The nullable summary distinguishes not-established/not-applicable from false;
// it does not imply every semantic element has children.
/**
 * @compiler-api Read-only per-update pair/status/child summary produced by collection/comparison.
 * All readonly fields are readable by compile policy and debug. Null sides denote
 * addition/removal; null children_changed is unestablished or inapplicable. Matched
 * symbols keep exact old/new frontend references; no duplicated syntax or unchanged
 * records are required. Child change alone does not prescribe recompiling an owner.
 */
class symbol_change
{
    /** @compiler-internal Producer-only construction; readiness follows the owning process contract. */
    public function __construct(
        public readonly ?symbol_record $previous,
        public readonly ?symbol_record $current,
        public readonly change_status $own_status,
        public readonly ?bool $children_changed = null
    )
    {
        if ((($previous === null) && ($current === null))
            || (($previous !== null) && ($current !== null) && ($previous->symbol_id !== $current->symbol_id))
            || (($previous === null) !== ($own_status === change_status::added))
            || (($current === null) !== ($own_status === change_status::removed))) {
            throw new \InvalidArgumentException('Change status must match previous/current symbol identities');
        }
    }
}

// File-worker output, before the coordinator assigns project identities.
// Temporary extraction facts; AST nodes and source bytes are never copied.
/**
 * @compiler-api Read-only file-worker fact carried in File_Declarations; all fields readable by
 * its coordinator. Node IDs belong to that frontend; no project ID has been assigned.
 */
class declaration
{
    /** @compiler-internal Producer-only construction; readiness follows the owning process contract. */
    public function __construct(
        public readonly symbol_kind $kind,
        public readonly string $name,
        public readonly int $declaration_node_id,
        public readonly int $body_node_id,
        public readonly int $owner_declaration_node_id = 0,
        public readonly int $template_parameters_node_id = 0,
        public readonly bool $receiver_const = false,
    )
    {
    }
}
