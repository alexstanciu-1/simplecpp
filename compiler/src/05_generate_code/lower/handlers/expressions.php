<?php
declare(strict_types=1);

/*
 * Role: Lower values in their checked evaluation order.
 * Used by: Lowering_Worker (private trait methods on this owner)
 * Call map:
 *   evaluate()
 *     -> lower_call() / lower_conversion() / lower_operation() / lower_simple_value()
 *   lower_call() -> argument_storage() [reuse an address or store a checked scalar]
 *     -> lower_storage_transition() [push/pop; ordinary lifecycle between prefix checks/commit]
 */

namespace lower;

use prepare_backend\integer_adaptation;

use check_bodies\value_kind;
use analyze_lifetimes\lifetime_end;

// Private methods composed by the per-callable worker.
trait Expression_Lowering
{
    /**
     * Checked call order records effects, but argument reads must precede later
     * nested calls. Traverse the checked operand graph with a bounded explicit stack.
     */
    private function evaluate(int $value_id, int $statement_id, int &$next_call, int $limit): void
    {
        $body = $this->input->analysis->body;
        foreach (\check_bodies\Expression_Order::steps($body, $value_id, $next_call, $limit) as $step)
        {
            if ($step->call_id !== 0) {
                $this->lower_call($step->call_id, $statement_id);
                ++$next_call;
            }
            else
            {
                switch ($body->values[$step->value_id - 1]->kind)
                {
                    case value_kind::conversion:
                        $this->lower_conversion($step->value_id, $statement_id);
                        break;
                    case value_kind::operation:
                        $this->lower_operation($step->value_id, $statement_id);
                        break;
                    case value_kind::local_read:
                    case value_kind::local_borrow:
                        foreach ($body->values[$step->value_id - 1]->payload->indices() as $projection) {
                            $this->consume($projection->operand, $statement_id, lifetime_end::index_input, $step->value_id);
                        }
                        $this->get_value($step->value_id);
                        break;
                    default:
                        $this->get_value($step->value_id);
                        break;
                }
            }
        }
    }

    /** Consume prepared arguments and choose direct local or temporary storage for caller-constructed results. */
    private function lower_call(int $call_id, int $statement_id): void
    {
        $body = $this->input->analysis->body;
        $call = $body->calls[$call_id - 1];
        $target = $this->input->backend->callable_for($body, $call->target_callable_id);
        if ($call->argument_count !== count($target->parameters)) {
            throw new \LogicException('Lowered call arity differs from its prepared contract');
        }

        // Preserve the prepared value/borrow mode while consuming this call's argument accesses.
        $start = count($this->arguments);
        foreach ($target->parameters as $index => $parameter)
        {
            $argument = $body->argument_for($call_id, $index + 1);
            $id = $this->get_value($argument->value_id);
            if (($argument->parameter_type_id !== $parameter->type_id) || ($argument->passing !== $parameter->passing)
                || ($this->values[$id - 1]->type_id !== $parameter->type_id)) {
                throw new \LogicException('Argument type differs from its prepared parameter');
            }
            $id = $this->argument_storage($id, $parameter, $argument->value_id, $call->source_node_id);
            $this->arguments[] = $id;
            $this->consume($argument->value_id, $statement_id,
                $parameter->passing !== \type_model\argument_passing::value
                    ? lifetime_end::argument_borrow : lifetime_end::argument_copy, $call_id);
        }

        if (in_array($target->storage?->role, [\type_model\storage_role::push, \type_model\storage_role::pop], true)) {
            $this->lower_storage_transition(new call_operands($target, $start, $call->argument_count), $call->source_node_id);
            return;
        }

        // A semantic object result may use a hidden destination despite its physical void return.
        $void = $target->return_definition->representation->kind === \type_model\representation_kind::void_type;
        if (($call->result_value_id === 0) !== $void) {
            throw new \LogicException('Inconsistent lowered call result presence');
        }
        $result_id = 0;
        $destination = 0;
        if (!$void)
        {
            $value = $body->values[$call->result_value_id - 1] ?? throw new \LogicException('Missing checked call result');
            if (($value->kind !== value_kind::call_result) || ($value->payload !== $call_id) || ($value->type_id !== $target->signature->return_type)) {
                throw new \LogicException('Inconsistent checked call result during lowering');
            }

            // Local initialization reuses its destination; other constructions get temporary storage.
            if ($target->result_passing === \type_model\result_passing::caller_storage) {
                $destination = $this->construction_destinations[$call->result_value_id] ?? 0;
                if ($destination === 0) {
                    $this->slots[] = new storage_slot(0, $value->type_id, $call->result_value_id);
                    $destination = count($this->slots);
                }
            }
            $result_id = $this->add_value($call->result_value_id, $destination);
        }

        $this->instructions[] = new lowered_instruction(instruction_kind::call, $call->source_node_id, $result_id,
            new call_operands($target, $start, $call->argument_count, $destination));
    }

    /** Address passing reuses existing storage or materializes a checked scalar exactly once for this call. */
    private function argument_storage(int $id, \prepare_backend\callable_parameter $parameter, int $source_id, int $node): int
    {
        if (!$parameter->passing->is_borrow() || ($this->values[$id - 1]->storage_slot_id !== 0)) {
            return $id;
        }
        if (($parameter->passing !== \type_model\argument_passing::borrow_const)
            || ($parameter->definition->representation->kind !== \type_model\representation_kind::integer)
            || ($parameter->definition->lifetime->cleanup !== \type_model\cleanup_kind::none)) {
            throw new \LogicException('Temporary argument storage requires a cleanup-free const scalar borrow');
        }

        // The scalar was evaluated in checked order. Store that result; never evaluate its expression again.
        $this->slots[] = new storage_slot(0, $parameter->type_id, $source_id);
        $slot = count($this->slots);
        $address = new storage_address($slot);
        $this->instructions[] = new lowered_instruction(instruction_kind::store, $node, 0, new store_operands($address, $id));

        // This address operand shares checked provenance, not the scalar's lowered identity.
        // Analysis ends the single semantic access at this call; no destructor is required for its slot.
        $this->values[] = new lowered_value($source_id, $parameter->type_id, $slot);
        $borrow = count($this->values);
        $this->instructions[] = new lowered_instruction(instruction_kind::borrow, $node, $borrow, $address);
        return $borrow;
    }

    /** Validate the supported integer widening contract, consume its input and emit an explicit adaptation. */
    private function lower_conversion(int $value_id, int $statement_id): void
    {
        $body = $this->input->analysis->body;
        $conversion = $body->conversion_for($value_id);
        $input_id = $this->get_value($conversion->input_value_id);
        $value = $body->values[$value_id - 1];
        $source = $body->definition_for($this->values[$input_id - 1]->type_id);
        $destination = $body->definition_for($value->type_id);
        if (($conversion->operation !== \check_bodies\conversion_kind::integer_widen)
            || ($source->representation->kind !== \type_model\representation_kind::integer)
            || ($destination->representation->kind !== \type_model\representation_kind::integer)
            || ($source->signed !== $destination->signed)
            || ($source->representation->payload->bit_width >= $destination->representation->payload->bit_width)) {
            throw new \LogicException('Unsupported lowered integer widening');
        }

        // Widening consumes its checked input and produces a fresh scalar result.
        $operation = $source->signed ? integer_adaptation::sign_extend : integer_adaptation::zero_extend;
        $this->consume($conversion->input_value_id, $statement_id, lifetime_end::conversion_input, $value_id);
        $result_id = $this->add_value($value_id);
        $this->instructions[] = new lowered_instruction(instruction_kind::convert, $value->source_node_id, $result_id,
            new conversion_operands($input_id, $operation));
    }

    /** Lower a selected binary contract after validating its implementation and consuming operands. */
    private function lower_operation(int $value_id, int $statement_id): void
    {
        $body = $this->input->analysis->body;
        $value = $body->values[$value_id - 1];
        $operation = $body->operation_for($value_id);
        $native = Binary_Operations::select($body, $operation->contract);

        // Keep operand consumption in the same order as lifetime analysis.
        $operands = [];
        foreach ([$operation->left, $operation->right] as $operand) {
            $operands[] = $this->get_value($operand);
            $this->consume($operand, $statement_id, lifetime_end::operation_input, $value_id);
        }
        $result = $this->add_value($value_id);
        $this->instructions[] = new lowered_instruction(instruction_kind::binary, $value->source_node_id, $result,
            new binary_operands($operands[0], $operands[1], $native));
    }

    /** Materialize a checked literal, scalar local read or object borrow in the private instruction stream. */
    private function lower_simple_value(\check_bodies\typed_value $value, int $id): void
    {
        switch ($value->kind)
        {
            case value_kind::byte_literal:
                $this->instructions[] = new lowered_instruction(instruction_kind::byte_literal, $value->source_node_id, $id, $value->payload);
                break;
            case value_kind::default_construct:
                $operation = $this->input->analysis->body->definition_for($value->type_id)->lifetime->default_constructor;
                $target = $this->input->backend->abi_for($operation->link_name);
                if ($target?->lifecycle_operation !== $operation) {
                    throw new \LogicException('Missing or stale default construction target');
                }
                $this->instructions[] = new lowered_instruction(instruction_kind::default_construct, $value->source_node_id, $id, $target);
                break;
            case value_kind::record_default:
                $this->instructions[] = new lowered_instruction(instruction_kind::constant, $value->source_node_id, $id, 'zeroinitializer');
                break;
            case value_kind::integer_literal:
                $this->instructions[] = new lowered_instruction(instruction_kind::constant, $value->source_node_id, $id, $value->payload);
                break;
            case value_kind::local_read:
            case value_kind::local_borrow:
                $this->load_local($value, $id);
                break;
            default:
                throw new \LogicException('Unsupported checked value during lowering');
        }
    }
}
