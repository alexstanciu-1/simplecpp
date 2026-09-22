<?php
declare(strict_types=1);

/*
 * Role: Completed typed body and dependency/operation queries.
 * Used by: Body_Worker; body join; lifetime analysis and lowering
 * Flow: typed rows + dependencies -> Checked_Body -> consumers
 */

namespace check_bodies;

/**
 * @compiler-api Typed callable data consumed by lifetime analysis and lowering.
 * Readable fields: owner, names, values, calls, statements, falls_through, local_types,
 * scopes, arguments. Read type/signature meaning through definition_for/signature_for; dependency
 * maps are checking-internal reuse bookkeeping, not cross-process storage contracts.
 * Value and call IDs are separate one-based domains; argument/call/scope ranges use
 * zero-based start/count. Local/scope IDs belong to names; source IDs to owner.frontend.
 * Statements include checked unreachable syntax; analysis selects the reachable
 * blocks. Void calls have no typed_value row. All nested rows/inputs are shared
 * read-only after handoff; construction alone does not establish checked validity.
 */
class Checked_Body
{
    public readonly int $callable_id;
    /**
     * @compiler-internal Producer-only construction; readiness follows the owning process contract.
     * @param list<typed_value> $values
     * @param list<typed_call> $calls
     * @param list<typed_statement> $statements
     * @param array<int, \type_model\type_record> $type_dependencies
     * @param array<int, signature_dependency> $signature_dependencies
     * @param list<typed_scope> $scopes
     * @param list<typed_argument> $arguments
     */
    public function __construct(
        public readonly \collect_symbols\symbol_record $owner,
        public readonly \resolve_symbols\Symbol_Resolution $names,
        public readonly array $values,
        public readonly array $calls,
        public readonly array $statements,
        public readonly bool $falls_through,

        /** @compiler-internal Checking reuse bookkeeping; consumers use resolved-contract accessors. */
        public readonly array $type_dependencies,

        /** @compiler-internal Checking reuse bookkeeping; consumers use resolved-contract accessors. */
        public readonly array $signature_dependencies,
        public readonly ?\resolve_types\Local_Types $local_types,
        public readonly array $scopes,
        public readonly array $arguments = [],
        public readonly array $blocks = [],
        public readonly ?\instantiate\instance_context $instance = null,
    )
    {
        $this->callable_id = $instance?->context_id ?? $owner->symbol_id;
    }

    /**
     * @compiler-api Parameters are initialized incoming bindings at callable entry.
     * They occupy local IDs 1..count in declaration order and the root scope. No
     * fabricated initialization statements; lifetime/passing rules are a later stage.
     */
    public function entry_parameter_count(): int
    {
        return $this->names->parameter_count;
    }

    /** @compiler-api Borrowed entry bindings never acquire ownership; ordinary locals retain value semantics. */
    public function local_passing(int $local_id): \type_model\argument_passing
    {
        $this->names->local_for($local_id);
        return $this->signature_for($this->callable_id)->parameter_passing[$local_id - 1] ?? \type_model\argument_passing::value;
    }

    /** @compiler-api Read a checked argument by one-based position in a call from this body. */
    public function argument_for(int $call_id, int $position): typed_argument
    {
        $call = $this->calls[$call_id - 1] ?? throw new \OutOfBoundsException('Missing checked call');
        if (($position < 1) || ($position > $call->argument_count)) {
            throw new \OutOfBoundsException('Missing checked argument');
        }
        return $this->arguments[$call->argument_start + $position - 1];
    }

    /** @compiler-api Existing unary conversion and its earlier input value; never copies/retypes its operand. */
    public function conversion_for(int $value_id): conversion_value
    {
        $value = $this->values[$value_id - 1] ?? null;
        if (($value === null) || ($value->kind !== value_kind::conversion) || (!($value->payload instanceof conversion_value))
            || ($value->payload->input_value_id >= $value_id)) {
            throw new \LogicException('Invalid or cyclic checked conversion');
        }
        return $value->payload;
    }

    /** @compiler-api Read a completed operation and validate its earlier, exactly typed operands. */
    public function operation_for(int $value_id): operation_value
    {
        $value = $this->values[$value_id - 1] ?? null;
        $operation = $value?->payload;
        if (($value?->kind !== value_kind::operation) || !($operation instanceof operation_value)
            || (count($operation->contract->operand_types) !== 2)
            || ($value->type_id !== $operation->contract->result_type)) {
            throw new \LogicException('Invalid checked operation');
        }
        foreach ([$operation->left, $operation->right] as $index => $id) {
            if (($id <= 0) || ($id >= $value_id)
                || (($this->values[$id - 1]->type_id ?? null) !== $operation->contract->operand_types[$index])) {
                throw new \LogicException('Invalid or cyclic checked operation operand');
            }
        }
        return $operation;
    }

    /** @compiler-api Read the canonical type ID for a local in this body's name owner; throws when absent. */
    public function local_type_for(int $local_id): int
    {
        return ($this->local_types ?? throw new \OutOfBoundsException('Body has no local types'))->type_for($local_id);
    }

    /** @compiler-api Read the checked statement range for a one-based name scope ID; throws when absent. */
    public function scope_for(int $scope_id): typed_scope
    {
        return $this->scopes[$scope_id - 1] ?? throw new \OutOfBoundsException('Missing checked scope: ' . $scope_id);
    }

    /** @compiler-api Read the shared signature contract for this owner or a used target; throws when missing/malformed. */
    public function signature_for(int $callable_id): \type_model\signature_representation
    {
        $dependency = $this->signature_dependencies[$callable_id]
            ?? throw new \OutOfBoundsException('No resolved signature in this body for symbol: ' . $callable_id);
        if ($dependency->representation->kind !== \type_model\representation_kind::function_signature) {
            throw new \LogicException('Expected a resolved callable signature');
        }
        return $dependency->representation->payload;
    }

    /** @compiler-api Resource effects come from the exact callable dependency checked with this body. */
    public function allocation_effect_for(int $callable_id): ?\type_model\allocation_effect
    {
        $dependency = $this->signature_dependencies[$callable_id]
            ?? throw new \OutOfBoundsException('Missing checked callable dependency');
        return $dependency->storage?->allocation_effect ?? $dependency->external?->signature->allocation_effect;
    }

    /** @compiler-api Read the shared type meaning used by this body; throws when missing/unresolved. */
    public function definition_for(int $type_id): \type_model\named_type_definition
    {
        $record = $this->type_dependencies[$type_id]
            ?? throw new \OutOfBoundsException('No resolved type in this body for type: ' . $type_id);
        return $record->definition ?? throw new \LogicException('Missing checked value type definition');
    }

    /** Validate a projected path against retained type contracts and return its final storage type. */
    public function place_type(place $location): int
    {
        $type = $this->local_type_for($location->local_id);
        foreach ($location->projections as $projection)
        {
            $shape = $this->definition_for($type)->representation;
            if ($projection->kind === projection_kind::field) {
                if (($shape->kind !== \type_model\representation_kind::structure)
                    || ($projection->operand < 0) || ($projection->operand >= $shape->payload->count)) {
                    throw new \LogicException('Invalid field lifetime projection');
                }
            }
            elseif ($projection->kind === projection_kind::element)
            {
                $index = $this->values[$projection->operand - 1] ?? null;
                if (($this->definition_for($type)->element_storage?->element_type !== $projection->type_id)
                    || ($index === null)
                    || ($this->definition_for($index->type_id)->representation->kind !== \type_model\representation_kind::integer)) {
                    throw new \LogicException('Invalid dynamic element projection');
                }
            }
            else
            {
                $index = $this->values[$projection->operand - 1] ?? null;
                if (($shape->kind !== \type_model\representation_kind::fixed_array)
                    || ($shape->payload->element_type !== $projection->type_id) || ($index === null)
                    || ($this->definition_for($index->type_id)->representation->kind !== \type_model\representation_kind::integer)) {
                    throw new \LogicException('Invalid index lifetime projection');
                }
            }
            $type = $projection->type_id;
            $this->definition_for($type);
        }
        return $type;
    }

    /** @compiler-api On-demand debug view; not a semantic input or a persisted-cache format. */
    public function to_array(): array
    {
        $signatures = [];
        foreach ($this->signature_dependencies as $dependency) {
            $signatures[] = ['callable_id' => $dependency->callable_id, 'representation_id' => $dependency->representation_id];
        }
        return ['symbol_id' => $this->owner->symbol_id, 'callable_id' => $this->callable_id, 'source_file_id' => $this->owner->frontend->source_file_id,
            'body_node_id' => $this->owner->body_node_id, 'values' => $this->values, 'calls' => $this->calls, 'statements' => $this->statements,
            'blocks' => $this->blocks, 'scopes' => $this->scopes, 'entry_parameter_count' => $this->entry_parameter_count(), 'arguments' => $this->arguments,
            'falls_through' => $this->falls_through, 'type_dependencies' => array_keys($this->type_dependencies),
            'signature_dependencies' => $signatures];
    }
}
