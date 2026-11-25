<?php
declare(strict_types=1);

/*
 * Role: Emit prepared storage paths and fail-stop array bounds guards.
 * Used by: Emission_Worker (private methods composed on this owner)
 * Call map: emit_address() -> checked_index(); storage_operand() -> prepared pointer
 *   address_operand() -> [action] resolve a local/projected/internal lifecycle destination
 * Layout supplies offsets/stride; source index signedness stays in its type contract.
 */
namespace emit_llvm;

use prepare_backend\LLVM_Types;

trait Location_Emission
{
    /** Require identical scalar slot/value types before emitting a load or store; opaque bytes cannot be copied. */
    private function slot_value_type(int $slot_id, int $value_id): string
    {
        $body = $this->body;
        $slot = $body->slot_for($slot_id);
        $value = $body->values[$value_id - 1] ?? throw new \LogicException('Missing LLVM storage value');
        if ($slot->type_id !== $value->type_id) {
            throw new \LogicException('LLVM slot and value types differ');
        }
        if (($value->storage_slot_id !== 0)
            || ($body->definition_for($slot->type_id)->representation->kind === \type_model\representation_kind::opaque_inline)) {
            throw new \LogicException('Direct load/store cannot copy an opaque object');
        }
        return $this->storage_type($slot->type_id);
    }
    /** Read source/provider-neutral prepared storage spelling for this type. */
    private function storage_type(int $type_id): string
    {
        $shape = $this->body->definition_for($type_id)->representation;
        if ($shape->kind === \type_model\representation_kind::structure) {
            return ($this->body->backend_context()->layouts[$type_id]
                ?? throw new \LogicException('Missing record layout'))->llvm_type;
        }
        if ($shape->kind === \type_model\representation_kind::fixed_array) {
            return '[' . $shape->payload->count . ' x ' . $this->storage_type($shape->payload->element_type) . ']';
        }
        return LLVM_Types::storage($shape);
    }

    /** Map every storage access to owned allocation or the caller's incoming address. */
    private function slot_operand(int $slot_id): string
    {
        $slot = $this->body->slot_for($slot_id);
        if ($slot->incoming_result) {
            if (($this->body->binding->result_passing !== \type_model\result_passing::caller_storage)
                || ($slot->type_id !== $this->body->binding->signature->return_type)) {
                throw new \LogicException('Incoming result differs from the prepared signature');
            }
            return '%p1';
        }
        if ($slot->incoming_parameter === 0) {
            return '%s' . $slot_id;
        }
        $parameter = $this->body->binding->parameters[$slot->incoming_parameter - 1] ?? null;
        if (($parameter === null) || !$parameter->passing->is_borrow() || ($parameter->type_id !== $slot->type_id)
            || ($slot->source_local_id !== $slot->incoming_parameter) || ($slot->source_value_id !== 0)) {
            throw new \LogicException('Borrowed slot requires its matching incoming parameter');
        }
        return '%p' . ($slot->incoming_parameter + (int)($this->body->binding->result_passing === \type_model\result_passing::caller_storage));
    }

    /** Resolve the common lifecycle destination: local, projected, or checked native element address. */
    private function address_operand(\lower\storage_address $location): array
    {
        return $location->address_id === 0
            ? [$this->body->slot_for($location->slot_id)->type_id, $this->slot_operand($location->slot_id)]
            : ($this->addresses[$location->address_id] ?? throw new \LogicException('Address used before preparation'));
    }

    /** Resolve a root slot or field projection against the accepted target layout. */
    private function storage_operand(\lower\storage_address $location, int $value_id): array
    {
        if ($location->projections === []) {
            return [$this->slot_value_type($location->slot_id, $value_id), $this->slot_operand($location->slot_id)];
        }
        [$type, $address] = $this->addresses[$location->address_id] ?? throw new \LogicException('Address used before preparation');
        if ($this->body->values[$value_id - 1]->type_id !== $type) {
            throw new \LogicException('Projected address and value types differ');
        }
        return [$this->storage_type($type), $address];
    }

    /** Emit the checked-address primitive once; failures cannot reach its consuming memory operation. */
    private function emit_address(\lower\storage_address $location): void
    {
        if (isset($this->addresses[$location->address_id]) || ($location->address_id <= 0)) {
            throw new \LogicException('Repeated or invalid prepared address');
        }
        $type = $this->body->slot_for($location->slot_id)->type_id;
        $base = $this->slot_operand($location->slot_id);
        foreach ($location->projections as $projection)
        {
            $address = '%a' . ++$this->next_address;
            if ($projection->kind === \check_bodies\projection_kind::field)
            {
                $layout = $this->body->backend_context()->layouts[$type] ?? throw new \LogicException('Missing field layout');
                $field = $layout->fields[$projection->operand] ?? throw new \LogicException('Invalid field projection');
                if ($field->type_id !== $projection->type_id) {
                    throw new \LogicException('Field projection changed type');
                }
                $this->ir .= '  ' . $address . ' = getelementptr i8, ' . $this->pointer . ' ' . $base
                    . ', i64 ' . $layout->offsets[$projection->operand] . "\n";
            }
            elseif ($projection->kind === \check_bodies\projection_kind::element) {
                $storage = $this->body->definition_for($type)->element_storage;
                if ($storage?->element_type !== $projection->type_id) {
                    throw new \LogicException('Invalid prepared element projection');
                }
                $address = $this->storage_element_address($storage, $base, $projection->operand);
            }
            else
            {
                $shape = $this->body->definition_for($type)->representation;
                if (($shape->kind !== \type_model\representation_kind::fixed_array)
                    || ($shape->payload->element_type !== $projection->type_id)) {
                    throw new \LogicException('Invalid array projection');
                }
                $index = $this->checked_index($projection->operand, $shape->payload->count);
                $this->ir .= '  ' . $address . ' = getelementptr ' . $this->storage_type($projection->type_id)
                    . ', ' . $this->pointer . ' ' . $base . ', ' . $index . "\n";
            }
            $base = $address;
            $type = $projection->type_id;
        }
        $this->addresses[$location->address_id] = [$type, $base];
    }

    /** Normalize integer indices without unsigned sign-extension, then stop on any out-of-bounds index. */
    private function checked_index(int $value, int $count): string
    {
        $definition = $this->body->definition_for($this->body->values[$value - 1]->type_id);
        $bits = $definition->representation->payload->bit_width;
        // One extra bit keeps every source integer and positive capacity representable for unsigned comparison.
        $capacity_bits = strlen(decbin($count)) + 1;
        $width = max($bits + 1, $capacity_bits);
        $id = ++$this->next_address;
        $index = '%index' . $id;
        $this->ir .= '  ' . $index . ' = ' . ($definition->signed ? 'sext' : 'zext') . ' i' . $bits . ' '
            . $this->operands[$value] . ' to i' . $width . "\n";
        $this->ir .= '  %bound' . $id . ' = icmp ult i' . $width . ' ' . $index . ', ' . $count . "\n"
            . '  br i1 %bound' . $id . ', label %index_ok' . $id . ', label %index_fail' . $id . "\n"
            . 'index_fail' . $id . ":\n  call void @llvm.trap()\n  unreachable\nindex_ok" . $id . ":\n";
        $this->references['llvm.trap'] = $this->body->backend_context()->abi_for('llvm.trap')
            ?? throw new \LogicException('Missing backend bounds-failure primitive');
        return 'i' . $width . ' ' . $index;
    }
}
