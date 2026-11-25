<?php
declare(strict_types=1);
namespace check_bodies;
trait Statement_Checking {
    private function enter(int $id): Body_Cursor {
        $scope = $this->context->names->scope_for_block($id); $node = $this->tree()->row($id);
        if ((int)$node->kind !== \parse\SYNTAX_BLOCK) { throw new \LogicException('Expected callable block'); }
        return new Body_Cursor($scope,$this->output->statement_count(),(int)$node->first_child);
    }
    private function check_statements(int $return_type, bool $is_void): array /** vector<Typed_Block> */ {
        $pending = new Body_Statement_Stack();
        $pending->push(new Body_Statement_Frame($this->enter((int)$this->context->input->owner->source_fact()->body_node_id),null));
        $flow = new Flow_Builder(); $flow->begin($flow->reserve(),0,1);
        while (!$pending->empty()) {
            $frame = $pending->top(); $control = $frame->control;
            if ($control !== null) {
                $next = $this->resume_control($control,$flow);
                if ($next === null) { $pending->pop(); } else { $pending->push(new Body_Statement_Frame($next,null)); }
                continue;
            }
            $cursor = $frame->body;
            if ($cursor === null) { throw new \LogicException('Missing body continuation'); }
            $id = $cursor->next;
            if ($id === 0) {
                $this->output->complete_scope($cursor->scope,new Typed_Scope($cursor->start,$this->output->statement_count() - $cursor->start));
                $pending->pop(); continue;
            }
            $cursor->next = (int)$this->tree()->row($id)->next_sibling;
            $next_frame = $this->check_statement($id,$cursor->scope,$return_type,$is_void,$flow);
            if ($next_frame !== null) { $pending->push($next_frame); }
        }
        return $flow->complete($this->output->statement_count());
    }
    private function check_statement(int $id, int $scope, int $return_type, bool $is_void, Flow_Builder $flow): ?Body_Statement_Frame {
        $kind = (int)$this->tree()->row($id)->kind;
        if ($kind === \parse\SYNTAX_BLOCK) { return new Body_Statement_Frame($this->enter($id),null); }
        $flow->ensure($this->output->statement_count(),$scope);
        if (($kind === \parse\SYNTAX_IF_STATEMENT) || ($kind === \parse\SYNTAX_WHILE_STATEMENT)) { return new Body_Statement_Frame(null,$this->check_control($id,$scope,$kind,$flow)); }
        if ($kind === \parse\SYNTAX_ECHO_STATEMENT) { $this->check_echo($id,$scope); return null; }
        if ($kind === \parse\SYNTAX_LOCAL_DECLARATION) { $this->output->append_statement($this->check_local_declaration($id,$scope)); }
        elseif ($kind === \parse\SYNTAX_ASSIGNMENT_STATEMENT) { $this->output->append_statement($this->check_assignment($id,$scope)); }
        elseif ($kind === \parse\SYNTAX_RETURN_STATEMENT) {
            $this->output->append_statement($this->check_return($id,$scope,$return_type,$is_void));
            $flow->terminate($this->output->statement_count(),\check_bodies\FLOW_RETURN,0,0);
        } elseif ($kind === \parse\SYNTAX_EXPRESSION_STATEMENT) {
            $start = $this->output->call_count(); $expression = \parse\Syntax_Access::statement_expression($this->tree(),$id);
            $value = $expression === 0 ? 0 : $this->check_expression($expression,0);
            $this->output->select_place_access($value,true);
            $this->output->append_statement(new Typed_Statement($id,\check_bodies\STATEMENT_EXPRESSION,$value,$start,$this->output->call_count()-$start,$scope));
        } else { $this->fail($id,'Unsupported statement for body checking'); }
        return null;
    }
    private function check_echo(int $id, int $scope): void {
        $tree = $this->tree();
        for ($operand = (int)$tree->row($id)->first_child; $operand !== 0; $operand = (int)$tree->row($operand)->next_sibling) {
            $start = $this->output->call_count(); $value = $this->check_expression($operand,0);
            $type = $value === 0 ? 0 : $this->output->value_for($value)->type_id;
            $target = $this->context->types->language_callable(\type_model\BINDING_ECHO,$type);
            if ($target === 0) { $this->fail($operand,'No echo contract for this value type'); }
            $this->bound_call($operand,$target,$this->context->signature($target),$value);
            $this->output->append_statement(new Typed_Statement($id,\check_bodies\STATEMENT_EXPRESSION,0,$start,$this->output->call_count()-$start,$scope));
        }
    }
    private function check_local_declaration(int $id, int $scope): Typed_Statement {
        $parts = \parse\Syntax_Access::local_declaration_parts($this->tree(),$id); $local = $this->context->names->local_for_declaration($id);
        $path /** vector<Place_Projection> */ = [];
        return $this->check_local_write($id,$scope,new Place($local,$path),$this->context->local_type($local),(int)$parts->initializer_id,
            \check_bodies\STATEMENT_LOCAL_DECLARATION,'initialization',$this->output->call_count());
    }
    private function check_assignment(int $id, int $scope): Typed_Statement {
        $parts = \parse\Syntax_Access::assignment_parts($this->tree(),$id); $start = $this->output->call_count();
        $cursor = $this->check_place((int)$parts->target_id,true);
        return $this->check_local_write($id,$scope,new Place($cursor->local,$cursor->projections),$cursor->type,(int)$parts->value_id,
            \check_bodies\STATEMENT_ASSIGNMENT,'assignment',$start);
    }
    private function check_return(int $id, int $scope, int $return_type, bool $is_void): Typed_Statement {
        $start = $this->output->call_count(); $expression = \parse\Syntax_Access::statement_expression($this->tree(),$id);
        $value = $expression === 0 ? 0 : $this->check_expression($expression,$return_type);
        if (($expression !== 0) && ($value === 0)) { $this->fail($expression,'Return expression produces no value'); }
        if (($value === 0) && !$is_void) { $this->fail($id,'A value is required by the declared return type'); }
        if (($value !== 0) && $is_void) { $this->fail($id,'Cannot return a value from a void function'); }
        if ($value !== 0) { $value = $this->convert($value,$return_type,'return',$expression); }
        $mode = $is_void ? \check_bodies\RETURN_VALUE : $this->return_construction($value,$return_type,$expression);
        return new Typed_Statement($id,\check_bodies\STATEMENT_RETURN,$value,$start,$this->output->call_count()-$start,$scope,null,\check_bodies\WRITE_VALUE_COPY,$mode);
    }
    private function return_construction(int $value, int $type, int $node): int {
        $signature = $this->context->signature($this->context->input->callable_id);
        if ($signature->representation->result_production() !== \type_model\RESULT_OWNED) { $this->output->select_place_access($value,false); return \check_bodies\RETURN_VALUE; }
        $source = $this->output->value_for($value); $location = $source->pending;
        if ($location === null) {
            $completed = $source->completed; if ($completed === null) { throw new \LogicException('Missing return value'); }
            return $completed->kind === \check_bodies\VALUE_RECORD_DEFAULT ? \check_bodies\RETURN_STORE : \check_bodies\RETURN_DIRECT_CONSTRUCT;
        }
        $life = $this->life($type); $owned = ($location->size() === 0) && ($location->local_id > $this->context->names->runtime_parameter_count());
        $tree = $this->tree(); $owner = $this->context->input->owner;
        $parts = \parse\Syntax_Access::function_parts($tree,\parse\Syntax_Access::underlying_declaration($tree,(int)$owner->source_fact()->declaration_node_id));
        $return_node = (int)$parts->return_type_id; $generic = false;
        if ((int)$tree->row($return_node)->kind !== \parse\SYNTAX_TEMPLATE_APPLICATION) {
            $generic = $this->context->names->name_for($return_node)->kind === \resolve_symbols\REFERENCE_TEMPLATE_PARAMETER;
        }
        $mode = \check_bodies\RETURN_VALUE;
        if ($owned && !$generic) {
            $expiring = (int)$life->expiring;
            if ($expiring === \type_model\EXPIRING_VALUE) { $mode = \check_bodies\RETURN_STORE; }
            elseif ($expiring === \type_model\EXPIRING_CONSTRUCT) { $mode = \check_bodies\RETURN_MOVE_CONSTRUCT; }
            elseif ($expiring === \type_model\EXPIRING_COPY) { $mode = $this->copy_return($life,$node); }
            else { $this->fail($node,'Construction from an expiring return source is unavailable'); }
        } else { $mode = $this->copy_return($life,$node); }
        $this->output->select_place_access($value,$mode !== \check_bodies\RETURN_STORE); return $mode;
    }
    private function copy_return(\type_model\Lifetime_Policy $life, int $node): int {
        $mode = \check_bodies\RETURN_VALUE;
        if ((int)$life->copy === \type_model\COPY_VALUE) { $mode = \check_bodies\RETURN_STORE; }
        elseif ((int)$life->copy === \type_model\COPY_CONSTRUCT) { $mode = \check_bodies\RETURN_COPY_CONSTRUCT; }
        else { $this->fail($node,'Owned return requires copy construction for this source'); }
        return $mode;
    }
}
