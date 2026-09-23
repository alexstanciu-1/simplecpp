<?php
declare(strict_types=1);
namespace analyze_lifetimes;
trait Local_Lifetimes {
    private function start_local(int $id, int $statement): void {
        if (isset($this->live[$id])) { throw new \LogicException('Local initialized more than once on a path'); }
        $row = new Active_Local($id,$statement);
        if ($this->active_depth === q_count($this->active)) { $this->active[] = $row; } else { $this->active[$this->active_depth] = $row; }
        $this->active_depth++; $this->live[$id] = $row;
    }
    private function exit_to_scope(int $scope, int $boundary): void {
        while ($this->active_depth > 0) {
            $last = $this->active[$this->active_depth-1];
            if (Local_Flow::contains($this->body,(int)$this->body->names->local_for($last->local_id)->scope_id,$scope)) { break; }
            $this->end_local($boundary,\analyze_lifetimes\LOCAL_SCOPE_EXIT);
        }
    }
    private function end_local(int $boundary, int $end): void {
        if ($this->active_depth === 0) { throw new \LogicException('Missing active local'); }
        $this->active_depth = $this->active_depth-1; $local = $this->active[$this->active_depth]; $id = $local->local_id; unset($this->live[$id]);
        if (!\type_model\Semantic_Modes::is_borrow($this->body->local_passing($id))) {
            if ((int)$this->life($this->body->local_type_for($id))->cleanup === \type_model\CLEANUP_DESTROY) { $this->cleanups[] = new Cleanup_Obligation(\analyze_lifetimes\CLEANUP_LOCAL,$id,$boundary,$this->block_id); }
        }
        $this->locals[] = new Local_Lifetime($id,$local->initialized_statement_id,$boundary,$end,$this->block_id);
    }
    private function validate_local_target(\check_bodies\Typed_Statement $statement): void {
        $body = $this->body; $place = $statement->target;
        if ($place === null) { throw new \LogicException('Missing local target'); }
        $target = $place->local_id; $local = $body->names->local_for($target);
        if ($statement->kind === \check_bodies\STATEMENT_LOCAL_DECLARATION) {
            if (isset($this->live[$target]) || ((int)$local->scope_id !== $statement->scope_id) || ((int)$local->declaration_node_id !== $statement->source_node_id)) { throw new \LogicException('Invalid local initialization lifetime'); }
        } else { if (!isset($this->live[$target])) { throw new \LogicException('Assignment requires a live initialized local'); } }
        if ($body->local_passing($target) === \type_model\PASS_BORROW_CONST) { throw new \LogicException('Assignment cannot write through a const reference'); }
        $type = $body->place_type($place); $life = $this->life($type); $write = $statement->write_kind;
        $this->contract($type,$statement->source_node_id,($statement->kind === \check_bodies\STATEMENT_LOCAL_DECLARATION) && ($write === \check_bodies\WRITE_VALUE_COPY));
        if ($statement->kind === \check_bodies\STATEMENT_ASSIGNMENT) {
            if (($write === \check_bodies\WRITE_VALUE_COPY) && ((int)$life->assignment !== \type_model\ASSIGNMENT_VALUE)) { throw new \LogicException('Value assignment requires its type contract'); }
        }
        $value = $body->value_for($statement->value_id);
        if ($write === \check_bodies\WRITE_ZERO_INITIALIZE) {
            if (((int)$life->construction !== \type_model\CONSTRUCTION_ZERO) || ($value->kind !== \check_bodies\VALUE_RECORD_DEFAULT)) { throw new \LogicException('Zero initialization requires its construction contract and checked initializer'); }
        }
        if ($write === \check_bodies\WRITE_COPY_CONSTRUCT) {
            if (((int)$life->copy !== \type_model\COPY_CONSTRUCT) || ($value->kind !== \check_bodies\VALUE_LOCAL_BORROW)) { throw new \LogicException('Copy construction requires its type contract and a borrowed local source'); }
        }
        if ($write === \check_bodies\WRITE_COPY_ASSIGN) {
            if (((int)$life->assignment !== \type_model\ASSIGNMENT_CALL) || ($value->kind !== \check_bodies\VALUE_LOCAL_BORROW)) { throw new \LogicException('Copy assignment requires its type contract and a borrowed source'); }
        }
        if ($value->type_id !== $type) { $this->fail($statement->source_node_id,'Unsupported lifetime analysis for local conversion'); }
    }
}
