<?php
declare(strict_types=1);

/*
 * Role: Prepare storage and lower local effects and ordered cleanup obligations.
 * Used by: Lowering_Worker (private trait methods on this owner)
 * Call map:
 *   prepare_storage_slots(); write_local(); end_locals(); emit_cleanups()
 *     -> [action] create slots, emit stores and consume local ends
 *   construct_from() / Storage_Lowering -> construct_at() -> lifecycle_target()
 */

namespace lower;

use prepare_backend\callable_binding;

use check_bodies\statement_kind;

// Private methods composed by the per-callable worker.
trait Local_Lowering
{
    /** Assign one static slot per reached binding and map direct constructor results to their local destinations. */
    private function prepare_storage_slots(): void
    {
        $analysis = $this->input->analysis;
        $body = $analysis->body;

        // The incoming result belongs to the caller; it has no callee cleanup obligation.
        $signature = $body->signature_for($body->callable_id);
        if ($signature->result === \type_model\result_production::owned)
        {
            $this->slots[] = new storage_slot(0, $signature->return_type, incoming_result: true);
            $this->result_slot = count($this->slots);
            foreach ($body->statements as $statement) {
                if ($statement->return === \check_bodies\return_kind::direct_construct) {
                    $this->construction_destinations[$statement->value_id] = $this->result_slot;
                }
            }
        }

        // Allocate one static slot per reached binding. Repeated loop execution
        // initializes the same slot again; semantic liveness belongs to analysis.
        $locals = [];
        foreach ($analysis->local_lifetimes as $local) {
            $locals[$local->local_id] ??= $local;
        }
        uasort($locals, static fn($a, $b) => [$a->initialized_statement_id, $a->local_id] <=> [$b->initialized_statement_id, $b->local_id]);

        // Constructor calls write directly into these slots when initializing a local.
        foreach ($locals as $local) {
            $this->start_local($local->local_id, $body->local_type_for($local->local_id));
            $statement = $body->statements[$local->initialized_statement_id - 1] ?? null;
            if ($statement?->write === \check_bodies\local_write_kind::direct_construct) {
                $this->construction_destinations[$statement->value_id] = $this->slot_ids[$local->local_id];
            }
        }
    }

    /** Assign a local storage identity, with incoming references backed by caller storage. */
    private function start_local(int $local_id, int $type_id): void
    {
        if (isset($this->slot_ids[$local_id])) {
            throw new \LogicException('Local slot initialized twice');
        }
        $borrow = $this->input->analysis->body->local_passing($local_id)->is_borrow();
        $this->slots[] = new storage_slot($local_id, $type_id, 0, $borrow ? $local_id : 0);
        $this->slot_ids[$local_id] = count($this->slots);
    }

    /** Bind parameters at entry: copy scalar values, retain incoming addresses for record borrows. */
    private function initialize_parameters(callable_binding $binding): void
    {
        $analysis = $this->input->analysis;
        $body = $analysis->body;
        if ($body->entry_parameter_count() !== count($binding->parameters)) {
            throw new \LogicException('Incoming parameter count differs from its backend contract');
        }
        foreach ($binding->parameters as $index => $parameter)
        {
            $local_id = $index + 1;
            $local = $body->names->parameter_for($local_id);
            $lifetime = $analysis->local_for($local_id);
            if (($lifetime === null) || ($lifetime->initialized_statement_id !== 0) || ($local->scope_id !== 1)
                || ($body->local_type_for($local_id) !== $parameter->type_id)) {
                throw new \LogicException('Incoming parameter requires its analyzed entry initialization');
            }

            $borrow = $parameter->passing->is_borrow();
            $this->values[] = new lowered_value(0, $parameter->type_id, $borrow ? $this->slot_ids[$local_id] : 0);
            $id = count($this->values);
            $this->instructions[] = new lowered_instruction(instruction_kind::parameter, $local->declaration_node_id, $id, $local_id);
            if (!$borrow) {
                $this->store_local($local_id, $id, $local->declaration_node_id);
            }
        }
    }

    /** Validate the analyzed initialization and select direct construction, independent copying or scalar storage. */
    private function write_local(\check_bodies\typed_statement $statement, int $statement_id, int $value_id, ?storage_address $address = null): void
    {
        $analysis = $this->input->analysis;
        $body = $analysis->body;
        $local_id = ($statement->target?->local_id ?? 0);
        if ($statement->kind === statement_kind::local_declaration)
        {
            $lifetime = $analysis->local_for($local_id);
            $local = $body->names->local_for($local_id);
            if (($lifetime === null) || ($lifetime->initialized_statement_id !== $statement_id)
                || ($local->declaration_node_id !== $statement->source_node_id)) {
                throw new \LogicException('Local declaration requires its analyzed initialization');
            }
        }

        // Direct construction has already initialized the local destination during expression lowering.
        if ($statement->write === \check_bodies\local_write_kind::direct_construct) {
            if (($this->values[$value_id - 1]->storage_slot_id !== $this->slot_ids[$local_id])
                || ($this->values[$value_id - 1]->type_id !== $body->local_type_for($local_id))) {
                throw new \LogicException('Local construction must target its own storage');
            }
            return;
        }

        if ($statement->write === \check_bodies\local_write_kind::copy_construct) {
            $this->construct_from($this->slot_ids[$local_id], $value_id, $statement->source_node_id, \type_model\lifecycle_operation_kind::copy_construct);
            return;
        }
        if ($statement->write === \check_bodies\local_write_kind::copy_assign) {
            $this->assign_local($address, $body->place_type($statement->target), $value_id, $statement->source_node_id);
            return;
        }
        $this->store_local($local_id, $value_id, $statement->source_node_id, $address);
    }

    /** Assign through the accepted operation; projected destinations keep their prepared address. */
    private function assign_local(storage_address $destination, int $type, int $value_id, int $node): void
    {
        $source = $this->values[$value_id - 1];
        $operation = $this->input->analysis->body->definition_for($type)->lifetime->copy_assignment;
        $target = $operation === null ? null : $this->input->backend->abi_for($operation->link_name);
        if (($target === null) || ($target->lifecycle_operation !== $operation)
            || ($source->type_id !== $type) || ($source->storage_slot_id === 0)) {
            throw new \LogicException('Copy assignment requires its operation and same-type source storage');
        }
        $this->instructions[] = new lowered_instruction(instruction_kind::copy_assign, $node, 0,
            new copy_assignment_operands($destination, $value_id, $target));
    }

    /** Construct a distinct destination through its prepared copy or expiring-source operation. */
    private function construct_from(int $slot_id, int $value_id, int $node_id, \type_model\lifecycle_operation_kind $kind): void
    {
        $slot = $this->slots[$slot_id - 1];
        $source = $this->values[$value_id - 1];
        if (($source->type_id !== $slot->type_id) || ($source->storage_slot_id === 0)
            || ($source->storage_slot_id === $slot_id)) {
            throw new \LogicException('Source construction requires distinct same-type storage');
        }

        $this->construct_at(new storage_address($slot_id), $slot->type_id, $value_id, $node_id, $kind);
    }

    /** Bind construction for any typed destination; a trivial copy needs no ABI implementation. */
    private function construct_at(storage_address $destination, int $type_id, int $value_id, int $node_id,
        \type_model\lifecycle_operation_kind $kind): void
    {
        $life = $this->input->analysis->body->definition_for($type_id)->lifetime;
        if ($this->values[$value_id - 1]->type_id !== $type_id) {
            throw new \LogicException('Construction requires matching source and destination types');
        }
        $trivial = ($kind === \type_model\lifecycle_operation_kind::copy_construct) && ($life->copy === \type_model\copy_kind::value);
        $target = $trivial ? null : $this->lifecycle_target($life->operation($kind));
        $this->instructions[] = new lowered_instruction($kind === \type_model\lifecycle_operation_kind::copy_construct
            ? instruction_kind::copy_construct : instruction_kind::move_construct, $node_id, 0,
            new construction_operands($destination, $value_id, $target));
    }

    /** Select a scalar load or object-address borrow from the checked local value kind. */
    private function load_local(\check_bodies\typed_value $value, int $id): void
    {
        $slot_id = $this->slot_ids[$value->payload->local_id] ?? throw new \LogicException('Local read requires an analyzed slot');
        if (($value->payload->projections === []) && ($this->slots[$slot_id - 1]->type_id !== $value->type_id)) {
            throw new \LogicException('Local load type differs from its slot');
        }
        $kind = $value->kind === \check_bodies\value_kind::local_borrow ? instruction_kind::borrow : instruction_kind::load;
        $this->instructions[] = new lowered_instruction($kind, $value->source_node_id, $id, $this->prepare_address($value->payload, $value->source_node_id));
    }

    /** Consume analyzed scope or return exits at this boundary; destruction is emitted from separate obligations. */
    private function end_locals(int $boundary, bool $returning, int $block_id): void
    {
        $lifetimes = $this->input->analysis->local_lifetimes;
        while (($this->next_local_end < count($lifetimes)) && ($lifetimes[$this->next_local_end]->end_after_statement === $boundary)
            && ($lifetimes[$this->next_local_end]->block_id === $block_id)) {
            $end = $lifetimes[$this->next_local_end++];
            $expected = $returning ? \analyze_lifetimes\local_end::return_exit : \analyze_lifetimes\local_end::scope_exit;
            if ((!isset($this->slot_ids[$end->local_id])) || ($end->end !== $expected)) {
                throw new \LogicException('Local lifetime exit requires a live slot and matching exit');
            }
        }
    }

    /**
     * Consume the lifetime stage's ordered obligations; never infer destruction from a borrow end.
     */
    private function emit_cleanups(int $boundary, int $block_id): void
    {
        $analysis = $this->input->analysis;
        $body = $analysis->body;
        while ($this->next_cleanup < count($analysis->cleanups))
        {
            $cleanup = $analysis->cleanups[$this->next_cleanup];
            if (($cleanup->block_id !== $block_id) || ($cleanup->after_statement !== $boundary)) {
                break;
            }

            // Resolve the analyzed subject to storage even when its expression access has ended.
            if ($cleanup->subject === \analyze_lifetimes\cleanup_subject::local) {
                $slot_id = $this->slot_ids[$cleanup->subject_id] ?? 0;
                $node_id = $body->names->local_for($cleanup->subject_id)->declaration_node_id;
            }
            else {
                $value = $this->values[($this->value_ids[$cleanup->subject_id] ?? 0) - 1] ?? null;
                $slot_id = $value?->storage_slot_id ?? 0;
                $node_id = $body->values[$cleanup->subject_id - 1]->source_node_id;
            }

            // Caller-owned storage is never part of this callee's cleanup obligations.
            $slot = $this->slots[$slot_id - 1] ?? throw new \LogicException('Cleanup requires constructed storage');
            if (($slot->incoming_parameter !== 0) || ($slot->incoming_result)) {
                throw new \LogicException('Caller-owned parameter or result storage cannot be destroyed');
            }
            if (($cleanup->subject === \analyze_lifetimes\cleanup_subject::local)
                ? ($slot->source_local_id !== $cleanup->subject_id)
                : (($slot->source_local_id !== 0) || ($slot->source_value_id !== $cleanup->subject_id))) {
                throw new \LogicException('Cleanup subject differs from its constructed storage');
            }

            // The slot's shared type contract selects the exact prepared destructor target.
            $operation = $body->definition_for($slot->type_id)->lifetime?->destructor
                ?? throw new \LogicException('Cleanup requires its type implementation');
            $target = $this->lifecycle_target($operation);
            $this->instructions[] = new lowered_instruction(instruction_kind::destroy, $node_id, 0,
                new destruction_operands(new storage_address($slot_id), $target));
            ++$this->next_cleanup;
        }
    }

    /** Store a checked value at its root or projected destination, preserving the selected storage identity. */
    private function store_local(int $local_id, int $value_id, int $node_id, ?storage_address $address = null): void
    {
        $slot_id = $this->slot_ids[$local_id] ?? throw new \LogicException('Local write requires an analyzed slot');
        if ((($address?->projections ?? []) === []) && ($this->slots[$slot_id - 1]->type_id !== ($this->values[$value_id - 1]->type_id ?? null))) {
            throw new \LogicException('Local store type differs from its resolved slot type');
        }
        $this->instructions[] = new lowered_instruction(instruction_kind::store, $node_id, 0, new store_operands($address ?? new storage_address($slot_id), $value_id));
    }

    /** Emit address preparation before its consuming load/store; each index is already evaluated exactly once. */
    private function prepare_address(\check_bodies\place $place, int $node): storage_address
    {
        $slot = $this->slot_ids[$place->local_id] ?? throw new \LogicException('Address requires analyzed initialization storage');
        $projections = [];
        foreach ($place->projections as $projection) {
            $operand = $projection->kind !== \check_bodies\projection_kind::field
                ? $this->value_ids[$projection->operand] : $projection->operand;
            $projections[] = new address_projection($projection->kind, $operand, $projection->type_id);
        }
        $address = new storage_address($slot, $projections, $projections === [] ? 0 : ++$this->next_address);
        if ($projections !== []) {
            $this->instructions[] = new lowered_instruction(instruction_kind::address, $node, 0, $address);
        }
        return $address;
    }

    /** Require the shared type operation's accepted ABI, independent of implementation origin. */
    private function lifecycle_target(\type_model\runtime_lifecycle_operation|\type_model\source_lifecycle_operation|null $operation): \prepare_backend\abi_target
    {
        $target = $operation === null ? null : $this->input->backend->abi_for($operation->link_name);
        if (($target === null) || ($target->lifecycle_operation !== $operation)) {
            throw new \LogicException('Missing or stale lifecycle target');
        }
        return $target;
    }
}
