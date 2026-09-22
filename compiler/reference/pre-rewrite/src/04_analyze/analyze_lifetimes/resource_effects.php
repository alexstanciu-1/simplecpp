<?php
declare(strict_types=1);

/*
 * Role: Check resource requirements, observe accesses and apply transitions as separate operations.
 * Used by: Allocation_Flow (private trait methods on this owner)
 * Call map: effect()/apply_fields() -> require_state(); record_access(); check_mutation(); apply_transition()
 *   require_state() -> accept() [validation only; no access tracking]
 */
namespace analyze_lifetimes;

use type_model\allocation_effect_kind as effect_kind;
use check_bodies\value_kind;

trait Resource_Effects
{
    /** Runtime effects retain explicit transfer ordering while sharing the same checked transition primitives. */
    private function effect(int $call, \type_model\allocation_effect $effect, resource_flow_state $flow,
        array $borrows, ?ownership_observations $observations): void
    {
        $owner = $this->operand($call, $effect->owner);
        $node = $this->node($owner);
        $required = match ($effect->kind) {
            effect_kind::acquire => Resource_States::EMPTY,
            effect_kind::inspect, effect_kind::transfer, effect_kind::mutate => Resource_States::OWNED,
            effect_kind::release, effect_kind::observe => Resource_States::EITHER,
        };
        $result = match ($effect->kind) {
            effect_kind::acquire => Resource_States::OWNED_VALUE,
            effect_kind::release, effect_kind::transfer => Resource_States::EMPTY_VALUE,
            default => Resource_States::IDENTITY,
        };
        $mutates = !in_array($effect->kind, [effect_kind::inspect, effect_kind::observe], true);
        $this->require_state($owner, $required, $node, $flow->states, $observations,
            $required === Resource_States::EMPTY ? 'Allocation acquisition requires an empty owner' : 'Allocation operation requires an owned allocation');
        if ($effect->destination !== null)
        {
            $destination = $this->operand($call, $effect->destination);
            if (Resource_Locations::overlaps($owner, $destination)) {
                $this->fail($node, 'Allocation transfer requires a distinct empty destination');
            }
            $this->exclude($owner, $destination, $observations, $node);
            $this->require_state($destination, Resource_States::EMPTY, $node, $flow->states, $observations,
                'Allocation transfer requires a distinct empty destination');
            $destination_transition = new resource_transition(Resource_States::EMPTY, Resource_States::OWNED_VALUE, true);
            $this->record_access($destination, $flow->mutations, $observations, $node);
            $this->check_mutation($destination, $destination_transition, $borrows, $observations, $node);
            $this->apply_transition($destination, $destination_transition, $flow);
        }
        $transition = new resource_transition($required, $result, $mutates);
        $this->record_access($owner, $flow->mutations, $observations, $node);
        $this->check_mutation($owner, $transition, $borrows, $observations, $node);
        $this->apply_transition($owner, $transition, $flow);
    }

    /** Apply one object's independent field effects; multi-operand calls first resolve aliases in apply_summary(). */
    private function apply_fields(resource_location $base, array $fields, resource_flow_state $flow,
        array $borrows, ?ownership_observations $observations, int $node): void
    {
        foreach ($fields as $path => $transition)
        {
            $location = Resource_Locations::project($base, $path);
            $this->require_state($location, $transition->required, $node, $flow->states, $observations,
                'Source call ownership requirements are not satisfied');
            if ($transition->accessed) {
                $this->record_access($location, $flow->mutations, $observations, $node);
            }
            $this->check_mutation($location, $transition, $borrows, $observations, $node);
            $this->apply_transition($location, $transition, $flow);
        }
    }

    /** A write may change allocation identity even when its final empty/owned state is unchanged. */
    private function check_mutation(resource_location $location, resource_transition $transition,
        array $borrows, ?ownership_observations $observations, int $node): void
    {
        if (($observations === null) || (!$transition->mutates)) {
            return;
        }
        foreach ($borrows as $borrow) {
            if (Resource_Locations::overlaps($location, $borrow)) {
                $this->fail($node, 'Allocation mutation would invalidate an active call borrow');
            }
            $this->exclude($location, $borrow, $observations, $node);
        }
        $observations->mutated[$location->key()] = true;
    }

    /** Commit flow facts only; all requirements and access restrictions were checked before this operation. */
    private function apply_transition(resource_location $location, resource_transition $transition, resource_flow_state $flow): void
    {
        $key = $location->key();
        $flow->states[$key] = Resource_States::compose($flow->states[$key] ?? Resource_States::EMPTY_VALUE, $transition->result);
        if (($transition->accessed) && ($transition->mutates) && isset($this->parameters[$key])) {
            $flow->mutations[$key] = true;
        }
    }

    /** Infer or check ownership requirements without recording an access or modifying flow facts. */
    private function require_state(resource_location $location, int $required, int $node, array $state,
        ?ownership_observations $observations, string $message): void
    {
        if ($observations !== null) {
            $this->accept($observations, $location->key(), Resource_States::compatible($state[$location->key()] ?? 0, $required), $node, $message);
        }
    }

    /** Parameter requirements are inferred; concrete local states cannot acquire caller preconditions. */
    private function accept(ownership_observations $observations, string|int $key, int $compatible, int $node, string $message): void
    {
        if (isset($this->parameters[$key])) {
            $observations->required[$key] &= $compatible;
            $compatible = $observations->required[$key] === 0 ? 0 : Resource_States::EITHER;
        }
        if ($compatible !== Resource_States::EITHER) {
            $this->fail($node, $message);
        }
    }

    /** Only statically named existing owners participate; indexed owner objects remain deferred. */
    private function operand(int $call, int $position): resource_location
    {
        $value = $this->body->values[$this->body->argument_for($call, $position + 1)->value_id - 1];
        if ($value->kind !== value_kind::local_borrow) {
            $this->fail($value->source_node_id, 'Allocation operation requires an existing local owner');
        }
        return $this->source_location($value);
    }

    /** Source calls and implicit copies require a statically identifiable source subobject. */
    private function source_location(\check_bodies\typed_value $value): resource_location
    {
        foreach ($value->payload->projections as $projection) {
            if ($projection->kind !== \check_bodies\projection_kind::field) {
                $this->fail($value->source_node_id, 'Dynamically selected owner subobjects require an ownership contract');
            }
        }
        return Resource_Locations::place($value->payload);
    }

    private function node(resource_location $location): int
    {
        return $this->body->names->local_for($location->local)->declaration_node_id;
    }

    private function fail(int $node, string $message): never
    {
        Resource_Locations::fail($this->body, $node, $message);
    }
}
