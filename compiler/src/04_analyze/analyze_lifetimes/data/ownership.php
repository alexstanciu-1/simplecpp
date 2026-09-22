<?php
declare(strict_types=1);

/*
 * Role: Resource locations, compact transitions and fixed ownership task outputs.
 * Used by: ownership preparation, Allocation_Flow and lifetime acceptance
 * Flow: immutable type/body inputs -> private analysis -> accepted summaries and facts.
 */
namespace analyze_lifetimes;

/** A descriptor location; element indices are deliberately not part of its identity. */
final class resource_location
{
    /** @param list<int> $path Canonical static field ordinals. */
    public function __construct(public readonly int $local, public readonly array $path = [])
    {
    }

    public function key(): string
    {
        return (string)$this->local . ($this->path === [] ? '' : ':' . Resource_Locations::path_key($this->path));
    }
}

/** Private block facts; clone before traversal so fixed-point entry snapshots remain unchanged. */
final class resource_flow_state {
    /**
     * @param array<string|int, int> $states Resource-location key to two-lane transfer relation.
     * @param array<string|int, bool> $mutations Parameter leaves mutated on any preceding path.
     */
    public function __construct(public array $states = [], public array $mutations = [])
    {
    }
}

/** Private validation observations; solving never receives or mutates this record. */
final class ownership_observations
{
    /** @var array<string|int, bool> */
    public array $mutated = [];
    /** @var array<string|int, int> Union of transfer relations at normal exits. */
    public array $returns = [];
    /** @var array<string, int> Owned result field poststates, separate from parameter relations. */
    public array $result = [];
    /** @var array<string|int, bool> */
    public array $accessed = [];
    /** @var array<string, array{string, string}> Canonical parameter-relative exclusions. */
    public array $distinct = [];

    /** @param array<string|int, int> $required Allowed incoming states for each parameter leaf. */
    public function __construct(public array $required = [])
    {
    }
}

/**
 * Required is an incoming-state mask; result is a two-lane transfer relation.
 * Mutation includes allocation replacement even when result is IDENTITY.
 * Accessed distinguishes an unused field from one whose state stays unchanged.
 */
final class resource_transition
{
    public function __construct(public readonly int $required, public readonly int $result,
        public readonly bool $mutates = false, public readonly bool $accessed = true)
    {
        if (($required < Resource_States::EMPTY) || ($required > Resource_States::EITHER) || ($result < 1) || ($result > 15)) {
            throw new \InvalidArgumentException('Invalid resource transition');
        }
    }
}

/** Private call row; mapping and alias grouping share the same bound location/transition. */
final class bound_resource_effect {
    public function __construct(public readonly resource_location $location, public readonly resource_transition $transition)
    {
    }
}

/** Meaning only: producer and dependency provenance remain in the accepted result. */
final class ownership_summary {
    /**
     * @param array<int, array<string, resource_transition>> $parameters Zero-based parameter to relative field transitions.
     * @param array<string, array{string, string}> $distinct Exact parameter:path pairs which callers must keep distinct.
     * @param array<string, int> $result Owned result field poststates; no relation to parameter positions.
     */
    public function __construct(public readonly array $parameters = [], public readonly array $distinct = [],
        public readonly array $result = [])
    {
    }
}

/** Complete semantic lifecycle, including resource obligations with no executable cleanup. */
final class ownership_lifecycle
{
    public readonly \type_model\lifecycle_order $order;
    /** @param array<int, string> $children Direct owning field ordinal to lifecycle dependency key. */
    public function __construct(public readonly int $type_id, public readonly \type_model\named_type_definition $definition,
        public readonly \type_model\lifecycle_operation_kind $kind, public readonly array $children, public readonly ?int $body_id)
    {
        $this->order = $kind->composition($body_id !== null);
    }
}

/** Selected work has exact accepted inputs, never a mutable global result table. */
final class ownership_task {
    /** @param array<string, ownership_summary> $dependencies */
    public function __construct(public readonly string $key,
        public readonly \check_bodies\Checked_Body|ownership_lifecycle $subject,
        public readonly array $dependencies)
    {
    }
}

/** Private output, then retained provenance for reuse and caller invalidation. */
final class ownership_result {
    public function __construct(public readonly ownership_task $task, public readonly ownership_summary $summary,
        public readonly ?allocation_analysis $allocations = null)
    {
    }
}
