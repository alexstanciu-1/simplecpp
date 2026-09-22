<?php
declare(strict_types=1);

/*
 * Role: Produce iterative expression evaluation order.
 * Used by: Lifetime_Worker and Lowering_Worker
 * Call map:
 *   Expression_Order::steps()
 *     -> Expression_Order::frame()
 */

namespace check_bodies;

/**
 * @compiler-api Ordered traversal of completed checked expressions, never syntax.
 * Yields each leaf or completed operation once after its operands, left to right.
 * Consumers own lifetime actions and lowering. Scratch storage scales with depth;
 * no event list is retained. Call ranges constrain effects; earlier value IDs
 * constrain operand dependencies. Void roots are the final call in their range.
 */
class Expression_Order
{
    /** Yield checked values and completed calls in operand order using private cursors bounded by expression depth. */
    public static function steps(Checked_Body $body, int $root, int $start, int $limit): \Generator
    {
        $call = (($root === 0) && ($start < $limit)) ? $limit : 0;
        if (($call !== 0) && (($body->calls[$call - 1] ?? null)?->result_value_id !== 0)) {
            throw new \LogicException('Missing checked expression result');
        }
        if (($root === 0) && ($call === 0)) {
            return;
        }
        $pending = [self::frame($body, $root, $call, $limit + 1)];
        $next_call = $start;
        while ($pending !== [])
        {
            $frame = $pending[count($pending) - 1];
            if ($frame->next_operand < $frame->operand_count)
            {
                $index = $frame->next_operand++;
                if ($frame->call_id !== 0) {
                    $id = $body->argument_for($frame->call_id, $index + 1)->value_id;
                }
                elseif ($body->values[$frame->value_id - 1]->kind === value_kind::conversion) {
                    $id = $body->conversion_for($frame->value_id)->input_value_id;
                }
                elseif (in_array($body->values[$frame->value_id - 1]->kind, [value_kind::local_read, value_kind::local_borrow], true)) {
                    $projection = $body->values[$frame->value_id - 1]->payload->projections[$index];
                    if ($projection->kind === projection_kind::field) {
                        continue;
                    }
                    $id = $projection->operand;
                }
                else {
                    $operation = $body->operation_for($frame->value_id);
                    $id = $index === 0 ? $operation->left : $operation->right;
                }
                if (($id <= 0) || (($frame->value_id !== 0) && ($id >= $frame->value_id))) {
                    throw new \LogicException('Invalid or cyclic checked operand');
                }
                $pending[] = self::frame($body, $id, 0, $frame->call_limit);
                continue;
            }
            if ($frame->call_id !== 0) {
                if ($frame->call_id !== ++$next_call) {
                    throw new \LogicException('Checked call evaluation order is inconsistent');
                }
            }
            array_pop($pending);
            yield $frame;
        }
        if ($next_call !== $limit) {
            throw new \LogicException('Incomplete checked expression call segment');
        }
    }

    /** Validate one checked expression node and create its operand cursor within the enclosing call bound. */
    private static function frame(Checked_Body $body, int $value_id, int $call_id, int $bound): evaluation_cursor
    {
        $count = 0;
        if ($value_id !== 0)
        {
            $value = $body->values[$value_id - 1] ?? throw new \LogicException('Missing checked value');
            switch ($value->kind)
            {
                case value_kind::call_result:
                    $call_id = $value->payload;
                    if (($body->calls[$call_id - 1] ?? null)?->result_value_id !== $value_id) {
                        throw new \LogicException('Inconsistent checked call result');
                    }
                    break;
                case value_kind::conversion:
                    $body->conversion_for($value_id);
                    $count = 1;
                    break;
                case value_kind::operation:
                    $body->operation_for($value_id);
                    $count = 2;
                    break;
                case value_kind::byte_literal:
                case value_kind::default_construct:
                case value_kind::record_default:
                case value_kind::integer_literal:
                    break;
                case value_kind::local_read:
                case value_kind::local_borrow:
                    $count = count($value->payload->projections);
                    break;
            }
        }
        if ($call_id !== 0)
        {
            if (($call_id <= 0) || ($call_id >= $bound)) {
                throw new \LogicException('Call is repeated, cyclic or outside its expression segment');
            }
            $call = $body->calls[$call_id - 1] ?? throw new \LogicException('Missing checked call');
            $signature = $body->signature_for($call->target_callable_id);
            $void = $body->definition_for($signature->return_type)->representation->kind === \type_model\representation_kind::void_type;
            if ((($call->result_value_id === 0) !== $void)
                || (($value_id !== 0) && ($body->values[$value_id - 1]->type_id !== $signature->return_type))) {
                throw new \LogicException('Inconsistent checked call result presence or type');
            }
            if (($call->argument_start < 0) || ($call->argument_count !== $signature->count)
                || ($call->argument_count > (count($body->arguments) - $call->argument_start))) {
                throw new \LogicException('Invalid checked call argument range');
            }
            $count = $call->argument_count;
            $bound = $call_id;
        }
        return new evaluation_cursor($value_id, $call_id, $count, $bound);
    }
}
