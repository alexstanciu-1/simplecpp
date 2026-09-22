<?php
declare(strict_types=1);

/*
 * Role: Lower statement values and local writes.
 * Used by: Lowering_Worker (private trait methods on this owner)
 * Call map:
 *   lower_statement()
 *     -> evaluate(); write_local() / write_result() [selected destination]
 *     -> end_locals(); emit_cleanups()
 */

namespace lower;

use check_bodies\statement_kind;
use analyze_lifetimes\lifetime_end;

// Private methods composed by the per-callable worker.
trait Statement_Lowering
{
    /** Evaluate the statement and apply its local effects and cleanup before handing its value to the terminator. */
    private function lower_statement(\check_bodies\typed_statement $statement, int $statement_id, int $block_id): int
    {
        $next_call = $statement->call_start;
        $end_call = $next_call + $statement->call_count;
        foreach ($statement->target?->indices() ?? [] as $projection) {
            $this->evaluate($projection->operand, $statement_id, $next_call, $projection->call_end);
            $this->consume($projection->operand, $statement_id, lifetime_end::target_index);
        }
        $address = $statement->target === null ? null : $this->prepare_address($statement->target, $statement->source_node_id);
        $this->evaluate($statement->value_id, $statement_id, $next_call, $end_call);
        $value_id = $statement->value_id === 0 ? 0 : $this->get_value($statement->value_id);

        // End expression access with the exact consumption reason established by analysis.
        if ($value_id !== 0)
        {
            $end = match ($statement->kind)
            {
                statement_kind::expression_statement => lifetime_end::discard,
                statement_kind::return_statement => $statement->return === \check_bodies\return_kind::value
                    ? lifetime_end::return_copy : lifetime_end::return_construct,
                statement_kind::condition => lifetime_end::condition,
                statement_kind::local_declaration, statement_kind::assignment => match ($statement->write) {
                    \check_bodies\local_write_kind::value_copy => $statement->kind === statement_kind::assignment ? lifetime_end::assignment_source : lifetime_end::local_copy,
                    \check_bodies\local_write_kind::zero_initialize, \check_bodies\local_write_kind::direct_construct => lifetime_end::local_construct,
                    \check_bodies\local_write_kind::copy_construct => lifetime_end::copy_source,
                    \check_bodies\local_write_kind::copy_assign => lifetime_end::assignment_source,
                },
            };
            $this->consume($statement->value_id, $statement_id, $end);
        }

        // Complete initialization or assignment before temporary and lexical cleanup.
        if (in_array($statement->kind, [statement_kind::local_declaration, statement_kind::assignment], true)) {
            $this->write_local($statement, $statement_id, $value_id, $address);
        }
        if ($statement->return !== \check_bodies\return_kind::value) {
            $this->write_result($statement, $value_id);
            $value_id = 0;
        }
        $this->end_locals($statement_id, $statement->kind === statement_kind::return_statement, $block_id);
        $this->emit_cleanups($statement_id, $block_id);

        // Return/condition scalars remain available after the owned objects have been destroyed.
        return $value_id;
    }

    /** Establish the caller's result before full-expression and local cleanup. */
    private function write_result(\check_bodies\typed_statement $statement, int $value): void
    {
        if ($this->result_slot === 0) {
            throw new \LogicException('Owned return requires an incoming destination');
        }
        if ($statement->return === \check_bodies\return_kind::direct_construct) {
            if ($this->values[$value - 1]->storage_slot_id !== $this->result_slot) {
                throw new \LogicException('Fresh return must construct in the caller destination');
            }
            return;
        }
        if ($statement->return === \check_bodies\return_kind::store) {
            $this->instructions[] = new lowered_instruction(instruction_kind::store, $statement->source_node_id, 0,
                new store_operands(new storage_address($this->result_slot), $value));
            return;
        }
        $kind = $statement->return === \check_bodies\return_kind::copy_construct
            ? \type_model\lifecycle_operation_kind::copy_construct : \type_model\lifecycle_operation_kind::move_construct;
        $this->construct_from($this->result_slot, $value, $statement->source_node_id, $kind);
    }
}
