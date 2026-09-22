<?php
declare(strict_types=1);

/*
 * Role: Infer alias exclusions and apply complete calls against one pre-call snapshot.
 * Used by: Allocation_Flow (private trait methods on this owner)
 * Call map: apply_summary() -> map_summary(); check_summary(); apply_transition()
 *   record_access() -> exclude() [validation only]
 * Stable object borrows do not pin allocations; element borrows do.
 */
namespace analyze_lifetimes;

trait Resource_Aliasing
{
    /** Check all operands before committing any effect; parameter order never becomes callee execution order. */
    private function apply_summary(ownership_summary $summary, array $operands, resource_flow_state $flow,
        array $borrows, ?ownership_observations $observations, int $node): void
    {
        $mapped = $this->map_summary($summary, $operands);
        $effects = $this->check_summary($summary, $mapped, $flow, $borrows, $observations, $node);
        foreach ($effects as $effect) {
            $this->apply_transition($effect->location, $effect->transition, $flow);
        }
    }

    /**
     * Resolve parameter-relative paths once; retain every operand's requirements even when locations alias.
     * @param array<int, resource_location> $operands Zero-based semantic parameter positions.
     * @return array<string, bound_resource_effect> Exact endpoint to bound effect.
     */
    private function map_summary(ownership_summary $summary, array $operands): array
    {
        $mapped = [];
        foreach ($summary->parameters as $position => $fields) {
            foreach ($fields as $path => $transition) {
                $mapped[Resource_Locations::parameter_key($position, $path)] = new bound_resource_effect(
                    Resource_Locations::project($operands[$position], $path), $transition);
            }
        }
        return $mapped;
    }

    /**
     * Validate the unchanged pre-call state and select at most one writer for each aliased resource leaf.
     * @param array<string, bound_resource_effect> $mapped Every bound operand.
     * @return array<string|int, bound_resource_effect> One effect per caller resource.
     */
    private function check_summary(ownership_summary $summary, array $mapped, resource_flow_state $flow,
        array $borrows, ?ownership_observations $observations, int $node): array
    {
        $effects = [];
        foreach ($mapped as $effect)
        {
            $location = $effect->location;
            $transition = $effect->transition;
            $this->require_state($location, $transition->required, $node, $flow->states, $observations,
                'Source call ownership requirements are not satisfied');
            if ($transition->accessed) {
                $this->record_access($location, $flow->mutations, $observations, $node);
            }
            $key = $location->key();
            $previous = ($effects[$key] ?? null)?->transition;
            if (($observations !== null) && ($previous?->mutates === true) && ($transition->mutates)) {
                $this->fail($node, 'Aliased arguments require a single resource writer');
            }

            // A read-only alias must not overwrite its writer's poststate.
            if (($previous === null) || ($transition->mutates)) {
                $effects[$key] = $effect;
            }
        }
        foreach ($summary->distinct as [$left, $right]) {
            $this->exclude($mapped[$left]->location, $mapped[$right]->location, $observations, $node);
        }
        foreach ($effects as $effect) {
            $this->check_mutation($effect->location, $effect->transition, $borrows, $observations, $node);
        }
        return $effects;
    }

    /** Observe access against preceding mutations without changing flow facts or checking ownership state. */
    private function record_access(resource_location $location, array $preceding_mutations,
        ?ownership_observations $observations, int $node): void
    {
        $key = $location->key();
        if (($observations === null) || !isset($this->parameters[$key])) {
            return;
        }
        foreach ($preceding_mutations as $previous => $_) {
            $other = $this->locations[$previous];
            if ($other->local !== $location->local) {
                $this->exclude($other, $location, $observations, $node);
            }
        }
        $observations->accessed[$key] = true;
    }

    /** Discharge known locations now; retain parameter-relative exclusions for callers to discharge. */
    private function exclude(resource_location $left, resource_location $right, ?ownership_observations $observations, int $node): void
    {
        if ($observations === null) {
            return;
        }
        if (Resource_Locations::overlaps($left, $right)) {
            $this->fail($node, 'Aliased arguments violate resource access order or allocation stability');
        }
        if (($left->local !== $right->local) && isset($this->parameters[$left->key()], $this->parameters[$right->key()])) {
            $pair = Resource_Locations::distinct_pair(
                Resource_Locations::parameter_key($left->local - 1, Resource_Locations::path_key($left->path)),
                Resource_Locations::parameter_key($right->local - 1, Resource_Locations::path_key($right->path)));
            $observations->distinct[Resource_Locations::distinct_key($pair)] = $pair;
        }
    }
}
