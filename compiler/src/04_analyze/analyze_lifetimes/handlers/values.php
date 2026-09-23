<?php
declare(strict_types=1);
namespace analyze_lifetimes;
trait Value_Lifetimes {
    /** Returns the consumed call boundary; no by-reference scalar output. */
    private function evaluate(int $value, int $statement, int $next_call, int $limit): int {
        $order = \check_bodies\Expression_Order::steps($this->body,$value,$next_call,$limit);
        $step = $order->next();
        while ($step !== null) {
            if ($step->call_id !== 0) { $this->consume_call_arguments($step->call_id,$statement); $next_call++; }
            else if ($step->value_id !== 0) {
                $id = $step->value_id; $kind = $this->body->value_for($id)->kind;
                if ($kind === \check_bodies\VALUE_CONVERSION) { $this->consume_conversion_input($id,$statement); }
                else if ($kind === \check_bodies\VALUE_OPERATION) { $this->consume_operation_inputs($id,$statement); }
                else if (($kind === \check_bodies\VALUE_LOCAL_READ) || ($kind === \check_bodies\VALUE_LOCAL_BORROW)) { $this->consume_indices($id,$statement); }
            }
            if ($step->value_id !== 0) {
                $row = $this->body->value_for($step->value_id); $this->contract($row->type_id,$row->source_node_id,false); $this->produce_value($step->value_id);
            }
            $step = $order->next();
        }
        return $next_call;
    }
    private function consume_indices(int $id, int $statement): void {
        $place = $this->body->value_for($id)->place();
        for ($i = 0; $i < $place->size(); $i++) {
            $projection = $place->at($i);
            if ($projection->kind !== \check_bodies\PROJECTION_FIELD) { $this->end_value($projection->operand,$statement,\analyze_lifetimes\END_INDEX_INPUT,$id); }
        }
    }
    private function consume_call_arguments(int $id, int $statement): void {
        $call = $this->body->call_for($id);
        for ($position = 1; $position < $call->argument_count+1; $position++) {
            $argument = $this->body->argument_for($id,$position); $value = $this->body->value_for($argument->value_id);
            if ($value->type_id !== $argument->parameter_type_id) { $this->fail($value->source_node_id,'Unsupported lifetime analysis for argument conversion'); }
            if ($argument->passing !== $this->body->signature_for($call->target_callable_id)->representation->parameter_passing($position-1)) { throw new \LogicException('Argument passing differs from its checked signature'); }
            if ($argument->passing === \type_model\PASS_BORROW_MUTABLE) {
                if ($value->kind !== \check_bodies\VALUE_LOCAL_BORROW) { throw new \LogicException('Mutable argument requires writable local storage'); }
                if ($this->body->local_passing($value->place()->local_id) === \type_model\PASS_BORROW_CONST) { throw new \LogicException('Mutable argument requires writable local storage'); }
            }
            $borrow = $argument->passing !== \type_model\PASS_VALUE;
            $this->contract($argument->parameter_type_id,$value->source_node_id,!$borrow);
            $this->end_value($argument->value_id,$statement,$borrow ? \analyze_lifetimes\END_ARGUMENT_BORROW : \analyze_lifetimes\END_ARGUMENT_COPY,$id);
        }
    }
    private function consume_conversion_input(int $id, int $statement): void {
        $conversion = $this->body->conversion_for($id);
        if ($conversion->operation !== \check_bodies\CONVERSION_INTEGER_WIDEN) { throw new \LogicException('Unsupported lifetime conversion operation'); }
        $this->end_value($conversion->input_value_id,$statement,\analyze_lifetimes\END_CONVERSION_INPUT,$id);
    }
    private function consume_operation_inputs(int $id, int $statement): void {
        $operation = $this->body->operation_for($id);
        $this->end_value($operation->left,$statement,\analyze_lifetimes\END_OPERATION_INPUT,$id);
        $this->end_value($operation->right,$statement,\analyze_lifetimes\END_OPERATION_INPUT,$id);
    }
    private function produce_value(int $id): void {
        if (isset($this->live_values[$id]) || isset($this->consumed[$id])) { throw new \LogicException('Temporary produced or consumed more than once'); }
        $value = $this->body->value_for($id);
        if (($value->kind === \check_bodies\VALUE_LOCAL_READ) || ($value->kind === \check_bodies\VALUE_LOCAL_BORROW)) {
            $place = $value->place(); $local = $place->local_id;
            if (!isset($this->live[$local])) { throw new \LogicException('Read requires a live initialized local of the checked type'); }
            if ($place->size() === 0) { if ($value->type_id !== $this->body->local_type_for($local)) { throw new \LogicException('Read requires a live initialized local of the checked type'); } }
            if ($this->body->place_type($place) !== $value->type_id) { throw new \LogicException('Invalid projected lifetime read'); }
        }
        if (($value->kind === \check_bodies\VALUE_CALL_RESULT) || ($value->kind === \check_bodies\VALUE_DEFAULT_CONSTRUCT)) {
            if ((int)$this->life($value->type_id)->cleanup === \type_model\CLEANUP_DESTROY) {
                if ($this->temporary_depth === q_count($this->temporaries)) { $this->temporaries[] = $id; } else { $this->temporaries[$this->temporary_depth] = $id; }
                $this->temporary_depth++;
            }
        }
        $this->live_values[$id] = true;
    }
    private function end_value(int $id, int $statement, int $end, int $consumer): void {
        if (!isset($this->live_values[$id]) || isset($this->consumed[$id])) { throw new \LogicException('Temporary consumption requires a live value'); }
        $value = $this->body->value_for($id);
        $copy = ($end === \analyze_lifetimes\END_LOCAL_COPY) || ($end === \analyze_lifetimes\END_RETURN_COPY) || ($end === \analyze_lifetimes\END_ARGUMENT_COPY);
        $this->contract($value->type_id,$value->source_node_id,$copy);
        if ($end === \analyze_lifetimes\END_ASSIGNMENT_SOURCE) {
            if ((int)$this->life($value->type_id)->assignment === \type_model\ASSIGNMENT_UNAVAILABLE) { $this->fail($value->source_node_id,'Copy assignment is unavailable for this type'); }
        }
        unset($this->live_values[$id]); $this->consumed[$id] = true;
        $this->values[] = new Value_Lifetime($id,$statement,$end,$consumer);
    }
}
