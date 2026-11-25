<?php
declare(strict_types=1);

/*
 * Role: Emit supported lowered instructions.
 * Used by: Emission_Worker (private trait methods on this owner)
 * Call map:
 *   emit_instruction()
 *     -> emit_byte_literal() / emit_constant() / emit_binary() / emit_convert() [value kinds]
 *     -> emit_call() / emit_construct() / emit_destroy() [call and lifecycle kinds]
 *     -> emit_storage_begin() / emit_storage_end() [checked native element transitions]
 */

namespace emit_llvm;

use prepare_backend\LLVM_Types;
use lower\instruction_kind;
use lower\lowered_instruction;

// Private handlers over completed lowering plans; composed by Emission_Worker.
trait Instruction_Emission
{
    /** Reject duplicate value definitions and dispatch each prepared instruction to its emission handler. */
    private function emit_instruction(lowered_instruction $instruction): void
    {
        $id = $instruction->result_value_id;
        if (($id !== 0) && (isset($this->operands[$id]) || (!isset($this->body->values[$id - 1])))) {
            throw new \LogicException('Invalid or repeated LLVM value definition');
        }
        match ($instruction->kind)
        {
            instruction_kind::byte_literal => $this->emit_byte_literal($instruction),
            instruction_kind::constant => $this->emit_constant($instruction),
            instruction_kind::binary => $this->emit_binary($instruction),
            instruction_kind::convert => $this->emit_convert($instruction),
            instruction_kind::parameter => $this->emit_parameter($instruction),
            instruction_kind::storage_begin => $this->emit_storage_begin($instruction->payload),
            instruction_kind::storage_end => $this->emit_storage_end($instruction->payload),
            instruction_kind::call => $this->emit_call($instruction),
            instruction_kind::default_construct => $this->emit_default_construct($instruction),
            instruction_kind::copy_construct, instruction_kind::move_construct => $this->emit_construct($instruction),
            instruction_kind::copy_assign => $this->emit_copy_assign($instruction),
            instruction_kind::destroy => $this->emit_destroy($instruction),
            instruction_kind::borrow => $this->emit_borrow($instruction),
            instruction_kind::address => $this->emit_address($instruction->payload),
            instruction_kind::load => $this->emit_load($instruction),
            instruction_kind::store => $this->emit_store($instruction),
        };
    }

    /** Allocate immutable module bytes under an exact callable/value identity and retain their pointer/length operands. */
    private function emit_byte_literal(lowered_instruction $instruction): void
    {
        $id = $instruction->result_value_id;
        if ($this->body->definition_for($this->body->values[$id - 1]->type_id)->representation->kind
            !== \type_model\representation_kind::byte_span) {
            throw new \LogicException('Byte literal requires a semantic span');
        }
        $bytes = $instruction->payload->bytes;
        $length = strlen($bytes);
        $name = '@scpp_bytes_' . $this->body->binding->callable_id . '_' . $id;
        $this->constants .= $name . ' = private unnamed_addr constant [' . $length . ' x i8] c'
            . LLVM_Types::quote($bytes) . ", align 1\n";
        $this->operands[$id] = [$name, $length];
    }

    // Constants are inline operands; they introduce no SSA instruction.
    private function emit_constant(lowered_instruction $instruction): void
    {
        $id = $instruction->result_value_id;
        $this->operands[$id] = $instruction->payload;
    }

    /** Emit a prepared binary primitive; comparisons produce i1 from independently typed integer inputs. */
    private function emit_binary(lowered_instruction $instruction): void
    {
        $id = $instruction->result_value_id;
        $body = $this->body;
        $left = $instruction->payload->left;
        $right = $instruction->payload->right;
        $type_id = $body->values[$left - 1]->type_id;
        $definition = $body->definition_for($type_id);
        $result_id = $body->values[$id - 1]->type_id;
        $result = $body->definition_for($result_id);
        $operation = $instruction->payload->operation;
        $arithmetic = $operation === \lower\binary_operation::add_wrap;
        if (($definition->representation->kind !== \type_model\representation_kind::integer)
            || (($body->values[$right - 1]->type_id ?? null) !== $type_id)
            || (($arithmetic) && ($result_id !== $type_id))
            || ((!$arithmetic) && (($result->representation->kind !== \type_model\representation_kind::integer)
                || ($result->representation->payload->bit_width !== 1)))) {
            throw new \LogicException('LLVM binary operand/result representation mismatch');
        }
        $lhs = $this->operands[$left] ?? throw new \LogicException('Undefined LLVM binary operand');
        $rhs = $this->operands[$right] ?? throw new \LogicException('Undefined LLVM binary operand');
        $opcode = match ($operation) {
            \lower\binary_operation::add_wrap => 'add',
            \lower\binary_operation::less_signed => 'icmp slt',
            \lower\binary_operation::less_unsigned => 'icmp ult',
        };
        $type = LLVM_Types::scalar($definition->representation);
        $this->operands[$id] = '%v' . $id;
        $this->ir .= '  %v' . $id . ' = ' . $opcode . ' ' . $type . ' ' . $lhs . ', ' . $rhs . "\n";
    }

    /** Emit the prepared integer adaptation using already-defined input operands. */
    private function emit_convert(lowered_instruction $instruction): void
    {
        $id = $instruction->result_value_id;
        $body = $this->body;
        $conversion = $instruction->payload;
        $source_value = $body->values[$conversion->input_value_id - 1] ?? throw new \LogicException('Missing LLVM conversion input');
        $source = $body->definition_for($source_value->type_id)->representation;
        $destination = $body->definition_for($body->values[$id - 1]->type_id)->representation;
        if (($source->kind !== \type_model\representation_kind::integer) || ($destination->kind !== \type_model\representation_kind::integer)) {
            throw new \LogicException('LLVM integer conversion requires integer values');
        }
        $operand = $this->operands[$conversion->input_value_id] ?? throw new \LogicException('Undefined LLVM conversion operand');
        $this->ir .= '  %v' . $id . ' = ' . LLVM_Types::integer_conversion($conversion->operation,
            $source->payload->bit_width, $destination->payload->bit_width, $operand) . "\n";
        $this->operands[$id] = '%v' . $id;
    }

    /** Bind each incoming parameter in ABI order to its prepared lowered value. */
    private function emit_parameter(lowered_instruction $instruction): void
    {
        $id = $instruction->result_value_id;
        $body = $this->body;
        $binding = $this->body->binding;
        $position = $instruction->payload;
        $parameter = $binding->parameters[$position - 1] ?? null;
        if (($position !== $this->next_parameter++) || ($parameter === null) || ($body->values[$id - 1]->source_value_id !== 0)
            || ($body->values[$id - 1]->type_id !== $parameter->type_id)) {
            throw new \LogicException('Invalid LLVM incoming parameter');
        }
        $slot_id = $body->values[$id - 1]->storage_slot_id;
        if ($parameter->passing->is_borrow()
            ? (($slot_id === 0) || ($body->slot_for($slot_id)->incoming_parameter !== $position))
            : ($slot_id !== 0)) {
            throw new \LogicException('Incoming parameter storage differs from its passing contract');
        }
        $this->operands[$id] = '%p' . ($position + (int)($binding->result_passing === \type_model\result_passing::caller_storage));
    }

    /** Associate an object or scalar borrow with its prepared storage address without a load. */
    private function emit_borrow(lowered_instruction $instruction): void
    {
        $id = $instruction->result_value_id;
        $slot = $this->body->slot_for($instruction->payload->slot_id);
        $value = $this->body->values[$id - 1];
        if ($value->storage_slot_id !== $instruction->payload->slot_id) {
            throw new \LogicException('Borrow requires the prepared addressed storage');
        }
        if ($instruction->payload->projections === []) {
            if ($slot->type_id !== $value->type_id) {
                throw new \LogicException('Borrowed root changed type');
            }
            $this->operands[$id] = $this->slot_operand($instruction->payload->slot_id);
        }
        else {
            [$type, $address] = $this->addresses[$instruction->payload->address_id];
            if ($type !== $value->type_id) {
                throw new \LogicException('Borrowed projection changed type');
            }
            $this->operands[$id] = $address;
        }
    }

    /** Read through a prepared root/field address; values retain their semantic types. */
    private function emit_load(lowered_instruction $instruction): void
    {
        $id = $instruction->result_value_id;
        [$type, $address] = $this->storage_operand($instruction->payload, $id);
        $this->operands[$id] = '%v' . $id;
        $this->ir .= '  %v' . $id . ' = load ' . $type . ', ' . $this->pointer . ' ' . $address . "\n";
    }

    /** Store through the same prepared location path used by reads. */
    private function emit_store(lowered_instruction $instruction): void
    {
        $write = $instruction->payload;
        $slot = $this->body->slot_for($write->address->slot_id);
        if (($slot->incoming_parameter !== 0)
            && ($this->body->binding->parameters[$slot->incoming_parameter - 1]->passing === \type_model\argument_passing::borrow_const)) {
            throw new \LogicException('LLVM store cannot write through a const parameter');
        }
        [$type, $address] = $this->storage_operand($write->address, $write->value_id);
        $operand = $this->operands[$write->value_id] ?? throw new \LogicException('Undefined LLVM store operand');
        $this->ir .= '  store ' . $type . ' ' . $operand . ', ' . $this->pointer . ' ' . $address . "\n";
    }
}
