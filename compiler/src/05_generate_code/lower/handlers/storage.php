<?php
declare(strict_types=1);

/*
 * Role: Expand checked live-prefix transitions around ordinary element lifecycle instructions.
 * Used by: Lowering_Worker (private methods on this owner)
 * Call map: lower_call() -> lower_storage_transition() -> construct_at() / lifecycle_target() [Local_Lowering]
 * Writes: private body instructions and body-local internal address identities.
 */
namespace lower;

trait Storage_Lowering
{
    /** Check capacity/address first, operate on the element, then publish the changed live prefix. */
    private function lower_storage_transition(call_operands $call, int $node): void
    {
        $storage = $call->target->parameters[0]->definition->element_storage;
        $life = $storage->element->lifetime;
        $destination = new storage_address(0, address_id: ++$this->next_address);
        $transition = new storage_transition($call, $destination);
        $this->instructions[] = new lowered_instruction(instruction_kind::storage_begin, $node, 0, $transition);

        // Bind the exact complete operation now; emission never chooses source versus runtime lifecycle.
        if ($call->target->storage->role === \type_model\storage_role::push) {
            $this->construct_at($destination, $storage->element_type, $this->arguments[$call->argument_start + 1],
                $node, \type_model\lifecycle_operation_kind::copy_construct);
        }
        elseif ($life->cleanup === \type_model\cleanup_kind::destroy) {
            $this->instructions[] = new lowered_instruction(instruction_kind::destroy, $node, 0,
                new destruction_operands($destination, $this->lifecycle_target($life->destructor)));
        }
        $this->instructions[] = new lowered_instruction(instruction_kind::storage_end, $node, 0, $transition);
    }

}
