<?php
declare(strict_types=1);

/*
 * Role: Checked-body lifetime facts and reachable block IDs.
 * Used by: Lifetime_Worker; Lifetime_Join; Lowerer
 * Flow: Checked_Body + lifetime facts -> Analyzed_Body
 */

namespace analyze_lifetimes;

/**
 * @compiler-api Lifetime output read by lowering; readable body, lifetimes, reachable_statement_count,
 * falls_through, local_lifetimes, reachable_blocks, ordered cleanups and optional allocations.
 * body is the exact checked input; allocations retain sparse resource states for that body.
 * Reachability follows explicit graph edges; statement count is not a prefix boundary.
 * Temporary facts cover reached values; local exits are ordered by block and boundary.
 * Value/statement/local IDs belong to body and its names; do not mix snapshots.
 * Read-only after join. Constructor checks local indexing, not complete analysis.
 */
class Analyzed_Body
{
    /** @var array<int, int> Reached local ID -> lifetime row. */
    private readonly array $by_local;

    /**
     * @compiler-internal Producer-only construction; readiness follows the owning process contract.
     * @param list<value_lifetime> $lifetimes Only reachable values, in consumption order; statement IDs are nondecreasing.
     * @param list<local_lifetime> $local_lifetimes In exit order, inner/recent first.
     * @param list<cleanup_obligation> $cleanups Only managed objects, ordered by block/boundary and destruction order.
     */
    public function __construct(
        public readonly \check_bodies\Checked_Body $body,
        public readonly array $lifetimes,

        // Number of statements in reachable_blocks; never use as a source prefix.
        public readonly int $reachable_statement_count,
        public readonly bool $falls_through,
        public readonly array $local_lifetimes = [],
        public readonly array $reachable_blocks = [1],
        public readonly array $cleanups = [],
        public readonly ?allocation_analysis $allocations = null,
        public readonly ?ownership_result $ownership = null,
    )
    {
        if (($allocations !== null) && ($allocations->body !== $body)) {
            throw new \LogicException('Allocation facts belong to another checked body');
        }
        if (($ownership !== null) && (($ownership->task->subject !== $body) || ($ownership->allocations !== $allocations))) {
            throw new \LogicException('Stale ownership input for lifetime result');
        }
        $index = [];
        $exits = [];
        foreach ($local_lifetimes as $row => $local)
        {
            $key = $local->local_id . ':' . $local->block_id . ':' . $local->end_after_statement;
            if (($local->local_id <= 0) || isset($exits[$key]) || ($local->initialized_statement_id < 0)
                || ($local->initialized_statement_id > $local->end_after_statement)
                || ($local->end_after_statement > count($body->statements))) {
                throw new \LogicException('Invalid or duplicate analyzed local lifetime');
            }
            $binding = $body->names->local_for($local->local_id);
            if ((($local->initialized_statement_id === 0) !== ($local->local_id <= $body->entry_parameter_count()))
                || (($local->initialized_statement_id === 0) && ($binding->scope_id !== 1))) {
                throw new \LogicException('Invalid local entry initialization');
            }
            if (isset($index[$local->local_id])
                && ($local_lifetimes[$index[$local->local_id]]->initialized_statement_id !== $local->initialized_statement_id)) {
                throw new \LogicException('Inconsistent analyzed local initialization');
            }
            $exits[$key] = true;
            $index[$local->local_id] ??= $row;
        }
        $this->by_local = $index;
        $this->validate_cleanups();
        $this->validate_allocations();
    }

    /**
     * Validate completeness and destruction order against checked construction and lifetime facts.
     * Boundary ranks are private scratch data; no flow analysis or retained ordering fields are added.
     */
    private function validate_cleanups(): void
    {
        $body = $this->body;

        // Every managed local exit needs cleanup on that path, in reverse initialization order.
        // Rank phase 1 follows full-expression temporaries (phase 0) at a shared boundary.
        $expected = [];
        foreach ($this->local_lifetimes as $local) {
            if (!$body->local_passing($local->local_id)->is_borrow()
                && ($body->definition_for($body->local_type_for($local->local_id))->lifetime?->cleanup === \type_model\cleanup_kind::destroy)) {
                $expected['local:' . $local->local_id . ':' . $local->block_id . ':' . $local->end_after_statement]
                    = [1, -$local->initialized_statement_id];
            }
        }

        // Constructed local destinations have already transferred ownership out of temporaries.
        // Checked result IDs follow producer order for calls and composed defaults alike.
        foreach ($this->lifetimes as $lifetime)
        {
            $value = $body->values[$lifetime->value_id - 1];
            if (in_array($value->kind, [\check_bodies\value_kind::call_result, \check_bodies\value_kind::default_construct], true) && ($lifetime->end !== lifetime_end::local_construct)
                && !(($lifetime->end === lifetime_end::return_construct)
                    && ($body->statements[$lifetime->statement_id - 1]->return === \check_bodies\return_kind::direct_construct))
                && ($body->definition_for($value->type_id)->lifetime?->cleanup === \type_model\cleanup_kind::destroy)) {
                $expected['temporary:' . $lifetime->value_id . ':' . $lifetime->statement_id] = [0, -$lifetime->value_id];
            }
        }

        // Consume each obligation once and require the complete boundary/destruction sequence.
        $ranks = array_flip($this->reachable_blocks);
        $previous = [-1, -1, -1, 0];
        foreach ($this->cleanups as $cleanup)
        {
            $block = $body->blocks[$cleanup->block_id - 1] ?? null;
            $block_rank = $ranks[$cleanup->block_id] ?? -1;
            $key = $cleanup->subject->value . ':' . $cleanup->subject_id . ':'
                . ($cleanup->subject === cleanup_subject::local ? $cleanup->block_id . ':' : '') . $cleanup->after_statement;
            if (!isset($expected[$key]) || ($block === null) || ($block_rank < 0)
                || ($cleanup->after_statement < $block->statement_start)
                || ($cleanup->after_statement > ($block->statement_start + $block->statement_count))) {
                throw new \LogicException('Invalid or duplicate cleanup obligation');
            }

            // At one boundary, temporaries precede locals and each group unwinds construction.
            $order = [$block_rank, $cleanup->after_statement, ...$expected[$key]];
            if ($order < $previous) {
                throw new \LogicException('Invalid cleanup destruction order');
            }
            unset($expected[$key]);
            $previous = $order;
        }

        if ($expected !== []) {
            throw new \LogicException('Missing owned-object cleanup obligations');
        }
    }

    /** Validate resource-result coverage and provenance without rerunning the worker's flow analysis. */
    private function validate_allocations(): void
    {
        $owners = Resource_Locations::locals($this->body);
        if ((($owners !== []) || ($this->ownership !== null)) !== ($this->allocations !== null)) {
            throw new \LogicException('Missing or unexpected allocation analysis');
        }
        if ($this->allocations === null) {
            return;
        }
        $blocks = array_keys($this->allocations->entries);
        $reachable = $this->reachable_blocks;
        sort($reachable);
        if ($blocks !== $reachable) {
            throw new \LogicException('Allocation analysis must cover reachable blocks');
        }
        foreach ($this->allocations->entries as $state) {
            foreach ($state as $local => $value) {
                if (!isset($owners[$local]) || (!is_int($value) || ($value < 1) || ($value > 15))) {
                    throw new \LogicException('Invalid allocation entry state');
                }
            }
        }
    }

    // Unreached declarations have no runtime lifetime.
    /**
     * @compiler-api Read the first exit of a reached local; all exits share its initialization.
     * Use local_lifetimes for all paths. Null means valid but unreached.
     * Throws for an invalid local ID. Does not create storage or cleanup.
     */
    public function local_for(int $local_id): ?local_lifetime
    {
        $row = $this->by_local[$local_id] ?? null;
        if ($row !== null) {
            return $this->local_lifetimes[$row];
        }
        $this->body->names->local_for($local_id); // Distinguish unreached from invalid IDs.
        return null;
    }

    /** @compiler-api On-demand debug view; not a semantic input or a persisted-cache format. */
    public function to_array(): array
    {
        return ['symbol_id' => $this->body->owner->symbol_id, 'callable_id' => $this->body->callable_id,
            'reachable_statement_count' => $this->reachable_statement_count,
            'falls_through' => $this->falls_through, 'lifetimes' => $this->lifetimes,
            'local_lifetimes' => $this->local_lifetimes, 'reachable_blocks' => $this->reachable_blocks, 'cleanups' => $this->cleanups,
            'allocations' => $this->allocations?->entries];
    }
}
