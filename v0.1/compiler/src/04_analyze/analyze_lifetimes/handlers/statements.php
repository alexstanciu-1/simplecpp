<?php
declare(strict_types=1);
namespace analyze_lifetimes;
trait Statement_Lifetimes {
    private function end_kind(\check_bodies\Typed_Statement $statement): int {
        $kind = $statement->kind; $write = $statement->write_kind;
        if ($kind === \check_bodies\STATEMENT_EXPRESSION) { return \analyze_lifetimes\END_DISCARD; }
        if ($kind === \check_bodies\STATEMENT_RETURN) { return $statement->return_mode === \check_bodies\RETURN_VALUE ? \analyze_lifetimes\END_RETURN_COPY : \analyze_lifetimes\END_RETURN_CONSTRUCT; }
        if ($kind === \check_bodies\STATEMENT_CONDITION) { return \analyze_lifetimes\END_CONDITION; }
        $end = \analyze_lifetimes\END_ASSIGNMENT_SOURCE;
        if ($write === \check_bodies\WRITE_VALUE_COPY) { if ($kind === \check_bodies\STATEMENT_LOCAL_DECLARATION) { $end = \analyze_lifetimes\END_LOCAL_COPY; } }
        else if (($write === \check_bodies\WRITE_ZERO_INITIALIZE) || ($write === \check_bodies\WRITE_DIRECT_CONSTRUCT)) { $end = \analyze_lifetimes\END_LOCAL_CONSTRUCT; }
        else if ($write === \check_bodies\WRITE_COPY_CONSTRUCT) { $end = \analyze_lifetimes\END_COPY_SOURCE; }
        return $end;
    }
    private function analyze_statement(\check_bodies\Typed_Statement $statement, int $index, int $end_index, \check_bodies\Typed_Block $block): void {
        $body = $this->body; $id = $index+1;
        $this->exit_to_scope($statement->scope_id,$index); $scope = $body->scope_for($statement->scope_id);
        if (($index < $scope->statement_start) || ($index >= $scope->statement_start+$scope->statement_count)) { throw new \LogicException('Checked statement is outside its scope range'); }
        $end = $this->end_kind($statement); $target = $statement->target;
        if ($target !== null) { $this->validate_local_target($statement); }
        $next = $statement->call_start; $limit = $next+$statement->call_count;
        if (($next < 0) || ($statement->call_count < 0) || ($limit > $body->call_count())) { throw new \LogicException('Invalid checked statement call segment'); }
        if ($target !== null) {
            for ($i = 0; $i < $target->size(); $i++) {
                $projection = $target->at($i);
                if ($projection->kind !== \check_bodies\PROJECTION_FIELD) {
                    $next = $this->evaluate($projection->operand,$id,$next,$projection->call_end);
                    $this->end_value($projection->operand,$id,\analyze_lifetimes\END_TARGET_INDEX,0);
                }
            }
        }
        $value = $statement->value_id; $next = $this->evaluate($value,$id,$next,$limit);
        if ($value !== 0) { $this->end_value($value,$id,$end,0); }
        if (($next !== $limit) || (q_count($this->live_values) !== 0)) { throw new \LogicException('Incomplete checked expression consumption'); }
        if ($statement->kind === \check_bodies\STATEMENT_LOCAL_DECLARATION) {
            if ($target === null) { throw new \LogicException('Missing local target'); }
            $this->start_local($target->local_id,$id);
        }
        while ($this->temporary_depth > 0) {
            $this->temporary_depth = $this->temporary_depth-1; $temporary = $this->temporaries[$this->temporary_depth];
            $transferred = ($statement->write_kind === \check_bodies\WRITE_DIRECT_CONSTRUCT) || ($statement->return_mode === \check_bodies\RETURN_DIRECT_CONSTRUCT);
            if (!$transferred || ($temporary !== $value)) { $this->cleanups[] = new Cleanup_Obligation(\analyze_lifetimes\CLEANUP_TEMPORARY,$temporary,$id,$this->block_id); }
        }
        if ($statement->kind === \check_bodies\STATEMENT_RETURN) { $this->validate_return($statement,$id,$end_index,$block); }
    }
    private function validate_return(\check_bodies\Typed_Statement $statement, int $id, int $end_index, \check_bodies\Typed_Block $block): void {
        $body = $this->body; $value = $statement->value_id; $signature = $body->signature_for($body->callable_id)->representation;
        $type = $signature->signature_return(); $owned = $signature->result_production() === \type_model\RESULT_OWNED;
        if ($owned !== ($statement->return_mode !== \check_bodies\RETURN_VALUE)) { throw new \LogicException('Return construction disagrees with result ownership'); }
        if ($owned) {
            $source = $body->value_for($value); $life = $this->life($type); $valid = false; $mode = $statement->return_mode;
            if ($mode === \check_bodies\RETURN_STORE) { $valid = ($source->kind === \check_bodies\VALUE_RECORD_DEFAULT) || (($source->kind === \check_bodies\VALUE_LOCAL_READ) && ((int)$life->copy === \type_model\COPY_VALUE)); }
            else if ($mode === \check_bodies\RETURN_DIRECT_CONSTRUCT) { $valid = ($source->kind === \check_bodies\VALUE_CALL_RESULT) || ($source->kind === \check_bodies\VALUE_DEFAULT_CONSTRUCT); }
            else if ($mode === \check_bodies\RETURN_COPY_CONSTRUCT) { $valid = ($source->kind === \check_bodies\VALUE_LOCAL_BORROW) && ((int)$life->copy === \type_model\COPY_CONSTRUCT); }
            else if ($mode === \check_bodies\RETURN_MOVE_CONSTRUCT) { $valid = ($source->kind === \check_bodies\VALUE_LOCAL_BORROW) && ((int)$life->expiring === \type_model\EXPIRING_CONSTRUCT); }
            if (!$valid) { throw new \LogicException('Return construction lacks its checked source capability'); }
        }
        $is_void = $body->definition_for($type)->representation->kind() === \type_model\REPRESENTATION_VOID;
        if (($value === 0) !== $is_void) { throw new \LogicException('Analyzed return does not match its resolved type or flow'); }
        if ($value !== 0) { if ($body->value_for($value)->type_id !== $type) { throw new \LogicException('Analyzed return does not match its resolved type or flow'); } }
        if (($id !== $end_index) || ($block->end !== \check_bodies\FLOW_RETURN)) { throw new \LogicException('Analyzed return does not match its resolved type or flow'); }
    }
}
