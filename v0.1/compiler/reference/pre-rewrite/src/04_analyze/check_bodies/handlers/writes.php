<?php
declare(strict_types=1);

/*
 * Role: Select initialization and assignment from their independent type capabilities.
 * Used by: Body_Worker through Statement_Checking
 * Call map: check_local_write() -> initialization_write() / assignment_write()
 * Output: one completed location access and one explicit checked write mode.
 */
namespace check_bodies;

trait Local_Write_Checking
{
    /** Convert the expression once, then let the destination operation select source access. */
    private function check_local_write(int $id, int $scope, place $target, int $destination, int $expression,
        statement_kind $kind, conversion_use $use, ?int $start = null): typed_statement
    {
        $start ??= count($this->calls);
        if ($expression === 0) {
            $value = $this->default_value($id, $destination);
        }
        else {
            $value = $this->check_expression($expression, $destination);
            $value = $this->convert($value, $destination, $use, $expression);
        }
        $life = $this->types->definition_for($destination)->lifetime;
        $write = $kind === statement_kind::assignment
            ? $this->assignment_write($value, $life, $expression ?: $id)
            : $this->initialization_write($value, $life, $expression ?: $id);
        return new typed_statement($id, $kind, $value, $start, count($this->calls) - $start, $scope, $target, $write);
    }

    /** Initialize new storage from a location copy, a constructor destination or an ordinary value. */
    private function initialization_write(int $id, \type_model\lifetime_contract $life, int $node): local_write_kind
    {
        $source = $this->values[$id - 1];
        if ($source instanceof pending_place_value)
        {
            if ($life->copy === \type_model\copy_kind::unavailable) {
                $this->fail($node, 'Unsupported inline object copy; copy construction is unavailable');
            }
            $construct = $life->copy === \type_model\copy_kind::construct;
            $this->select_place_access($id, $construct);
            return $construct ? local_write_kind::copy_construct : local_write_kind::value_copy;
        }

        // Fresh construction does not require permission to copy an existing object.
        if ($source->kind === value_kind::record_default) {
            return local_write_kind::zero_initialize;
        }
        if (($source->kind === value_kind::default_construct) || (($source->kind === value_kind::call_result)
            && ($this->signature($this->calls[$source->payload - 1]->target_callable_id)->result
                === \type_model\result_production::owned))) {
            return local_write_kind::direct_construct;
        }
        if (($life->copy !== \type_model\copy_kind::value) || ($life->cleanup !== \type_model\cleanup_kind::none)) {
            $this->fail($node, 'Unsupported inline object copy; copy construction is unavailable');
        }
        return local_write_kind::value_copy;
    }

    /** Update live storage; assignment permission never comes from copy-construction capability. */
    private function assignment_write(int $id, \type_model\lifetime_contract $life, int $node): local_write_kind
    {
        if ($life->assignment === \type_model\assignment_kind::unavailable) {
            $this->fail($node, 'Copy assignment is unavailable for this type');
        }
        $source = $this->values[$id - 1];
        $call = $life->assignment === \type_model\assignment_kind::call;
        if ($source instanceof pending_place_value) {
            $this->select_place_access($id, $call);
            return $call ? local_write_kind::copy_assign : local_write_kind::value_copy;
        }
        if (($call) || ($source->kind === value_kind::default_construct) || ($life->cleanup !== \type_model\cleanup_kind::none)) {
            $this->fail($node, 'Copy assignment currently requires an existing source object');
        }
        return local_write_kind::value_copy;
    }
}
