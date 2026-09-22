<?php
declare(strict_types=1);

/*
 * Role: Prepare and resume typed branch/loop flow.
 * Used by: Body_Worker (private trait methods on this owner)
 * Call map:
 *   check_control(); resume_control()
 *     -> [action] check condition, schedule bodies and connect continuation
 */

namespace check_bodies;

use parse\syntax_kind;
use parse\Syntax_Access;
use type_model\representation_kind;

// Private branch/loop construction and cursor resumption for Body_Worker.
trait Control_Statement_Checking
{
    /** Check the integer condition and reserve the branch or loop blocks before checking their bodies. */
    private function check_control(int $id, int $scope, syntax_kind $kind, Flow_Builder $flow): control_cursor
    {
        $parts = Syntax_Access::control_parts($this->owner->frontend->syntax, $id);
        $header = 0;
        if ($kind === syntax_kind::while_statement) {
            $header = $flow->reserve();
            $flow->terminate(count($this->statements), flow_end::jump, $header);
            $flow->begin($header, count($this->statements), $scope);
        }
        $start = count($this->calls);
        $condition = $this->check_expression($parts->condition);
        if (($condition === 0) || ($this->types->types->representation_for_type($this->values[$condition - 1]->type_id)->kind !== representation_kind::integer)) {
            $this->fail($parts->condition, 'Condition requires an integer value');
        }
        $this->select_place_access($condition, false);
        $this->statements[] = new typed_statement($id, statement_kind::condition, $condition,
            $start, count($this->calls) - $start, $scope);
        $body_block = $flow->reserve();
        $alternative = $parts->alternative !== 0 ? $flow->reserve() : 0;
        $join = $flow->reserve();
        $flow->terminate(count($this->statements), flow_end::branch, $body_block, $alternative ?: $join);
        return new control_cursor($parts, $scope, $body_block, $alternative, $join, $header);
    }

    /** Schedule the next lexical body, respecting terminated paths, or return null after establishing the continuation. */
    private function resume_control(control_cursor $cursor, Flow_Builder $flow): ?body_cursor
    {
        if ($cursor->stage === 0) {
            $cursor->stage = 1;
            $flow->begin($cursor->body_block, count($this->statements), $this->names->scope_for_block($cursor->parts->body));
            return $this->enter($cursor->parts->body);
        }
        $flow->terminate(count($this->statements), flow_end::jump, $cursor->loop_header ?: $cursor->join_block);
        if (($cursor->stage === 1) && ($cursor->parts->alternative !== 0)) {
            $cursor->stage = 2;
            $flow->begin($cursor->alternative_block, count($this->statements), $this->names->scope_for_block($cursor->parts->alternative));
            return $this->enter($cursor->parts->alternative);
        }
        $flow->begin($cursor->join_block, count($this->statements), $cursor->scope_id);
        return null;
    }
}
