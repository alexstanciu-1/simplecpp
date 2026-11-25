<?php
declare(strict_types=1);

/*
 * Role: Completed lowering plan with exact inputs and local queries.
 * Used by: Lowering_Worker; Lowering_Join; LLVM_Emitter
 * Flow: fixed analysis/backend -> Lowered_Body -> function IR
 */

namespace lower;

use prepare_backend\Backend_Context;
use prepare_backend\callable_binding;

/**
 * @compiler-api Read-only callable plan produced by Lowerer; consumed by emission.
 * Readable data: binding, values, slots, arguments, instructions, blocks and entry_block_id.
 * Value/slot/block IDs are separate one-based domains within this exact result;
 * instruction ranges are zero-based start/count. Never mix IDs across results.
 * Plans carry control flow, storage slots and prepared value/address calls; supported source references borrow records.
 * Shared rows/contracts must not be mutated. Object identity participates in
 * emission reuse; old plans remain valid only with their own retained inputs.
 * Construction alone does not prove validity: take plans from the process API.
 */
class Lowered_Body
{
    /**
     * @compiler-internal Result assembly by lowering, not a consumer factory.
     * @param list<lowered_value> $values
     * @param list<lowered_instruction> $instructions
     * @param list<basic_block> $blocks
     * @param list<storage_slot> $slots */
    public function __construct(

        /**
         * @compiler-internal Retained lowering provenance and reuse dependencies.
         * Consumers use backend_context() and resolved-contract accessors.
         */
        public readonly lowering_input $input,
        public readonly callable_binding $binding,
        public readonly array $values,
        public readonly array $instructions,
        public readonly array $blocks,
        public readonly int $entry_block_id,
        public readonly array $slots,

        /** @var list<int> Lowered value IDs, selected by each call operand range. */
        public readonly array $arguments = [],
    )
    {
    }

    /** @compiler-api Source-file identity for backend partitioning; no AST/storage traversal by consumers. */
    public function source_file_id(): int
    {
        return $this->input->analysis->body->owner->frontend->source_file_id;
    }

    /** @compiler-api Exact shared backend context for coherence/reuse checks; no analysis layout exposed. */
    public function backend_context(): Backend_Context
    {
        return $this->input->backend;
    }

    /**
     * @compiler-api Shared type meaning for a type ID used by this plan.
     * Read the returned definition; do not inspect retained dependency-map layout.
     * @throws \LogicException The type is absent or has no resolved definition.
     */
    public function definition_for(int $type_id): \type_model\named_type_definition
    {
        return $this->input->analysis->body->definition_for($type_id);
    }

    /**
     * @compiler-api Return the existing slot for this plan's one-based slot ID.
     * @throws \LogicException Missing slot; zero never identifies storage.
     */
    public function slot_for(int $slot_id): storage_slot
    {
        return $this->slots[$slot_id - 1] ?? throw new \LogicException('Missing lowered local slot');
    }

    /** @compiler-api On-demand debug view; not a semantic input or persistence format. */
    public function to_array(): array
    {
        $instructions = [];
        foreach ($this->instructions as $instruction)
        {
            $payload = $instruction->payload;
            // Export references to shared targets, never repeat their full contracts per instruction.
            $data = match (true)
            {
                $payload instanceof call_operands => ['callable_id' => $payload->target->callable_id,
                    'link_name' => $payload->target->link_name, 'calling_convention' => $payload->target->calling_convention,
                    'argument_start' => $payload->argument_start, 'argument_count' => $payload->argument_count,
                    'destination_slot_id' => $payload->destination_slot_id],
                $payload instanceof storage_transition => ['callable_id' => $payload->call->target->callable_id,
                    'argument_start' => $payload->call->argument_start, 'argument_count' => $payload->call->argument_count,
                    'destination' => $payload->destination],
                $payload instanceof destruction_operands => ['destination' => $payload->destination,
                    'link_name' => $payload->target->link_name],
                $payload instanceof copy_assignment_operands => ['destination' => $payload->destination,
                    'source_value_id' => $payload->source_value_id, 'link_name' => $payload->target->link_name],
                $payload instanceof construction_operands => ['destination' => $payload->destination,
                    'source_value_id' => $payload->source_value_id, 'link_name' => $payload->target?->link_name],
                $payload instanceof \prepare_backend\abi_target => ['link_name' => $payload->link_name],
                default => $payload,
            };
            $instructions[] = ['kind' => $instruction->kind->value, 'source_node_id' => $instruction->source_node_id,
                'result_value_id' => $instruction->result_value_id, 'payload' => $data];
        }
        return ['callable_id' => $this->binding->callable_id, 'link_name' => $this->binding->link_name,
            'return_type_id' => $this->binding->signature->return_type, 'entry_block_id' => $this->entry_block_id,
            'arguments' => $this->arguments, 'values' => $this->values, 'slots' => $this->slots, 'instructions' => $instructions, 'blocks' => $this->blocks];
    }
}
