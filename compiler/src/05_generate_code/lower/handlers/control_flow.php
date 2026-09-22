<?php
declare(strict_types=1);

/*
 * Role: Translate typed block endings into backend terminators.
 * Used by: Lowering_Worker (private trait methods on this owner)
 * Call map:
 *   lower_terminator()
 *     -> [action] construct jump/branch/return and validate return contract
 */

namespace lower;

use prepare_backend\callable_binding;

// Private methods composed by the per-callable worker.
trait Control_Flow_Lowering
{
    /** Map checked block exits to lowered targets and validate every return against the prepared binding. */
    private function lower_terminator(\check_bodies\typed_block $block, array $block_ids,
        int $value_id, int $node_id, callable_binding $binding): jump_terminator|branch_terminator|return_terminator
    {
        $terminator = match ($block->end) {
            \check_bodies\flow_end::jump => new jump_terminator($block_ids[$block->first]),
            \check_bodies\flow_end::branch => new branch_terminator($value_id, $block_ids[$block->first], $block_ids[$block->second]),
            \check_bodies\flow_end::return_exit => new return_terminator($value_id, $node_id),
            \check_bodies\flow_end::fallthrough => new return_terminator(0, $node_id),
        };
        if ($terminator instanceof return_terminator)
        {
            $void = ($binding->return_definition->representation->kind === \type_model\representation_kind::void_type)
                || ($binding->result_passing === \type_model\result_passing::caller_storage);
            if ((($terminator->value_id === 0) !== $void)
                || (($terminator->value_id !== 0) && ($this->values[$terminator->value_id - 1]->type_id !== $binding->signature->return_type))) {
                throw new \LogicException('Lowered return does not match its prepared type');
            }
        }
        return $terminator;
    }
}
