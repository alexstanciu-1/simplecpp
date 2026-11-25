<?php
declare(strict_types=1);

/*
 * Role: Check statements and drive scope/control traversal.
 * Used by: Body_Worker (private trait methods on this owner)
 * Call map:
 *   check_statements()
 *     -> check_statement() [each statement]
 *   check_return() -> return_construction() [owned result]
 *   return_construction() -> copy_return() [copy permission]
 */

namespace check_bodies;

use parse\syntax_kind;
use parse\Syntax_Access;

// Private syntax traversal and ordinary-statement handlers for Body_Worker.
trait Statement_Checking
{
    /** Traverse lexical and control scopes iteratively, completing typed statements and their flow graph. */
    private function check_statements(int $return_type, bool $void): array
    {
        $tree = $this->owner->frontend->syntax;
        $pending = [$this->enter($this->owner->body_node_id)];
        $flow = new Flow_Builder();
        $flow->begin($flow->reserve(), 0, 1);

        // Advance siblings before dispatch; cursors resume nested work without recursion.
        while ($pending !== [])
        {
            $cursor = $pending[count($pending) - 1];
            if ($cursor instanceof control_cursor)
            {
                $next = $this->resume_control($cursor, $flow);
                if ($next === null) {
                    array_pop($pending);
                }
                else {
                    $pending[] = $next;
                }
                continue;
            }
            $id = $cursor->next_statement_id;
            if ($id === 0) {
                $this->scopes[$cursor->scope_id - 1] = new typed_scope($cursor->statement_start,
                    count($this->statements) - $cursor->statement_start);
                array_pop($pending);
                continue;
            }
            $cursor->next_statement_id = $tree->nodes[$id - 1]->next_sibling_id;
            $next = $this->check_statement($id, $cursor->scope_id, $return_type, $void, $flow);
            if ($next !== null) {
                $pending[] = $next;
            }
        }
        return $flow->complete(count($this->statements));
    }

    /**
     * Structural statements schedule cursors; ordinary handlers return one completed row.
     */
    private function check_statement(int $id, int $scope, int $return_type, bool $void,
        Flow_Builder $flow): body_cursor|control_cursor|null
    {
        $kind = $this->owner->frontend->syntax->nodes[$id - 1]->kind;
        if ($kind === syntax_kind::block) {
            return $this->enter($id);
        }
        $flow->ensure(count($this->statements), $scope);
        if (($kind === syntax_kind::if_statement) || ($kind === syntax_kind::while_statement)) {
            return $this->check_control($id, $scope, $kind, $flow);
        }
        if ($kind === syntax_kind::echo_statement) {
            $this->check_echo($id, $scope);
            return null;
        }
        $statement = match ($kind) {
            syntax_kind::local_declaration => $this->check_local_declaration($id, $scope),
            syntax_kind::assignment_statement => $this->check_assignment($id, $scope),
            syntax_kind::return_statement => $this->check_return($id, $scope, $return_type, $void),
            syntax_kind::expression_statement => $this->check_expression_statement($id, $scope),
            default => $this->fail($id, 'Unsupported statement for body checking'),
        };
        $this->statements[] = $statement;
        if ($statement->kind === statement_kind::return_statement) {
            $flow->terminate(count($this->statements), flow_end::return_exit);
        }
        return null;
    }

    /** Lower each echo operand to a separate checked call/full expression, preserving Simple C++ output order. */
    private function check_echo(int $id, int $scope): void
    {
        $tree = $this->owner->frontend->syntax;
        for ($operand = $tree->nodes[$id - 1]->first_child_id; $operand !== 0; $operand = $tree->nodes[$operand - 1]->next_sibling_id)
        {
            $start = count($this->calls);
            $value = $this->check_expression($operand);
            $type = $value === 0 ? 0 : $this->values[$value - 1]->type_id;
            $target = $this->types->language_callable(\type_model\language_binding::echo_value, $type);
            if ($target === 0) {
                $this->fail($operand, 'No echo contract for this value type');
            }
            $signature = $this->signature($target);
            $this->bound_call($operand, $target, $signature, $value);
            $this->statements[] = new typed_statement($id, statement_kind::expression_statement, 0,
                $start, count($this->calls) - $start, $scope);
        }
    }

    /** Build a root location for the resolved declaration and check its initializer. */
    private function check_local_declaration(int $id, int $scope): typed_statement
    {
        $parts = Syntax_Access::local_declaration_parts($this->owner->frontend->syntax, $id);
        $target = $this->names->local_for_declaration($id);
        return $this->check_local_write($id, $scope, new place($target), $this->local_type($target), $parts->initializer_id,
            statement_kind::local_declaration, conversion_use::initialization);
    }

    /** Require a resolved write binding before checking the assignment value and destination. */
    private function check_assignment(int $id, int $scope): typed_statement
    {
        $parts = Syntax_Access::assignment_parts($this->owner->frontend->syntax, $id);
        $start = count($this->calls);
        [$target, $type] = $this->check_place($parts->target_id, true);
        return $this->check_local_write($id, $scope, $target, $type, $parts->value_id,
            statement_kind::assignment, conversion_use::assignment, $start);
    }

    /** Check return presence and conversion against the declared result, selecting owned result construction. */
    private function check_return(int $id, int $scope, int $return_type, bool $void): typed_statement
    {
        $start = count($this->calls);
        $expression = Syntax_Access::statement_expression($this->owner->frontend->syntax, $id);
        $value = $expression === 0 ? 0 : $this->check_expression($expression, $return_type);
        if (($expression !== 0) && ($value === 0)) {
            $this->fail($expression, 'Return expression produces no value');
        }
        if (($value === 0) && (!$void)) {
            $this->fail($id, 'A value is required by the declared return type');
        }
        if (($value !== 0) && ($void)) {
            $this->fail($id, 'Cannot return a value from a void function');
        }
        if ($value !== 0) {
            $value = $this->convert($value, $return_type, conversion_use::return_value, $expression);
        }
        $mode = $void ? return_kind::value : $this->return_construction($value, $return_type, $expression);
        return new typed_statement($id, statement_kind::return_statement, $value,
            $start, count($this->calls) - $start, $scope, return: $mode);
    }

    /** Select construction before resolving location access; generic permissions never grow at instantiation. */
    private function return_construction(int $value, int $type, int $node): return_kind
    {
        $signature = $this->signature($this->callable_id);
        if ($signature->result !== \type_model\result_production::owned) {
            $this->select_place_access($value, false);
            return return_kind::value;
        }
        $source = $this->values[$value - 1];
        if (!($source instanceof pending_place_value)) {
            return $source->kind === value_kind::record_default ? return_kind::store : return_kind::direct_construct;
        }

        // Only an owned root local is eligible for implicit construction from an expiring source.
        $life = $this->types->definition_for($type)->lifetime;
        $location = $source->location;
        $owned = ($location->projections === []) && ($location->local_id > $this->names->parameter_count);
        $parts = Syntax_Access::function_parts($this->owner->frontend->syntax,
            Syntax_Access::underlying_declaration($this->owner->frontend->syntax, $this->owner->declaration_node_id));
        $return_node = $this->owner->frontend->syntax->nodes[$parts->return_type_id - 1];
        $generic = ($return_node->kind !== syntax_kind::template_application)
            && ($this->names->name_for($parts->return_type_id)->kind === \resolve_symbols\reference_kind::template_parameter);
        if (($owned) && (!$generic))
        {
            $mode = match ($life->expiring) {
                \type_model\expiring_construction::value => return_kind::store,
                \type_model\expiring_construction::construct => return_kind::move_construct,
                \type_model\expiring_construction::copy => $this->copy_return($life, $node),
                default => $this->fail($node, 'Construction from an expiring return source is unavailable'),
            };
        }
        else {
            $mode = $this->copy_return($life, $node);
        }
        $this->select_place_access($value, $mode !== return_kind::store);
        return $mode;
    }

    /** Borrowed and generic returns need copy permission, independently of native move availability. */
    private function copy_return(\type_model\lifetime_contract $life, int $node): return_kind
    {
        return match ($life->copy) {
            \type_model\copy_kind::value => return_kind::store,
            \type_model\copy_kind::construct => return_kind::copy_construct,
            default => $this->fail($node, 'Owned return requires copy construction for this source'),
        };
    }

    /** Check the full expression and retain its ordered effects for lifetime analysis. */
    private function check_expression_statement(int $id, int $scope): typed_statement
    {
        $start = count($this->calls);
        $expression = Syntax_Access::statement_expression($this->owner->frontend->syntax, $id);
        $value = $expression === 0 ? 0 : $this->check_expression($expression);
        // An unused location does not request a copy or a load.
        $this->select_place_access($value, true);
        return new typed_statement($id, statement_kind::expression_statement, $value,
            $start, count($this->calls) - $start, $scope);
    }

}
