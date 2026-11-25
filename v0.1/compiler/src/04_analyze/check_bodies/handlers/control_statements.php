<?php
declare(strict_types=1);
namespace check_bodies;
trait Control_Statement_Checking {
    private function check_control(int $id, int $scope, int $kind, Flow_Builder $flow): Control_Cursor {
        $parts = \parse\Syntax_Access::control_parts($this->tree(),$id); $header = 0;
        if ($kind === \parse\SYNTAX_WHILE_STATEMENT) {
            $header = $flow->reserve(); $flow->terminate($this->output->statement_count(),\check_bodies\FLOW_JUMP,$header,0);
            $flow->begin($header,$this->output->statement_count(),$scope);
        }
        $start = $this->output->call_count(); $condition = $this->check_expression((int)$parts->condition,0);
        if ($condition === 0) { $this->fail((int)$parts->condition,'Condition requires an integer value'); }
        if ($this->context->types->definition_for($this->output->value_for($condition)->type_id)->representation->kind() !== \type_model\REPRESENTATION_INTEGER) { $this->fail((int)$parts->condition,'Condition requires an integer value'); }
        $this->output->select_place_access($condition,false);
        $this->output->append_statement(new Typed_Statement($id,\check_bodies\STATEMENT_CONDITION,$condition,$start,$this->output->call_count()-$start,$scope));
        $body = $flow->reserve(); $alternative = (int)$parts->alternative === 0 ? 0 : $flow->reserve(); $join = $flow->reserve();
        $flow->terminate($this->output->statement_count(),\check_bodies\FLOW_BRANCH,$body,$alternative === 0 ? $join : $alternative);
        return new Control_Cursor($scope,(int)$parts->body,(int)$parts->alternative,$body,$alternative,$join,$header);
    }
    private function resume_control(Control_Cursor $cursor, Flow_Builder $flow): ?Body_Cursor {
        if ($cursor->stage === 0) {
            $cursor->stage = 1; $flow->begin($cursor->body_block,$this->output->statement_count(),$this->context->names->scope_for_block($cursor->body));
            return $this->enter($cursor->body);
        }
        $flow->terminate($this->output->statement_count(),\check_bodies\FLOW_JUMP,$cursor->header === 0 ? $cursor->join : $cursor->header,0);
        if (($cursor->stage === 1) && ($cursor->alternative !== 0)) {
            $cursor->stage = 2; $flow->begin($cursor->alternative_block,$this->output->statement_count(),$this->context->names->scope_for_block($cursor->alternative));
            return $this->enter($cursor->alternative);
        }
        $flow->begin($cursor->join,$this->output->statement_count(),$cursor->scope); return null;
    }
}
