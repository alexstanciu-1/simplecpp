<?php
declare(strict_types=1);

/*
 * Role: Analyze statement consumption and local/return effects.
 * Used by: Lifetime_Worker (private trait methods on this owner)
 * Call map:
 *   analyze_statement()
 *     -> evaluate(); [action] complete local/return lifetime effects
 */

namespace analyze_lifetimes;

use check_bodies\statement_kind;

// Private checked-statement consumption and return validation for Lifetime_Worker.
trait Statement_Lifetimes
{
    /** Analyze one full expression, transfer any constructed local destination and schedule remaining temporary cleanup. */
    private function analyze_statement(\check_bodies\typed_statement $statement, int $index,
        int $end_index, \check_bodies\typed_block $block): void
    {
        $body = $this->body;
        $statement_id = $index + 1;

        // Leave completed lexical scopes before evaluating the next statement.
        $this->exit_to_scope($statement->scope_id, $index);
        $scope = $body->scope_for($statement->scope_id);
        if (($index < $scope->statement_start) || ($index >= ($scope->statement_start + $scope->statement_count))) {
            throw new \LogicException('Checked statement is outside its scope range');
        }

        // The statement selects how its result is consumed, independently of object destruction.
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
        $target = ($statement->target?->local_id ?? 0);
        if ($target !== 0) {
            $this->validate_local_target($statement);
        }

        // Restrict expression traversal to the effects assigned to this checked statement.
        $next_call = $statement->call_start;
        $limit = $next_call + $statement->call_count;
        if (($next_call < 0) || ($statement->call_count < 0) || ($limit > count($body->calls))) {
            throw new \LogicException('Invalid checked statement call segment');
        }
        foreach ($statement->target?->indices() ?? [] as $projection) {
            $this->evaluate($projection->operand, $statement_id, $next_call, $projection->call_end);
            $this->end_value($projection->operand, $statement_id, lifetime_end::target_index);
        }
        $value = $statement->value_id;
        $this->evaluate($value, $statement_id, $next_call, $limit);
        if ($value !== 0) {
            $this->end_value($value, $statement_id, $end);
        }
        if (($next_call !== $limit) || ($this->live_values !== [])) {
            throw new \LogicException('Incomplete checked expression consumption');
        }

        // The local becomes live after its initialization expression has completed.
        if ($statement->kind === statement_kind::local_declaration) {
            $this->start_local($target, $statement_id);
        }

        // Direct construction transfers the destination to its local or caller owner.
        // Other temporaries die in reverse order when the full expression ends.
        while ($this->temporaries !== []) {
            $temporary = array_pop($this->temporaries);
            if ((($statement->write !== \check_bodies\local_write_kind::direct_construct)
                && ($statement->return !== \check_bodies\return_kind::direct_construct)) || ($temporary !== $statement->value_id)) {
                $this->cleanups[] = new cleanup_obligation(cleanup_subject::temporary, $temporary, $statement_id, $this->block_id);
            }
        }

        if ($statement->kind === statement_kind::return_statement) {
            $this->validate_return($statement, $statement_id, $end_index, $block);
        }
    }

    /** Require the analyzed return value and statement boundary to match the checked callable exit. */
    private function validate_return(\check_bodies\typed_statement $statement, int $statement_id,
        int $end_index, \check_bodies\typed_block $block): void
    {
        $body = $this->body;
        $value = $statement->value_id;
        $signature = $body->signature_for($body->callable_id);
        $return_type = $signature->return_type;
        $owned = $signature->result === \type_model\result_production::owned;
        if ($owned !== ($statement->return !== \check_bodies\return_kind::value)) {
            throw new \LogicException('Return construction disagrees with result ownership');
        }
        if ($owned)
        {
            $source = $body->values[$value - 1];
            $life = $body->definition_for($return_type)->lifetime;
            $valid = match ($statement->return)
            {
                \check_bodies\return_kind::store => ($source->kind === \check_bodies\value_kind::record_default)
                    || (($source->kind === \check_bodies\value_kind::local_read) && ($life->copy === \type_model\copy_kind::value)),
                \check_bodies\return_kind::direct_construct => in_array($source->kind,
                    [\check_bodies\value_kind::call_result, \check_bodies\value_kind::default_construct], true),
                \check_bodies\return_kind::copy_construct => ($source->kind === \check_bodies\value_kind::local_borrow)
                    && ($life->copy === \type_model\copy_kind::construct),
                \check_bodies\return_kind::move_construct => ($source->kind === \check_bodies\value_kind::local_borrow)
                    && ($life->expiring === \type_model\expiring_construction::construct),
                default => false,
            };
            if (!$valid) {
                throw new \LogicException('Return construction lacks its checked source capability');
            }
        }
        $void = $body->definition_for($return_type)->representation->kind === \type_model\representation_kind::void_type;
        if ((($value === 0) !== $void) || (($value !== 0) && ($body->values[$value - 1]->type_id !== $return_type))
            || ($statement_id !== $end_index) || ($block->end !== \check_bodies\flow_end::return_exit)) {
            throw new \LogicException('Analyzed return does not match its resolved type or flow');
        }
    }
}
