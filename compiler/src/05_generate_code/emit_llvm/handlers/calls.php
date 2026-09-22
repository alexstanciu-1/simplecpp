<?php
declare(strict_types=1);

/*
 * Role: Emit one prepared call and record its references.
 * Used by: Emission_Worker (private trait methods on this owner)
 * Call map:
 *   emit_call() -> call_arguments() [shared with native storage transitions]
 *   emit_call() / emit_construct() / emit_destroy()
 *     -> emit_abi_call(); [action] format physical operands and record the shared target
 */

namespace emit_llvm;

use prepare_backend\LLVM_Types;
use lower\lowered_instruction;

// Private handlers over completed lowering plans; composed by Emission_Worker.
trait Call_Emission
{
    /** Validate semantic operands and destination storage, then emit the call through its shared physical ABI target. */
    private function emit_call(lowered_instruction $instruction): void
    {
        $id = $instruction->result_value_id;
        $body = $this->body;
        $call = $instruction->payload;
        $target = $call->target;

        // Caller-storage results contribute a hidden address before ordinary semantic arguments.
        $constructs = $target->result_passing === \type_model\result_passing::caller_storage;
        $arguments = [];
        if ($constructs)
        {
            $slot = $body->slot_for($call->destination_slot_id);
            if (($id === 0) || ($slot->type_id !== $target->signature->return_type)
                || ($body->values[$id - 1]->storage_slot_id !== $call->destination_slot_id)) {
                throw new \LogicException('Constructor requires its prepared result storage');
            }
            $arguments[] = $this->slot_operand($call->destination_slot_id);
        }
        elseif ($call->destination_slot_id !== 0) {
            throw new \LogicException('Direct call cannot have destination storage');
        }

        array_push($arguments, ...$this->call_arguments($call));

        if ($target->storage !== null) {
            $this->emit_storage_call($instruction, $arguments);
            return;
        }

        // Physical void construction still produces a semantic value backed by destination storage.
        $call_return = LLVM_Types::return_type($target);
        if ((($id === 0) !== ((!$constructs) && ($call_return === 'void'))) || (($id !== 0) && ($body->values[$id - 1]->type_id !== $target->signature->return_type))) {
            throw new \LogicException('LLVM call result differs from its prepared contract');
        }
        $prefix = (($id === 0) || ($constructs)) ? '' : '%v' . $id . ' = ';
        $this->emit_abi_call($target->abi, $arguments, $prefix);
        if ($id !== 0) {
            $this->operands[$id] = $constructs ? $this->slot_operand($call->destination_slot_id) : '%v' . $id;
        }
    }

    /** Consume each semantic operand exactly once against the fixed call contract, including storage transitions. */
    private function call_arguments(\lower\call_operands $call): array
    {
        $body = $this->body;
        $target = $call->target;
        if ($body->backend_context()->binding_for($target->callable_id) !== $target) {
            throw new \LogicException('Stale LLVM call target');
        }
        if (($call->argument_start !== $this->next_argument) || ($call->argument_count > (count($body->arguments) - $this->next_argument))) {
            throw new \LogicException('Invalid LLVM call operand range');
        }

        // Consume the call's contiguous operand range against its prepared parameter types.
        $arguments = [];
        foreach ($target->parameters as $parameter)
        {
            $argument_id = $body->arguments[$this->next_argument++];
            $value = $body->values[$argument_id - 1] ?? null;
            if (($value === null) || ($value->type_id !== $parameter->type_id)) {
                throw new \LogicException('LLVM argument type differs from its prepared parameter');
            }
            $operand = $this->operands[$argument_id] ?? throw new \LogicException('Undefined LLVM argument operand');
            if ($parameter->span !== null)
            {
                if (!is_array($operand) || ($value->storage_slot_id !== 0)
                    || (strlen(decbin($operand[1])) > $parameter->span->length->bits)) {
                    throw new \LogicException('Byte span exceeds or disagrees with its prepared ABI');
                }
                array_push($arguments, $operand[0], (string)$operand[1]);
                continue;
            }
            if (!is_string($operand)) {
                throw new \LogicException('Scalar or object call argument requires one operand');
            }
            if ($parameter->passing->is_borrow() !== ($value->storage_slot_id !== 0)) {
                throw new \LogicException('Call operand does not match its prepared value/address passing');
            }
            $arguments[] = $operand;
        }

        return $arguments;
    }

    /** Construct the checked type directly into its owned local or temporary destination. */
    private function emit_default_construct(lowered_instruction $instruction): void
    {
        $value = $this->body->values[$instruction->result_value_id - 1];
        $slot = $this->body->slot_for($value->storage_slot_id);
        $operation = $this->body->definition_for($value->type_id)->lifetime->default_constructor;
        if (($slot->type_id !== $value->type_id) || ($slot->incoming_parameter !== 0)
            || ($operation === null) || ($instruction->payload->lifecycle_operation !== $operation)) {
            throw new \LogicException('Default construction disagrees with its storage contract');
        }
        $address = $this->slot_operand($value->storage_slot_id);
        $this->emit_abi_call($instruction->payload, [$address], '');
        $this->operands[$instruction->result_value_id] = $address;
    }

    /** Construct a same-type destination using its selected ABI, or ordinary bytes only for trivial copying. */
    private function emit_construct(lowered_instruction $instruction): void
    {
        $payload = $instruction->payload;
        [$type, $destination] = $this->address_operand($payload->destination);
        $source = $this->body->values[$payload->source_value_id - 1] ?? null;
        $kind = $instruction->kind === \lower\instruction_kind::copy_construct
            ? \type_model\lifecycle_operation_kind::copy_construct : \type_model\lifecycle_operation_kind::move_construct;
        $life = $this->body->definition_for($type)->lifetime;
        $operand = $this->operands[$payload->source_value_id] ?? throw new \LogicException('Undefined construction source');
        if ($source?->type_id !== $type) {
            throw new \LogicException('Construction source and destination types differ');
        }

        // Trivial copies share the construction instruction but need no lifecycle ABI.
        if ($payload->target === null)
        {
            if (($kind !== \type_model\lifecycle_operation_kind::copy_construct) || ($life->copy !== \type_model\copy_kind::value)) {
                throw new \LogicException('Construction requires its selected lifecycle target');
            }
            $llvm_type = $this->storage_type($type);
            if ($source->storage_slot_id !== 0) {
                $loaded = '%copy' . ++$this->next_address;
                $this->ir .= '  ' . $loaded . ' = load ' . $llvm_type . ', ptr ' . $operand . "\n";
                $operand = $loaded;
            }
            $this->ir .= '  store ' . $llvm_type . ' ' . $operand . ', ptr ' . $destination . "\n";
            return;
        }
        $operation = $life->operation($kind);
        if (($operation === null) || ($payload->target->lifecycle_operation !== $operation)
            || ($source->storage_slot_id === 0) || ($operand === $destination)) {
            throw new \LogicException('Source construction does not match its storage contract');
        }
        $this->emit_abi_call($payload->target, [$destination, $operand], '');
    }

    /** Preserve object lifetime and dispatch an explicit assignment operation on same-type addresses. */
    private function emit_copy_assign(lowered_instruction $instruction): void
    {
        $payload = $instruction->payload;
        $location = $payload->destination;
        [$type, $destination] = $this->address_operand($location);
        $source = $this->body->values[$payload->source_value_id - 1];
        $operation = $this->body->definition_for($type)->lifetime->copy_assignment;
        if (($operation === null) || ($payload->target->lifecycle_operation !== $operation)
            || ($source->type_id !== $type) || ($source->storage_slot_id === 0)) {
            throw new \LogicException('Assignment does not match its storage contract');
        }
        $this->emit_abi_call($payload->target, [$destination, $this->operands[$payload->source_value_id]], '');
    }

    /** Verify the destination owns this destructor and pass its address through the common ABI call emitter. */
    private function emit_destroy(lowered_instruction $instruction): void
    {
        $payload = $instruction->payload;
        [$type, $destination] = $this->address_operand($payload->destination);
        $operation = $this->body->definition_for($type)->lifetime?->destructor;
        if (($operation === null) || ($payload->target->lifecycle_operation !== $operation)) {
            throw new \LogicException('Destruction does not match its storage contract');
        }
        $this->emit_abi_call($payload->target, [$destination], '');
    }

    /** Format every physical call through the same fixed target contract. */
    private function emit_abi_call(\prepare_backend\abi_target $target, array $operands, string $prefix): void
    {
        if (($this->body->backend_context()->abi_for($target->link_name) !== $target)
            || (count($operands) !== count($target->parameters))) {
            throw new \LogicException('Stale ABI target or mismatched physical arguments');
        }

        // Calls and module imports retain the same exact target and physical parameter spellings.
        $arguments = [];
        foreach ($target->parameters as $index => $parameter) {
            $arguments[] = $parameter->type . LLVM_Types::attribute($parameter->extension) . ' ' . $operands[$index];
        }
        $this->references[$target->link_name] = $target;
        $this->ir .= '  ' . $prefix . 'call ' . $target->calling_convention . LLVM_Types::attribute($target->return_extension) . ' ' . $target->return_type
            . ' @' . LLVM_Types::quote($target->link_name) . '(' . implode(', ', $arguments) . ")\n";
    }
}
