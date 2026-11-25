<?php
declare(strict_types=1);

/*
 * Role: Prove local/subobject resource states and infer parameter transitions over the CFG.
 * Used by: Ownership_Worker; Lifetime_Worker for bodies without source ownership dependencies
 * Call map: analyze() -> initial_state(); solve(); block() [validation]
 *   block() -> statement(); leave(); statement() -> expression(); apply_summary() [if assignment]
 * Output: stable entries and a compact parameter summary; private scratch never escapes.
 */
namespace analyze_lifetimes;

use check_bodies\Checked_Body;
use check_bodies\value_kind;

final class Allocation_Flow
{
    use Resource_Effects;
    use Resource_Aliasing;
    private array $locations = [];
    private array $local_keys = [];
    /** @var array<string|int, bool> Fixed parameter-leaf membership, independent of inferred requirements. */
    private array $parameters = [];
    private ?ownership_observations $observations = null;

    /** Dependencies are accepted immutable summaries, captured before this worker starts. */
    public function __construct(private readonly Checked_Body $body, private readonly array $dependencies = [])
    {
    }

    /** Solve immutable block entries first, then collect validation observations through the same traversal. */
    public function analyze(): ?allocation_analysis
    {
        // Fixed location membership is independent of requirements inferred during validation.
        $initial = $this->initial_state();
        if (($this->locations === []) && ($this->dependencies === [])) {
            return null;
        }

        // Solving cannot mutate observations: only the second traversal receives their owner.
        $entries = $this->solve($initial);
        $observations = new ownership_observations(array_fill_keys(array_keys($this->parameters), Resource_States::EITHER));
        $states = [];
        foreach ($entries as $id => $entry) {
            $this->block($id, $entry, $observations);
            $states[$id] = $entry->states;
        }
        $this->observations = $observations;
        return new allocation_analysis($this->body, $states);
    }

    /** Index fixed locations; parameter lanes describe both possible incoming resource states. */
    private function initial_state(): resource_flow_state
    {
        $this->locations = Resource_Locations::locals($this->body);
        $initial = new resource_flow_state();
        foreach ($this->locations as $key => $location)
        {
            $this->local_keys[$location->local][] = $key;
            if ($location->local <= $this->body->entry_parameter_count()) {
                if (!$this->body->local_passing($location->local)->is_borrow() || ($location->path === [])) {
                    $this->fail($this->node($location), 'Allocation owner parameters require an ownership contract');
                }
                $initial->states[$key] = Resource_States::IDENTITY;
                $this->parameters[$key] = true;
            }
        }
        return $initial;
    }

    /** Converge resource relations and preceding parameter mutations; no diagnostic observations are writable. */
    private function solve(resource_flow_state $initial): array
    {
        $entries = [1 => $initial];
        $pending = [1];
        $queued = [1 => true];
        while ($pending !== [])
        {
            $id = array_pop($pending);
            unset($queued[$id]);
            $exit = $this->block($id, $entries[$id], null);
            foreach (\check_bodies\Flow_Graph::successors($this->body->blocks[$id - 1]) as $target)
            {
                $next = $this->retain($exit->states, $this->body->blocks[$target - 1]->scope_id);
                $previous = $entries[$target] ?? null;
                $merged = $previous?->states ?? [];
                foreach ($next as $key => $value) {
                    $merged[$key] = ($merged[$key] ?? 0) | $value;
                }
                $mutations = ($previous?->mutations ?? []) + $exit->mutations;
                if (($previous === null) || ($merged !== $previous->states) || ($mutations !== $previous->mutations)) {
                    $entries[$target] = new resource_flow_state($merged, $mutations);
                    if (!isset($queued[$target])) {
                        $pending[] = $target;
                        $queued[$target] = true;
                    }
                }
            }
        }
        ksort($entries);
        return $entries;
    }

    /** Publish only borrowed parameter leaves; local obligations must already be discharged. */
    public function summary(): ownership_summary
    {
        $observations = $this->observations ?? new ownership_observations();
        $parameters = [];
        foreach ($observations->required as $key => $required)
        {
            $state = $observations->returns[$key] ?? Resource_States::IDENTITY;
            $required &= Resource_States::deterministic($state);
            if ($required === 0) {
                $this->fail($this->node($this->locations[$key]), 'No consistent ownership contract across normal exits');
            }
            $location = $this->locations[$key];
            $mutates = isset($observations->mutated[$key]);
            if (($this->body->local_passing($location->local) === \type_model\argument_passing::borrow_const)
                && (($mutates) || ($state !== Resource_States::IDENTITY))) {
                $this->fail($this->node($location), 'Const borrowed resource state must remain unchanged');
            }
            $parameters[$location->local - 1][Resource_Locations::path_key($location->path)] = new resource_transition($required, $state, $mutates, isset($observations->accessed[$key]));
        }

        // Multiple possible writers require distinct leaves, including writers in different branches.
        $writers = array_intersect_key($observations->mutated, $this->parameters);
        foreach ($writers as $left => $_) {
            foreach ($writers as $right => $_) {
                if (($left < $right) && ($this->locations[$left]->local !== $this->locations[$right]->local)) {
                    $this->exclude($this->locations[$left], $this->locations[$right], $observations, $this->node($this->locations[$left]));
                }
            }
        }
        ksort($observations->distinct);

        // The current result boundary carries a fixed state per field, without path correlation.
        foreach ($observations->result as $state) {
            if (!in_array($state, [Resource_States::EMPTY_VALUE, Resource_States::OWNED_VALUE], true)) {
                $this->fail($this->body->owner->body_node_id, 'Owned result requires a consistent field state across returns');
            }
        }
        return new ownership_summary($parameters, $observations->distinct, $observations->result);
    }

    /** Apply expressions and lexical exits, retaining parameter facts at normal return. */
    private function block(int $id, resource_flow_state $entry, ?ownership_observations $observations): resource_flow_state
    {
        $flow = clone $entry;
        $block = $this->body->blocks[$id - 1];
        if ($observations !== null) {
            foreach ($flow->states as $key => $value) {
                $this->accept($observations, $key, Resource_States::deterministic($value), $this->node($this->locations[$key]),
                    'Allocation ownership differs across control-flow paths');
            }
        }

        // The same checked statements drive both solving and validation.
        $end = $block->statement_start + $block->statement_count;
        for ($index = $block->statement_start; $index < $end; ++$index) {
            $this->statement($this->body->statements[$index], $flow, $observations);
        }

        // Validate outgoing cleanup without discarding parameter facts needed by the return summary.
        $successors = \check_bodies\Flow_Graph::successors($block);
        if ($successors === [])
        {
            $this->leave($flow, 0, $observations);
            if ($observations !== null) {
                foreach ($observations->required as $key => $_) {
                    $observations->returns[$key] = ($observations->returns[$key] ?? 0) | $flow->states[$key];
                }
            }
        }
        else {
            foreach ($successors as $target) {
                $this->leave($flow, $this->body->blocks[$target - 1]->scope_id, $observations);
            }
        }
        return $flow;
    }

    /** Evaluate the destination and source in checked order, then establish local storage and end temporaries. */
    private function statement(\check_bodies\typed_statement $statement, resource_flow_state $flow, ?ownership_observations $observations): void
    {
        $flow->states = $this->leave($flow, $statement->scope_id, $observations);

        // Destination indices precede source evaluation; element destinations pin their backing allocation.
        $next = $statement->call_start;
        foreach ($statement->target?->indices() ?? [] as $projection) {
            $this->expression($projection->operand, $next, $projection->call_end, $flow, $observations);
            $next = $projection->call_end;
        }
        $borrows = [];
        if ($statement->target?->allocation_backed()) {
            $location = Resource_Locations::place($statement->target);
            $this->require_state($location, Resource_States::OWNED, $statement->source_node_id, $flow->states, $observations, 'Element access requires an owned allocation');
            $this->record_access($location, $flow->mutations, $observations, $statement->source_node_id);
            $borrows[-1] = $location;
        }

        // Complete call effects are applied before construction or assignment consumes the source.
        $constructed = $this->expression($statement->value_id, $next,
            $statement->call_start + $statement->call_count, $flow, $observations, $borrows);
        if (($statement->write === \check_bodies\local_write_kind::copy_assign)
            && isset($this->dependencies['assign:' . $this->body->values[$statement->value_id - 1]->type_id])) {
            $source = $this->body->values[$statement->value_id - 1];
            $this->apply_summary($this->dependencies['assign:' . $source->type_id],
                [Resource_Locations::place($statement->target), $this->source_location($source)],
                $flow, $borrows, $observations, $statement->source_node_id);
        }
        $local = $statement->kind === \check_bodies\statement_kind::local_declaration ? $statement->target->local_id : 0;
        if (($local !== 0) && ($statement->write === \check_bodies\local_write_kind::copy_construct)
            && isset($this->dependencies['copy:' . $this->body->local_type_for($local)])) {
            $constructed[$statement->value_id] = $this->source_construction_fields($statement->value_id, $flow, $observations);
        }
        if ($local !== 0) {
            foreach ($this->local_keys[$local] ?? [] as $key) {
                $flow->states[$key] = $constructed[$statement->value_id][Resource_Locations::path_key($this->locations[$key]->path)] ?? Resource_States::EMPTY_VALUE;
            }
            unset($constructed[$statement->value_id]);
        }

        // A returned object becomes caller-owned before temporary or local cleanup.
        if (($statement->kind === \check_bodies\statement_kind::return_statement) && ($statement->value_id !== 0))
        {
            $source = $this->body->values[$statement->value_id - 1];
            if ($this->body->definition_for($source->type_id)->resource_paths !== [])
            {
                $fields = match ($statement->return) {
                    \check_bodies\return_kind::direct_construct => $constructed[$statement->value_id]
                        ?? throw new \LogicException('Owned result requires prepared resource facts'),
                    \check_bodies\return_kind::copy_construct => $this->source_construction_fields($statement->value_id, $flow, $observations),
                    \check_bodies\return_kind::move_construct => $this->source_construction_fields($statement->value_id, $flow, $observations, 'move:'),
                    default => throw new \LogicException('Resource-owning result requires construction'),
                };
                if ($observations !== null) {
                    foreach ($fields as $path => $state) {
                        $observations->result[$path] = ($observations->result[$path] ?? 0) | $state;
                    }
                }
                if ($statement->return === \check_bodies\return_kind::direct_construct) {
                    unset($constructed[$statement->value_id]);
                }
            }
        }

        // Unconsumed constructed values end at the full-expression boundary.
        foreach (array_reverse($constructed, true) as $value => $fields) {
            $temporary = $this->body->values[$value - 1];
            $this->finish_fields($temporary->type_id, $fields, $observations, $temporary->source_node_id);
        }
    }

    /** Scope visibility belongs to root bindings; subobjects share that root's lifetime. */
    private function retain(array $state, int $scope): array
    {
        foreach ($state as $key => $_) {
            if (!Local_Flow::contains($this->body, $this->body->names->local_for($this->locations[$key]->local)->scope_id, $scope)) {
                unset($state[$key]);
            }
        }
        return $state;
    }

    /** Complete object destruction must discharge raw fields; borrowed receivers retain obligations. */
    private function leave(resource_flow_state $flow, int $scope, ?ownership_observations $observations): array
    {
        $retained = $this->retain($flow->states, $scope);
        $locals = [];
        foreach (array_diff_key($flow->states, $retained) as $key => $_) {
            $locals[$this->locations[$key]->local] = true;
        }
        if ($locals === []) {
            return $retained;
        }

        // Exiting leaves are discharged privately; retained states and sibling edges stay unchanged.
        $exit = clone $flow;
        foreach ($locals as $local => $_)
        {
            if ($local <= $this->body->entry_parameter_count()) {
                continue;
            }
            $type = $this->body->local_type_for($local);
            $summary = $this->dependencies['destroy:' . $type] ?? null;
            if ($summary !== null) {
                $this->apply_fields(new resource_location($local), $summary->parameters[0], $exit, [], $observations, $this->body->names->local_for($local)->declaration_node_id);
            }
            foreach ($this->local_keys[$local] as $key) {
                $location = $this->locations[$key];
                $this->require_state($location, Resource_States::EMPTY, $this->node($location), $exit->states, $observations,
                    'Allocation must be released or transferred before its owner leaves scope');
                $this->record_access($location, $exit->mutations, $observations, $this->node($location));
            }
        }
        $flow->mutations = $exit->mutations;
        return $retained;
    }

    /** Consume checked evaluation order, including pending borrows and source method contracts. */
    private function expression(int $root, int $start, int $limit, resource_flow_state $flow, ?ownership_observations $observations, array $borrows = []): array
    {
        $constructed = [];
        foreach (\check_bodies\Expression_Order::steps($this->body, $root, $start, $limit) as $step)
        {
            if ($step->call_id !== 0)
            {
                $call = $this->body->calls[$step->call_id - 1];
                $arguments = [];
                for ($i = 1; $i <= $call->argument_count; ++$i) {
                    $arguments[$this->body->argument_for($step->call_id, $i)->value_id] = true;
                }
                $pending = array_diff_key($borrows, $arguments);
                $effect = $this->body->allocation_effect_for($call->target_callable_id);
                if ($effect !== null) {
                    $this->effect($step->call_id, $effect, $flow, $pending, $observations);
                }
                $summary = $this->dependencies['body:' . $call->target_callable_id] ?? null;
                if ($summary !== null)
                {
                    if ($summary->result !== []) {
                        $constructed[$call->result_value_id] = $summary->result;
                    }
                    $operands = [];
                    foreach ($summary->parameters as $position => $_) {
                        $operands[$position] = $this->operand($step->call_id, $position);
                    }
                    $this->apply_summary($summary, $operands, $flow, $borrows, $observations, $call->source_node_id);
                }
                $borrows = $pending;
            }
            if ($step->value_id !== 0)
            {
                $value = $this->body->values[$step->value_id - 1];
                $place = in_array($value->kind, [value_kind::local_read, value_kind::local_borrow], true) ? $value->payload : null;
                if ($place !== null)
                {
                    $location = Resource_Locations::place($place);
                    if ($place->allocation_backed()) {
                        $this->require_state($location, Resource_States::OWNED, $value->source_node_id, $flow->states, $observations, 'Element access requires an owned allocation');
                        $this->record_access($location, $flow->mutations, $observations, $value->source_node_id);
                    }
                    if (($value->kind === value_kind::local_borrow) && $place->allocation_backed()) {
                        $borrows[$step->value_id] = $location;
                    }
                }
                if (in_array($value->kind, [value_kind::default_construct, value_kind::record_default], true)
                    && isset($this->dependencies['construct:' . $value->type_id])) {
                    $constructed[$step->value_id] = $this->construction_fields($this->dependencies['construct:' . $value->type_id],
                        $observations, $value->source_node_id);
                }
            }
        }
        return $constructed;
    }

    /** Apply the selected copy/move source contract and establish a distinct destination in empty storage. */
    private function source_construction_fields(int $value_id, resource_flow_state $flow, ?ownership_observations $observations, string $prefix = 'copy:'): array
    {
        $value = $this->body->values[$value_id - 1];
        $summary = $this->dependencies[$prefix . $value->type_id];
        $source = $this->source_location($value);
        $this->apply_fields($source, $summary->parameters[1], $flow, [], $observations, $value->source_node_id);
        return $this->construction_fields($summary, $observations, $value->source_node_id);
    }

    /** Both default and copy construction start a fresh destination under its complete operation contract. */
    private function construction_fields(ownership_summary $summary, ?ownership_observations $observations, int $node): array
    {
        $fields = [];
        foreach ($summary->parameters[0] as $path => $transition) {
            if (($observations !== null) && (($transition->required & Resource_States::EMPTY) === 0)) {
                $this->fail($node, 'Complete construction requires empty field storage');
            }
            $fields[$path] = Resource_States::compose(Resource_States::EMPTY_VALUE, $transition->result);
        }
        return $fields;
    }

    /** Validate and consume complete destruction of a full-expression temporary. */
    private function finish_fields(int $type, array $fields, ?ownership_observations $observations, int $node): void
    {
        $summary = $this->dependencies['destroy:' . $type] ?? throw new \LogicException('Missing temporary destruction summary');
        foreach ($fields as $path => $state) {
            $transition = $summary->parameters[0][$path];
            if (($observations !== null) && (Resource_States::compatible($state, $transition->required) !== Resource_States::EITHER)) {
                $this->fail($node, 'Temporary destruction does not satisfy its ownership contract');
            }
        }
    }

}
