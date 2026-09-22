<?php
declare(strict_types=1);

/*
 * Role: Emit one lowered block terminator.
 * Used by: Emission_Worker (private trait methods on this owner)
 * Call map:
 *   emit_terminator()
 *     -> emit_jump() / emit_branch() / emit_return()
 */

namespace emit_llvm;

use prepare_backend\LLVM_Types;

// Private handlers over completed lowering plans; composed by Emission_Worker.
trait Terminator_Emission
{
    private function emit_terminator(\lower\jump_terminator|\lower\branch_terminator|\lower\return_terminator $terminator,
        int $block_id): void
    {
        match (true) {
            $terminator instanceof \lower\jump_terminator => $this->emit_jump($terminator),
            $terminator instanceof \lower\branch_terminator => $this->emit_branch($terminator, $block_id),
            default => $this->emit_return($terminator),
        };
    }

    private function emit_jump(\lower\jump_terminator $terminator): void
    {
        $this->ir .= '  br label %b' . $terminator->target . "\n";
    }

    /** Convert the checked integer condition to a nonzero test and emit its two branch targets. */
    private function emit_branch(\lower\branch_terminator $terminator, int $block_id): void
    {
        $body = $this->body;
        $id = $terminator->value_id;
        $value = $body->values[$id - 1] ?? throw new \LogicException('Missing LLVM condition');
        $shape = $body->definition_for($value->type_id)->representation;
        if ($shape->kind !== \type_model\representation_kind::integer) {
            throw new \LogicException('LLVM condition requires integer representation');
        }
        $operand = $this->operands[$id] ?? throw new \LogicException('Undefined LLVM condition');
        $condition = '%condition' . $block_id;
        $this->ir .= '  ' . $condition . ' = icmp ne ' . LLVM_Types::scalar($shape) . ' ' . $operand . ", 0\n";
        $this->ir .= '  br i1 ' . $condition . ', label %b' . $terminator->first . ', label %b' . $terminator->second . "\n";
    }

    /** Emit a void or scalar return only when it matches the prepared callable result. */
    private function emit_return(\lower\return_terminator $terminator): void
    {
        $body = $this->body;
        $id = $terminator->value_id;
        if ($id === 0) {
            if ($this->return_type !== 'void') {
                throw new \LogicException('Non-void LLVM return requires a value');
            }
            $this->ir .= "  ret void\n";
        }
        else
        {
            $value = $body->values[$id - 1] ?? throw new \LogicException('Missing LLVM return value');
            $type = LLVM_Types::scalar($body->definition_for($value->type_id)->representation);
            if ($type !== $this->return_type) {
                throw new \LogicException('LLVM return shape differs from prepared contract');
            }
            $operand = $this->operands[$id] ?? throw new \LogicException('Undefined LLVM return operand');
            $this->ir .= '  ret ' . $type . ' ' . $operand . "\n";
        }
    }
}
