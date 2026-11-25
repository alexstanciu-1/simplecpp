<?php
declare(strict_types=1);
namespace check_bodies;
trait Local_Write_Checking {
    private function check_local_write(int $id, int $scope, Place $target, int $destination, int $expression, int $kind, string $role, int $start): Typed_Statement {
        $value = 0;
        if ($expression === 0) { $value = $this->default_value($id,$destination); }
        else { $value = $this->check_expression($expression,$destination); $value = $this->convert($value,$destination,$role,$expression); }
        $life = $this->life($destination); $node = $expression === 0 ? $id : $expression;
        $write = $kind === \check_bodies\STATEMENT_ASSIGNMENT ? $this->assignment_write($value,$life,$node) : $this->initialization_write($value,$life,$node);
        return new Typed_Statement($id,$kind,$value,$start,$this->output->call_count()-$start,$scope,$target,$write);
    }
    private function initialization_write(int $id, \type_model\Lifetime_Policy $life, int $node): int {
        $row = $this->output->value_for($id);
        if ($row->pending !== null) {
            if ((int)$life->copy === \type_model\COPY_UNAVAILABLE) { $this->fail($node,'Unsupported inline object copy; copy construction is unavailable'); }
            $construct = (int)$life->copy === \type_model\COPY_CONSTRUCT;
            $this->output->select_place_access($id,$construct);
            return $construct ? \check_bodies\WRITE_COPY_CONSTRUCT : \check_bodies\WRITE_VALUE_COPY;
        }
        $source = $row->completed; if ($source === null) { throw new \LogicException('Missing write source'); }
        if ($source->kind === \check_bodies\VALUE_RECORD_DEFAULT) { return \check_bodies\WRITE_ZERO_INITIALIZE; }
        if ($source->kind === \check_bodies\VALUE_DEFAULT_CONSTRUCT) { return \check_bodies\WRITE_DIRECT_CONSTRUCT; }
        if ($source->kind === \check_bodies\VALUE_CALL_RESULT) {
            $target = $this->output->call_for($source->call_id())->target_callable_id;
            if ($this->context->signature($target)->representation->result_production() === \type_model\RESULT_OWNED) { return \check_bodies\WRITE_DIRECT_CONSTRUCT; }
        }
        if (((int)$life->copy !== \type_model\COPY_VALUE) || ((int)$life->cleanup !== \type_model\CLEANUP_NONE)) { $this->fail($node,'Unsupported inline object copy; copy construction is unavailable'); }
        return \check_bodies\WRITE_VALUE_COPY;
    }
    private function assignment_write(int $id, \type_model\Lifetime_Policy $life, int $node): int {
        if ((int)$life->assignment === \type_model\ASSIGNMENT_UNAVAILABLE) { $this->fail($node,'Copy assignment is unavailable for this type'); }
        $row = $this->output->value_for($id); $call = (int)$life->assignment === \type_model\ASSIGNMENT_CALL;
        if ($row->pending !== null) { $this->output->select_place_access($id,$call); return $call ? \check_bodies\WRITE_COPY_ASSIGN : \check_bodies\WRITE_VALUE_COPY; }
        $source = $row->completed; if ($source === null) { throw new \LogicException('Missing assignment source'); }
        if ($call || ($source->kind === \check_bodies\VALUE_DEFAULT_CONSTRUCT) || ((int)$life->cleanup !== \type_model\CLEANUP_NONE)) { $this->fail($node,'Copy assignment currently requires an existing source object'); }
        return \check_bodies\WRITE_VALUE_COPY;
    }
}
