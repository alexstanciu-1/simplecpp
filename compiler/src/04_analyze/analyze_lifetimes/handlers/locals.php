<?php
declare(strict_types=1);

/*
 * Role: Track local starts, scope exits and target validity.
 * Used by: Lifetime_Worker (private trait methods on this owner)
 * Call map:
 *   exit_to_scope()
 *     -> end_local() [each exited local]
 */

namespace analyze_lifetimes;

use check_bodies\statement_kind;

// Private methods composed only by this process's callable worker.
trait Local_Lifetimes
{
    /** Register one initialized binding in the private live set and construction-order stack. */
    private function start_local(int $id, int $statement): void
    {
        if (isset($this->live[$id])) {
            throw new \LogicException('Local initialized more than once on a path');
        }
        $state = new active_local($id, $statement);
        $this->active[] = $state;
        $this->live[$id] = $state;
    }

    /** End innermost bindings until the remaining stack belongs to the destination scope. */
    private function exit_to_scope(int $scope, int $boundary): void
    {
        while ($this->active !== []) {
            $last = $this->active[count($this->active) - 1];
            if (Local_Flow::contains($this->body, $this->body->names->local_for($last->local_id)->scope_id, $scope)) {
                break;
            }
            $this->end_local($boundary, local_end::scope_exit);
        }
    }

    /** Record the most recent binding exit and its destruction obligation when required by the type. */
    private function end_local(int $boundary, local_end $end): void
    {
        $local = array_pop($this->active);
        unset($this->live[$local->local_id]);

        // Record semantic scope exit for all locals, with executable cleanup only when required.
        if (!$this->body->local_passing($local->local_id)->is_borrow()
            && ($this->body->definition_for($this->body->local_type_for($local->local_id))->lifetime->cleanup === \type_model\cleanup_kind::destroy)) {
            $this->cleanups[] = new cleanup_obligation(cleanup_subject::local, $local->local_id, $boundary, $this->block_id);
        }
        $this->locals[] = new local_lifetime($local->local_id, $local->initialized_statement_id, $boundary, $end, $this->block_id);
    }

    /** Check declaration or assignment liveness and the contract for copying versus direct construction. */
    private function validate_local_target(\check_bodies\typed_statement $statement): void
    {
        $body = $this->body;
        $target = ($statement->target?->local_id ?? 0);
        $local = $body->names->local_for($target);
        if ($statement->kind === statement_kind::local_declaration) {
            if (isset($this->live[$target]) || ($local->scope_id !== $statement->scope_id)
                || ($local->declaration_node_id !== $statement->source_node_id)) {
                throw new \LogicException('Invalid local initialization lifetime');
            }
        }
        elseif (!isset($this->live[$target])) {
            throw new \LogicException('Assignment requires a live initialized local');
        }

        if ($body->local_passing($target) === \type_model\argument_passing::borrow_const) {
            throw new \LogicException('Assignment cannot write through a const reference');
        }

        // Scalar writes require value copying; copy construction needs an explicit implementation.
        $type = $body->place_type($statement->target);
        $this->contract($type, $statement->source_node_id,
            ($statement->kind === statement_kind::local_declaration) && ($statement->write === \check_bodies\local_write_kind::value_copy));
        if (($statement->kind === statement_kind::assignment) && ($statement->write === \check_bodies\local_write_kind::value_copy)
            && ($body->definition_for($type)->lifetime->assignment !== \type_model\assignment_kind::value)) {
            throw new \LogicException('Value assignment requires its type contract');
        }
        if (($statement->write === \check_bodies\local_write_kind::zero_initialize)
            && (($body->definition_for($type)->lifetime->construction !== \type_model\construction_kind::zero)
                || ($body->values[$statement->value_id - 1]->kind !== \check_bodies\value_kind::record_default))) {
            throw new \LogicException('Zero initialization requires its construction contract and checked initializer');
        }
        if (($statement->write === \check_bodies\local_write_kind::copy_construct)
            && (($body->definition_for($type)->lifetime->copy !== \type_model\copy_kind::construct)
                || ($body->values[$statement->value_id - 1]->kind !== \check_bodies\value_kind::local_borrow))) {
            throw new \LogicException('Copy construction requires its type contract and a borrowed local source');
        }
        if (($statement->write === \check_bodies\local_write_kind::copy_assign)
            && (($body->definition_for($type)->lifetime->assignment !== \type_model\assignment_kind::call)
                || ($body->values[$statement->value_id - 1]->kind !== \check_bodies\value_kind::local_borrow))) {
            throw new \LogicException('Copy assignment requires its type contract and a borrowed source');
        }
        if (($body->values[$statement->value_id - 1]->type_id ?? null) !== $type) {
            $this->fail($statement->source_node_id, 'Unsupported lifetime analysis for local conversion');
        }
    }
}
