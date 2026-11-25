<?php
declare(strict_types=1);

/*
 * Role: Emit typed element operations through prepared native storage mechanics.
 * Used by: Emission_Worker; Call_Emission and Location_Emission
 * Call map: emit_storage_call() / emit_storage_begin() / emit_storage_end()
 *   -> storage_native(); storage_element_address() -> storage_native()
 * Ordinary lifecycle instructions operate between native prefix guards and commits.
 */
namespace emit_llvm;

trait Storage_Emission
{
    /** Expand a checked storage role while retaining ordinary argument evaluation and prepared ABI references. */
    private function emit_storage_call(\lower\lowered_instruction $instruction, array $arguments): void
    {
        $target = $instruction->payload->target;
        $storage = $target->parameters[0]->definition->element_storage;
        $family = $storage->family;
        $role = $target->storage->role;
        $owner = $arguments[0];
        if ($role === \type_model\storage_role::allocate)
        {
            $layout = $this->body->backend_context()->layouts[$storage->element_type];
            $bits = $family->counter->representation->payload->bit_width;
            foreach ([$layout->size, $layout->alignment] as $fact) {
                if (strlen(decbin($fact)) >= $bits) {
                    throw new \RuntimeException('Element layout exceeds the storage counter range');
                }
            }
            $this->storage_native($family, 'allocate', [$owner, $arguments[1], (string)$layout->size, (string)$layout->alignment]);
        }
        elseif ($role === \type_model\storage_role::count) {
            $name = '%v' . $instruction->result_value_id;
            $this->storage_native($family, 'count', [$owner], $name . ' = ');
            $this->operands[$instruction->result_value_id] = $name;
        }
        elseif (in_array($role, [\type_model\storage_role::push, \type_model\storage_role::pop], true)) {
            throw new \LogicException('Element lifecycle requires an expanded storage transition');
        }
        else {
            $this->storage_native($family, $role->value, $arguments);
        }
    }

    /** Prepare one typed element destination; native guards run before any copy or destruction. */
    private function emit_storage_begin(\lower\storage_transition $transition): void
    {
        $address_id = $transition->destination->address_id;
        if (($this->storage_transition !== null) || isset($this->addresses[$address_id])) {
            throw new \LogicException('Repeated or overlapping storage transition');
        }
        $arguments = $this->call_arguments($transition->call);
        $storage = $transition->call->target->parameters[0]->definition->element_storage;
        $owner = $arguments[0];
        $address = '%storage' . ++$this->next_address;
        if ($transition->call->target->storage->role === \type_model\storage_role::push) {
            $this->storage_native($storage->family, 'next', [$owner], $address . ' = ');
        }
        else
        {
            // A zero count gives -1; the checked native address rejects it without touching memory.
            $count = '%count' . $this->next_address;
            $last = '%last' . $this->next_address;
            $bits = $storage->family->counter->representation->payload->bit_width;
            $this->storage_native($storage->family, 'count', [$owner], $count . ' = ');
            $this->ir .= '  ' . $last . ' = sub i' . $bits . ' ' . $count . ', 1' . "\n";
            $this->storage_native($storage->family, 'at', [$owner, $last], $address . ' = ');
        }
        $this->addresses[$address_id] = [$storage->element_type, $address];
        $this->storage_transition = $transition;
    }

    /** Commit only the exact transition whose element operation has just been emitted. */
    private function emit_storage_end(\lower\storage_transition $transition): void
    {
        if ($this->storage_transition !== $transition) {
            throw new \LogicException('Unmatched storage transition completion');
        }
        $call = $transition->call;
        $storage = $call->target->parameters[0]->definition->element_storage;
        $owner = $this->operands[$this->body->arguments[$call->argument_start]];
        $this->storage_native($storage->family,
            $call->target->storage->role === \type_model\storage_role::push ? 'commit' : 'pop', [$owner]);
        unset($this->addresses[$transition->destination->address_id]);
        $this->storage_transition = null;
    }

    /** Use the same checked index and type-owned native address contract for scalar and record elements. */
    private function storage_element_address(\type_model\element_storage $storage, string $owner, int $value): string
    {
        $definition = $this->body->definition_for($this->body->values[$value - 1]->type_id);
        $bits = $definition->representation->payload->bit_width;
        $counter_bits = $storage->family->counter->representation->payload->bit_width;
        $width = max($bits + 1, $counter_bits + 1);
        $id = ++$this->next_address;
        $index = '%storage_index' . $id;
        $count = '%storage_count' . $id;
        $this->storage_native($storage->family, 'count', [$owner], $count . ' = ');
        $this->ir .= '  ' . $index . ' = ' . ($definition->signed ? 'sext' : 'zext') . ' i' . $bits . ' '
            . $this->operands[$value] . ' to i' . $width . "\n"
            . '  %storage_limit' . $id . ' = zext i' . $counter_bits . ' ' . $count . ' to i' . $width . "\n"
            . '  %storage_valid' . $id . ' = icmp ult i' . $width . ' ' . $index . ', %storage_limit' . $id . "\n"
            . '  br i1 %storage_valid' . $id . ', label %storage_ok' . $id . ', label %storage_fail' . $id . "\n"
            . 'storage_fail' . $id . ":\n  call void @llvm.trap()\n  unreachable\nstorage_ok" . $id . ":\n"
            . '  %storage_narrow' . $id . ' = trunc i' . $width . ' ' . $index . ' to i' . $counter_bits . "\n";
        $this->references['llvm.trap'] = $this->body->backend_context()->abi_for('llvm.trap');
        $address = '%storage_address' . $id;
        $this->storage_native($storage->family, 'at', [$owner, '%storage_narrow' . $id], $address . ' = ');
        return $address;
    }

    /** The adapter supplies implementation names; emission never guesses a runtime symbol or descriptor offset. */
    private function storage_native(\type_model\storage_family $family, string $role, array $arguments, string $prefix = ''): void
    {
        $primitive = $family->primitives[$role];
        $target = $this->body->backend_context()->storage_targets[$primitive->link_name] ?? null;
        if ($target?->task->primitive !== $primitive) {
            throw new \LogicException('Missing or stale storage primitive');
        }
        $this->emit_abi_call($target->target, $arguments, $prefix);
    }
}
